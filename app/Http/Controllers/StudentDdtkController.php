<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentDdtkRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StudentDdtkController extends Controller
{
    /**
     * Display DDTK & SDIDTK Dashboard with 4 Tabs.
     */
    public function index(Request $request)
    {
        $classrooms = Classroom::with(['homeroomTeacher', 'classLevel'])
            ->where('is_active', true)
            ->orderBy('sub_unit', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Default Classroom
        $user = auth()->user();
        $defaultClassroomId = null;
        if ($user && $user->employee_id) {
            $assignedClass = $classrooms->firstWhere('homeroom_teacher_id', $user->employee_id);
            if ($assignedClass) {
                $defaultClassroomId = $assignedClass->id;
            }
        }
        if (!$defaultClassroomId && $classrooms->isNotEmpty()) {
            $defaultClassroomId = $classrooms->first()->id;
        }

        $selectedClassroomId = $request->get('classroom_id', $defaultClassroomId);
        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);

        // Period filter (default: current year-month 'YYYY-MM')
        $currentPeriod = Carbon::now()->format('Y-m');
        $selectedPeriod = $request->get('period', $currentPeriod);

        // Examination date (defaults to today or period midpoint)
        $selectedDate = $request->get('date', Carbon::now()->format('Y-m-d'));

        // Active tab: 'inputDDTK', 'inputSDIDTK', 'rekap', 'grafik'
        $activeTab = $request->get('tab', 'inputDDTK');

        // Active academic year
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Students in the selected classroom
        $students = collect();
        if ($selectedClassroom) {
            $studentsQuery = Student::where('status', 'aktif');

            if ($selectedClassroom->sub_unit === 'DAYCARE') {
                $studentsQuery->where(function ($q) use ($selectedClassroom) {
                    $q->where('classroom_id', $selectedClassroom->id)
                      ->orWhere('daycare_classroom_id', $selectedClassroom->id);
                });
            } elseif ($selectedClassroom->sub_unit === 'TPQ') {
                $studentsQuery->where(function ($q) use ($selectedClassroom) {
                    $q->where('classroom_id', $selectedClassroom->id)
                      ->orWhere('tpq_classroom_id', $selectedClassroom->id);
                });
            } else {
                $studentsQuery->where('classroom_id', $selectedClassroom->id);
            }

            $students = $studentsQuery->orderBy('full_name', 'asc')->get();
        }

        // Existing records for the selected classroom and period
        $existingRecords = collect();
        if ($selectedClassroom && $students->isNotEmpty()) {
            $existingRecords = StudentDdtkRecord::where('classroom_id', $selectedClassroom->id)
                ->where('period', $selectedPeriod)
                ->get()
                ->keyBy('student_id');
        }

        // Summary Stats for the period
        $totalStudents = $students->count();
        $ddtkCount = 0;
        $sdidtkCount = 0;
        $sumHeight = 0;
        $sumWeight = 0;
        $countHeight = 0;
        $countWeight = 0;

        foreach ($students as $st) {
            $rec = $existingRecords[$st->id] ?? null;
            if ($rec) {
                if ($rec->height || $rec->weight || $rec->head_circumference || $rec->arm_circumference) {
                    $ddtkCount++;
                }
                if ($rec->visus_right || $rec->visus_left || $rec->kpsp_result || $rec->dental_health || $rec->blood_sugar !== null) {
                    $sdidtkCount++;
                }
                if ($rec->height > 0) {
                    $sumHeight += $rec->height;
                    $countHeight++;
                }
                if ($rec->weight > 0) {
                    $sumWeight += $rec->weight;
                    $countWeight++;
                }
            }
        }

        $avgHeight = $countHeight > 0 ? round($sumHeight / $countHeight, 1) : 0;
        $avgWeight = $countWeight > 0 ? round($sumWeight / $countWeight, 1) : 0;

        // Generate Available Periods in Chronological Order starting from Januari to Desember
        $selectedYear = Carbon::now()->year;
        if ($selectedPeriod && preg_match('/^(\d{4})/', $selectedPeriod, $matches)) {
            $selectedYear = (int) $matches[1];
        }

        $availablePeriods = [];
        for ($m = 1; $m <= 12; $m++) {
            $c = Carbon::create($selectedYear, $m, 1);
            $key = $c->format('Y-m');
            $availablePeriods[$key] = $c->translatedFormat('F Y');
        }

        // Historical Records for Growth Charts (Tab 4)
        $chartHistory = [];
        $distinctPeriods = [];
        if ($selectedClassroom) {
            $allHistory = StudentDdtkRecord::where('classroom_id', $selectedClassroom->id)
                ->orderBy('period', 'asc')
                ->get();

            $distinctPeriods = $allHistory->pluck('period')->unique()->values()->toArray();
            if (!in_array($selectedPeriod, $distinctPeriods)) {
                $distinctPeriods[] = $selectedPeriod;
                sort($distinctPeriods);
            }

            foreach ($distinctPeriods as $p) {
                $pRecords = $allHistory->where('period', $p);
                $avgH = $pRecords->where('height', '>', 0)->avg('height');
                $avgW = $pRecords->where('weight', '>', 0)->avg('weight');
                $avgLk = $pRecords->where('head_circumference', '>', 0)->avg('head_circumference');
                $avgLila = $pRecords->where('arm_circumference', '>', 0)->avg('arm_circumference');

                $monthName = Carbon::createFromFormat('Y-m', $p)->translatedFormat('M Y');

                $chartHistory[] = [
                    'period' => $p,
                    'label' => $monthName,
                    'avg_height' => $avgH ? round($avgH, 1) : null,
                    'avg_weight' => $avgW ? round($avgW, 1) : null,
                    'avg_head' => $avgLk ? round($avgLk, 1) : null,
                    'avg_arm' => $avgLila ? round($avgLila, 1) : null,
                    'tested_count' => $pRecords->count(),
                ];
            }
        }

        return view('admin.student-ddtk.index', compact(
            'classrooms',
            'selectedClassroom',
            'selectedClassroomId',
            'selectedPeriod',
            'selectedDate',
            'activeTab',
            'activeAcademicYear',
            'students',
            'existingRecords',
            'totalStudents',
            'ddtkCount',
            'sdidtkCount',
            'avgHeight',
            'avgWeight',
            'availablePeriods',
            'chartHistory',
            'distinctPeriods'
        ));
    }

    /**
     * Store or update Tab 1: Input DDTK (Inline Form).
     */
    public function storeDdtk(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'period' => 'required|string|max:20',
            'date' => 'required|date',
            'ddtk' => 'required|array',
        ]);

        $classroomId = $validated['classroom_id'];
        $period = $validated['period'];
        $date = $validated['date'];
        $ddtkData = $request->input('ddtk', []);

        $classroom = Classroom::find($classroomId);
        $academicYearId = $classroom?->academic_year_id ?? AcademicYear::where('is_active', true)->value('id');
        $userId = auth()->id();

        $savedCount = 0;

        DB::transaction(function () use ($ddtkData, $classroomId, $academicYearId, $period, $date, $userId, &$savedCount) {
            foreach ($ddtkData as $studentId => $item) {
                $height = isset($item['height']) && $item['height'] !== '' ? (float) $item['height'] : null;
                $weight = isset($item['weight']) && $item['weight'] !== '' ? (float) $item['weight'] : null;
                $headCirc = isset($item['head_circumference']) && $item['head_circumference'] !== '' ? (float) $item['head_circumference'] : null;
                $armCirc = isset($item['arm_circumference']) && $item['arm_circumference'] !== '' ? (float) $item['arm_circumference'] : null;

                // Only record if at least one measurement is entered
                if ($height !== null || $weight !== null || $headCirc !== null || $armCirc !== null) {
                    $record = StudentDdtkRecord::firstOrNew([
                        'student_id' => $studentId,
                        'period' => $period,
                    ]);

                    $record->classroom_id = $classroomId;
                    $record->academic_year_id = $academicYearId;
                    $record->date = $date;
                    $record->height = $height;
                    $record->weight = $weight;
                    $record->head_circumference = $headCirc;
                    $record->arm_circumference = $armCirc;
                    $record->recorded_by = $userId;
                    $record->save();

                    // Synchronize latest anthropometric data to Student master record
                    $student = Student::find($studentId);
                    if ($student) {
                        $updateData = [];
                        if ($height !== null) $updateData['height'] = $height;
                        if ($weight !== null) $updateData['weight'] = $weight;
                        if ($headCirc !== null) $updateData['head_circumference'] = $headCirc;
                        if ($armCirc !== null) $updateData['arm_circumference'] = $armCirc;
                        if (!empty($updateData)) {
                            $student->update($updateData);
                        }
                    }

                    $savedCount++;
                }
            }
        });

        $periodLabel = Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') : $period;
        $message = "Berhasil menyimpan data DDTK untuk {$savedCount} murid pada periode {$periodLabel}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'saved_count' => $savedCount,
            ]);
        }

        return redirect()->route('student-ddtk.index', [
            'classroom_id' => $classroomId,
            'period' => $period,
            'date' => $date,
            'tab' => 'inputDDTK',
        ])->with('success', $message);
    }

    /**
     * Store or update Tab 2: Input SDIDTK (Inline Form).
     */
    public function storeSdidtk(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'period' => 'required|string|max:20',
            'date' => 'required|date',
            'sdidtk' => 'required|array',
        ]);

        $classroomId = $validated['classroom_id'];
        $period = $validated['period'];
        $date = $validated['date'];
        $sdidtkData = $request->input('sdidtk', []);

        $classroom = Classroom::find($classroomId);
        $academicYearId = $classroom?->academic_year_id ?? AcademicYear::where('is_active', true)->value('id');
        $userId = auth()->id();

        $savedCount = 0;

        DB::transaction(function () use ($sdidtkData, $classroomId, $academicYearId, $period, $date, $userId, &$savedCount) {
            foreach ($sdidtkData as $studentId => $item) {
                $visusRight = isset($item['visus_right']) ? trim($item['visus_right']) : null;
                $visusLeft = isset($item['visus_left']) ? trim($item['visus_left']) : null;
                $kpsp = isset($item['kpsp_result']) ? trim($item['kpsp_result']) : null;
                $dental = isset($item['dental_health']) ? trim($item['dental_health']) : null;
                $bloodSugar = isset($item['blood_sugar']) && $item['blood_sugar'] !== '' ? (float) $item['blood_sugar'] : null;
                $hasLeprosy = !empty($item['has_leprosy']);
                $hasFrambesia = !empty($item['has_frambesia']);
                $notes = isset($item['notes']) ? trim($item['notes']) : null;

                // Check if any SDIDTK data is present
                $hasContent = $visusRight || $visusLeft || $kpsp || $dental || $bloodSugar !== null || $hasLeprosy || $hasFrambesia || $notes;

                if ($hasContent) {
                    $record = StudentDdtkRecord::firstOrNew([
                        'student_id' => $studentId,
                        'period' => $period,
                    ]);

                    $record->classroom_id = $classroomId;
                    $record->academic_year_id = $academicYearId;
                    $record->date = $date;
                    $record->visus_right = $visusRight ?: null;
                    $record->visus_left = $visusLeft ?: null;
                    $record->kpsp_result = $kpsp ?: null;
                    $record->dental_health = $dental ?: null;
                    $record->blood_sugar = $bloodSugar;
                    $record->has_leprosy = $hasLeprosy;
                    $record->has_frambesia = $hasFrambesia;
                    $record->notes = $notes ?: null;
                    $record->recorded_by = $userId;
                    $record->save();

                    $savedCount++;
                }
            }
        });

        $periodLabel = Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') : $period;
        $message = "Berhasil menyimpan data skrining SDIDTK untuk {$savedCount} murid pada periode {$periodLabel}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'saved_count' => $savedCount,
            ]);
        }

        return redirect()->route('student-ddtk.index', [
            'classroom_id' => $classroomId,
            'period' => $period,
            'date' => $date,
            'tab' => 'inputSDIDTK',
        ])->with('success', $message);
    }

    /**
     * Export Rekap Data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $period = $request->get('period', Carbon::now()->format('Y-m'));

        $classroom = Classroom::with(['homeroomTeacher', 'classLevel'])->findOrFail($classroomId);

        $studentsQuery = Student::where('status', 'aktif');
        if ($classroom->sub_unit === 'DAYCARE') {
            $studentsQuery->where(function ($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id)
                  ->orWhere('daycare_classroom_id', $classroom->id);
            });
        } elseif ($classroom->sub_unit === 'TPQ') {
            $studentsQuery->where(function ($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id)
                  ->orWhere('tpq_classroom_id', $classroom->id);
            });
        } else {
            $studentsQuery->where('classroom_id', $classroom->id);
        }
        $students = $studentsQuery->orderBy('full_name', 'asc')->get();

        $records = StudentDdtkRecord::where('classroom_id', $classroom->id)
            ->where('period', $period)
            ->get()
            ->keyBy('student_id');

        $periodLabel = Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') : $period;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap DDTK SDIDTK');

        // Document Title
        $sheet->setCellValue('A1', 'REKAPITULASI PEMERIKSAAN DDTK & SDIDTK MURID');
        $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'))));
        $sheet->setCellValue('A3', "Rombel: {$classroom->name} ({$classroom->sub_unit}) | Periode: {$periodLabel} | Tanggal Cetak: " . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9.5)->getColor()->setARGB('FF475569');

        // Table Headers (Multi-row header)
        // Row 5: Group Headers
        $sheet->setCellValue('A5', 'No');
        $sheet->mergeCells('A5:A6');
        $sheet->setCellValue('B5', 'NIS');
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('C5', 'Nama Murid');
        $sheet->mergeCells('C5:C6');
        $sheet->setCellValue('D5', 'L/P');
        $sheet->mergeCells('D5:D6');

        $sheet->setCellValue('E5', 'Pertumbuhan (DDTK)');
        $sheet->mergeCells('E5:H5');
        $sheet->setCellValue('E6', 'TB (cm)');
        $sheet->setCellValue('F6', 'BB (kg)');
        $sheet->setCellValue('G6', 'L.KA (cm)');
        $sheet->setCellValue('H6', 'L.LE (cm)');

        $sheet->setCellValue('I5', 'Visus Mata');
        $sheet->mergeCells('I5:J5');
        $sheet->setCellValue('I6', 'Kanan');
        $sheet->setCellValue('J6', 'Kiri');

        $sheet->setCellValue('K5', 'KPSP');
        $sheet->mergeCells('K5:K6');
        $sheet->setCellValue('L5', 'Gigi');
        $sheet->mergeCells('L5:L6');
        $sheet->setCellValue('M5', 'GDA (mg/dL)');
        $sheet->mergeCells('M5:M6');

        $sheet->setCellValue('N5', 'Gejala (Ya/Tidak)');
        $sheet->mergeCells('N5:O5');
        $sheet->setCellValue('N6', 'Kusta');
        $sheet->setCellValue('O6', 'Frambusia');

        $sheet->setCellValue('P5', 'Catatan');
        $sheet->mergeCells('P5:P6');

        $lastCol = 'P';
        $sheet->getStyle("A5:{$lastCol}6")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle("A5:{$lastCol}6")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF059669'); // Emerald
        $sheet->getStyle("A5:{$lastCol}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(5)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(20);

        $currentRow = 7;
        $no = 1;

        foreach ($students as $st) {
            $rec = $records[$st->id] ?? null;
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValueExplicit("B{$currentRow}", $st->nis ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currentRow}", $st->full_name);
            $sheet->setCellValue("D{$currentRow}", $st->gender === 'L' || $st->gender === 'Laki-laki' ? 'L' : 'P');

            $sheet->setCellValue("E{$currentRow}", $rec?->height ?? '-');
            $sheet->setCellValue("F{$currentRow}", $rec?->weight ?? '-');
            $sheet->setCellValue("G{$currentRow}", $rec?->head_circumference ?? '-');
            $sheet->setCellValue("H{$currentRow}", $rec?->arm_circumference ?? '-');

            $sheet->setCellValue("I{$currentRow}", $rec?->visus_right ?? '-');
            $sheet->setCellValue("J{$currentRow}", $rec?->visus_left ?? '-');
            $sheet->setCellValue("K{$currentRow}", $rec?->kpsp_result ?? '-');
            $sheet->setCellValue("L{$currentRow}", $rec?->dental_health ?? '-');
            $sheet->setCellValue("M{$currentRow}", $rec?->blood_sugar !== null ? $rec->blood_sugar : '-');
            $sheet->setCellValue("N{$currentRow}", $rec ? ($rec->has_leprosy ? 'YA' : 'TIDAK') : '-');
            $sheet->setCellValue("O{$currentRow}", $rec ? ($rec->has_frambesia ? 'YA' : 'TIDAK') : '-');
            $sheet->setCellValue("P{$currentRow}", $rec?->notes ?? '-');

            $sheet->getStyle("A{$currentRow}:B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:O{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Borders
        $sheet->getStyle("A5:{$lastCol}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        foreach (range(1, 16) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        // Signatures
        $signRow = $currentRow + 2;
        $sheet->setCellValue("C{$signRow}", "Mengetahui,\nKepala Sekolah");
        $city = setting('school_city', 'Malang');
        $sheet->setCellValue("M{$signRow}", "{$city}, " . \Carbon\Carbon::now()->translatedFormat('d F Y') . "\nWali Kelas / Petugas UKS");
        $sheet->getStyle("C{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $principalName = setting('headmaster_name', setting('school_principal_name', '( ................................ )'));
        $sheet->setCellValue("C" . ($signRow + 4), $principalName);
        $sheet->getStyle("C" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("C" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $teacherName = $classroom->homeroomTeacher?->name ?? '( ................................ )';
        $sheet->setCellValue("M" . ($signRow + 4), $teacherName);
        $sheet->getStyle("M" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("M" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $cleanClassName = preg_replace('/[^A-Za-z0-9_-]/', '_', $classroom->name);
        $filename = "Rekap_DDTK_SDIDTK_{$cleanClassName}_{$period}.xlsx";

        if ($request->filled('download_token')) {
            if (!headers_sent()) {
                @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
            }
        }

        $response = response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Printable view for Rekap Data.
     */
    public function print(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $period = $request->get('period', Carbon::now()->format('Y-m'));

        $classroom = Classroom::with(['homeroomTeacher', 'classLevel'])->findOrFail($classroomId);

        $studentsQuery = Student::where('status', 'aktif');
        if ($classroom->sub_unit === 'DAYCARE') {
            $studentsQuery->where(function ($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id)
                  ->orWhere('daycare_classroom_id', $classroom->id);
            });
        } elseif ($classroom->sub_unit === 'TPQ') {
            $studentsQuery->where(function ($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id)
                  ->orWhere('tpq_classroom_id', $classroom->id);
            });
        } else {
            $studentsQuery->where('classroom_id', $classroom->id);
        }
        $students = $studentsQuery->orderBy('full_name', 'asc')->get();

        $records = StudentDdtkRecord::where('classroom_id', $classroom->id)
            ->where('period', $period)
            ->get()
            ->keyBy('student_id');

        $periodLabel = Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') : $period;

        return view('admin.student-ddtk.print', compact(
            'classroom',
            'students',
            'period',
            'periodLabel',
            'records'
        ));
    }
}
