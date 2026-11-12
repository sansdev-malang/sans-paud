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
        // Active academic year
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $user = auth()->user();
        $isAdminUser = $user && (
            $user->hasRole('super_admin') || 
            $user->hasRole('admin_sd') || 
            $user->hasRole('admin_paud') || 
            $user->hasRole('admin_smp') || 
            $user->hasRole('kepala_sekolah') || 
            $user->hasRole('waka')
        );

        $assignedClassroomIds = [];
        if (!$isAdminUser && $user && $user->employee_id) {
            $fromAssignment = \App\Models\HomeroomAssignment::where('employee_id', $user->employee_id)
                ->where('is_active', true)
                ->pluck('classroom_id')
                ->toArray();
            $fromClassroom = Classroom::where('homeroom_teacher_id', $user->employee_id)->pluck('id')->toArray();
            $assignedClassroomIds = array_values(array_unique(array_filter(array_merge($fromAssignment, $fromClassroom))));
        }

        $classrooms = Classroom::with(['homeroomTeacher', 'classLevel'])
            ->where('is_active', true)
            ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id))
            ->when(!$isAdminUser, fn($q) => $q->whereIn('id', $assignedClassroomIds))
            ->orderBy('sub_unit', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Default Classroom
        $defaultClassroomId = null;
        if ($user && $user->employee_id && !empty($assignedClassroomIds)) {
            $assignedClass = $classrooms->firstWhere('id', $assignedClassroomIds[0]);
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

        // Students in the selected classroom
        $students = collect();
        if ($selectedClassroom) {
            $studentsQuery = Student::where('status', 'aktif')
                ->when($activeAcademicYear, fn($q) => $q->where('academic_year_id', $activeAcademicYear->id));

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

        // Existing records for the selected classroom and period (cross-classroom synced by student_id)
        $existingRecords = collect();
        if ($selectedClassroom && $students->isNotEmpty()) {
            $existingRecords = StudentDdtkRecord::whereIn('student_id', $students->pluck('id'))
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
        if ($selectedClassroom && $students->isNotEmpty()) {
            $allHistory = StudentDdtkRecord::whereIn('student_id', $students->pluck('id'))
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
            'date' => 'nullable|date',
            'ddtk' => 'required|array',
        ]);

        $classroomId = $validated['classroom_id'];
        $period = $validated['period'];
        $date = !empty($validated['date']) ? $validated['date'] : (Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->startOfMonth()->format('Y-m-d') : Carbon::today()->format('Y-m-d'));
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

        if ($savedCount === 0) {
            $message = "Tidak ada data pengukuran fisik DDTK yang diisi (kolom masih kosong). Silakan masukkan angka ukuran (TB/BB) terlebih dahulu.";
            $msgKey = 'warning';
        } else {
            $message = "Berhasil menyimpan data DDTK untuk {$savedCount} murid pada periode {$periodLabel}.";
            $msgKey = 'success';
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $savedCount > 0,
                'message' => $message,
                'saved_count' => $savedCount,
            ]);
        }

        return redirect()->route('student-ddtk.index', [
            'classroom_id' => $classroomId,
            'period' => $period,
            'tab' => 'inputDDTK',
        ])->with($msgKey, $message);
    }

    /**
     * Store or update Tab 2: Input SDIDTK (Inline Form).
     */
    public function storeSdidtk(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'period' => 'required|string|max:20',
            'date' => 'nullable|date',
            'sdidtk' => 'required|array',
        ]);

        $classroomId = $validated['classroom_id'];
        $period = $validated['period'];
        $date = !empty($validated['date']) ? $validated['date'] : (Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->startOfMonth()->format('Y-m-d') : Carbon::today()->format('Y-m-d'));
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

        if ($savedCount === 0) {
            $message = "Tidak ada data skrining SDIDTK yang diisi (semua isian masih kosong).";
            $msgKey = 'warning';
        } else {
            $message = "Berhasil menyimpan data skrining SDIDTK untuk {$savedCount} murid pada periode {$periodLabel}.";
            $msgKey = 'success';
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $savedCount > 0,
                'message' => $message,
                'saved_count' => $savedCount,
            ]);
        }

        return redirect()->route('student-ddtk.index', [
            'classroom_id' => $classroomId,
            'period' => $period,
            'tab' => 'inputSDIDTK',
        ])->with($msgKey, $message);
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

        $records = StudentDdtkRecord::whereIn('student_id', $students->pluck('id'))
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

        // Kolom A (No): Lebar tetap kompak (width: 6) agar tidak melar akibat teks judul di baris 1-3
        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);

        foreach (range(2, 16) as $colIndex) {
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

        $records = StudentDdtkRecord::whereIn('student_id', $students->pluck('id'))
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

    /**
     * Download Excel Template for DDTK / SDIDTK import.
     */
    public function downloadTemplate(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $period = $request->get('period', Carbon::now()->format('Y-m'));
        $type = $request->get('type', 'ddtk'); // 'ddtk', 'sdidtk', 'all'
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        $classroom = null;
        $students = collect();

        if ($classroomId) {
            $classroom = Classroom::find($classroomId);
            if ($classroom) {
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
            }
        }

        $periodLabel = Carbon::hasFormat($period, 'Y-m') ? Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') : $period;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'sdidtk') {
            $sheet->setTitle('Template SDIDTK');
            $sheet->setCellValue('A1', 'TEMPLATE IMPORT SKRINING SDIDTK');
            $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH'))));
            $sheet->setCellValue('A3', "Periode: {$periodLabel} ({$period}) | Rombel: " . ($classroom ? "{$classroom->name} ({$classroom->sub_unit})" : 'Semua Rombel'));
            $sheet->setCellValue('A4', 'Petunjuk: Kolom Periode: nama bulan & tahun (contoh: "Desember 2026"). Visus: 1/4 (normal), 1/3, - (usia < 3 thn). KPSP: Sesuai, Meragukan, Penyimpangan, ABK, dll. Gigi: Normal, Caries, Belum Timbul Gigi, Persitensi (gigi tumpuk), dll. GDA: angka mg/dL atau - (< 2 thn). Kusta/Frambusia: Ya/Tidak.');

            $headers = [
                'No', 'NIS', 'Nama Murid', 'Rombel', 'Periode (Bulan Tahun)',
                'Mata Kanan', 'Mata Kiri', 'KPSP', 'Kesehatan Gigi', 'Gula Darah / Hb',
                'Kusta (Ya/Tidak)', 'Frambusia (Ya/Tidak)', 'Catatan / Keterangan'
            ];
            $themeColor = '059669'; // Emerald
        } elseif ($type === 'all') {
            $sheet->setTitle('Template DDTK & SDIDTK');
            $sheet->setCellValue('A1', 'TEMPLATE IMPORT DDTK & SDIDTK (LENGKAP)');
            $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH'))));
            $sheet->setCellValue('A3', "Periode: {$periodLabel} ({$period}) | Rombel: " . ($classroom ? "{$classroom->name} ({$classroom->sub_unit})" : 'Semua Rombel'));
            $sheet->setCellValue('A4', 'Petunjuk: Kolom Periode bisa diisi nama bulan & tahun (contoh: "Desember 2026"). Anda bisa mengisi lintas beberapa bulan sekaligus dalam satu berkas. Kosongkan kolom yang tidak diuji.');

            $headers = [
                'No', 'NIS', 'Nama Murid', 'Rombel', 'Periode (Bulan Tahun)',
                'Tinggi Badan (cm)', 'Berat Badan (kg)', 'Lingkar Kepala (cm)', 'Lingkar Lengan (cm)',
                'Mata Kanan', 'Mata Kiri', 'KPSP', 'Kesehatan Gigi', 'Gula Darah / Hb',
                'Kusta (Ya/Tidak)', 'Frambusia (Ya/Tidak)', 'Catatan / Keterangan'
            ];
            $themeColor = '4F46E5'; // Indigo
        } else {
            // Default DDTK
            $sheet->setTitle('Template DDTK');
            $sheet->setCellValue('A1', 'TEMPLATE IMPORT ANTROPOMETRI DDTK');
            $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH'))));
            $sheet->setCellValue('A3', "Periode: {$periodLabel} ({$period}) | Rombel: " . ($classroom ? "{$classroom->name} ({$classroom->sub_unit})" : 'Semua Rombel'));
            $sheet->setCellValue('A4', 'Petunjuk: Kolom Periode: nama bulan & tahun (contoh: "Desember 2026"). Ukuran: gunakan angka desimal (contoh: 102,5 atau 16,3; format koma maupun titik didukung otomatis).');

            $headers = [
                'No', 'NIS', 'Nama Murid', 'Rombel', 'Periode (Bulan Tahun)',
                'Tinggi Badan (cm)', 'Berat Badan (kg)', 'Lingkar Kepala (cm)', 'Lingkar Lengan (cm)'
            ];
            $themeColor = '0284C7'; // Sky-600
        }

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF1E293B');
        $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF475569');

        $headerRow = 6;
        $totalCols = count($headers);
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        foreach ($headers as $colIdx => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $h);
        }

        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $themeColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        $currentRow = $headerRow + 1;
        if ($students->isNotEmpty()) {
            foreach ($students as $idx => $st) {
                $sheet->setCellValue("A{$currentRow}", $idx + 1);
                $sheet->setCellValueExplicit("B{$currentRow}", $st->nis ?: '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$currentRow}", $st->full_name);
                $sheet->setCellValue("D{$currentRow}", $classroom ? $classroom->name : '');
                $sheet->setCellValue("E{$currentRow}", $periodLabel);

                if ($type === 'ddtk') {
                    $sheet->setCellValue("F{$currentRow}", '');
                    $sheet->setCellValue("G{$currentRow}", '');
                    $sheet->setCellValue("H{$currentRow}", '');
                    $sheet->setCellValue("I{$currentRow}", '');
                } elseif ($type === 'sdidtk') {
                    $sheet->setCellValue("F{$currentRow}", '1/4');
                    $sheet->setCellValue("G{$currentRow}", '1/4');
                    $sheet->setCellValue("H{$currentRow}", 'Sesuai');
                    $sheet->setCellValue("I{$currentRow}", 'Normal');
                    $sheet->setCellValue("J{$currentRow}", '');
                    $sheet->setCellValue("K{$currentRow}", 'Tidak');
                    $sheet->setCellValue("L{$currentRow}", 'Tidak');
                    $sheet->setCellValue("M{$currentRow}", '');
                } elseif ($type === 'all') {
                    $sheet->setCellValue("F{$currentRow}", '');
                    $sheet->setCellValue("G{$currentRow}", '');
                    $sheet->setCellValue("H{$currentRow}", '');
                    $sheet->setCellValue("I{$currentRow}", '');
                    $sheet->setCellValue("J{$currentRow}", '1/4');
                    $sheet->setCellValue("K{$currentRow}", '1/4');
                    $sheet->setCellValue("L{$currentRow}", 'Sesuai');
                    $sheet->setCellValue("M{$currentRow}", 'Normal');
                    $sheet->setCellValue("N{$currentRow}", '');
                    $sheet->setCellValue("O{$currentRow}", 'Tidak');
                    $sheet->setCellValue("P{$currentRow}", 'Tidak');
                    $sheet->setCellValue("Q{$currentRow}", '');
                }

                $currentRow++;
            }
        } else {
            // 2 Sample rows
            $sampleNames = ['Ahmad Fulan', 'Fatimah Zahra'];
            foreach ($sampleNames as $idx => $sName) {
                $sheet->setCellValue("A{$currentRow}", $idx + 1);
                $sheet->setCellValueExplicit("B{$currentRow}", (string)(12345 + $idx), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$currentRow}", $sName);
                $sheet->setCellValue("D{$currentRow}", 'Kelompok A1');
                $sheet->setCellValue("E{$currentRow}", $periodLabel);
                if ($type === 'ddtk') {
                    $sheet->setCellValue("F{$currentRow}", 102.5);
                    $sheet->setCellValue("G{$currentRow}", 16.3);
                    $sheet->setCellValue("H{$currentRow}", 49.0);
                    $sheet->setCellValue("I{$currentRow}", 15.5);
                } elseif ($type === 'sdidtk') {
                    $sheet->setCellValue("F{$currentRow}", '1/4');
                    $sheet->setCellValue("G{$currentRow}", '1/4');
                    $sheet->setCellValue("H{$currentRow}", 'Sesuai');
                    $sheet->setCellValue("I{$currentRow}", 'Normal');
                    $sheet->setCellValue("J{$currentRow}", '');
                    $sheet->setCellValue("K{$currentRow}", 'Tidak');
                    $sheet->setCellValue("L{$currentRow}", 'Tidak');
                    $sheet->setCellValue("M{$currentRow}", '');
                } else {
                    $sheet->setCellValue("F{$currentRow}", 102.5);
                    $sheet->setCellValue("G{$currentRow}", 16.3);
                    $sheet->setCellValue("H{$currentRow}", 49.0);
                    $sheet->setCellValue("I{$currentRow}", 15.5);
                    $sheet->setCellValue("J{$currentRow}", '1/4');
                    $sheet->setCellValue("K{$currentRow}", '1/4');
                    $sheet->setCellValue("L{$currentRow}", 'Sesuai');
                    $sheet->setCellValue("M{$currentRow}", 'Normal');
                    $sheet->setCellValue("N{$currentRow}", '');
                    $sheet->setCellValue("O{$currentRow}", 'Tidak');
                    $sheet->setCellValue("P{$currentRow}", 'Tidak');
                    $sheet->setCellValue("Q{$currentRow}", '');
                }
                $currentRow++;
            }
        }

        $lastRow = max($headerRow + 1, $currentRow - 1);
        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A7:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E7:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Kolom A (No): Lebar tetap kompak (width: 6) agar tidak melar akibat teks judul & petunjuk panjang di baris 1-4
        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);

        // Kolom B dst: Auto-size menyesuaikan panjang data
        foreach (range(2, $totalCols) as $cIdx) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($cIdx))->setAutoSize(true);
        }

        $filename = 'Template_' . strtoupper($type) . '_' . ($classroom ? str_replace(' ', '_', $classroom->name) . '_' : '') . "{$period}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ]);
    }

    /**
     * Import DDTK / SDIDTK records from Excel file.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'period' => 'nullable|string|max:20',
            'date' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $defaultClassroomId = $request->input('classroom_id');
        $defaultPeriod = $request->input('period', Carbon::now()->format('Y-m'));
        $defaultDate = $request->input('date', Carbon::today()->format('Y-m-d'));

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        if (empty($rows)) {
            return back()->with('error', 'File Excel kosong atau tidak terbaca.');
        }

        // Active Academic Year
        $activeYear = AcademicYear::where('is_active', true)->first();
        $academicYearId = $activeYear?->id;
        $userId = auth()->id();

        // Preload active students and classrooms
        $allStudents = Student::where('status', 'aktif')->get();
        $studentsByNis = $allStudents->whereNotNull('nis')->keyBy(fn($s) => trim((string)$s->nis));
        $studentsByName = $allStudents->keyBy(fn($s) => strtolower(trim((string)$s->full_name)));
        $allClassrooms = Classroom::where('is_active', true)->get();
        $classroomsByName = $allClassrooms->keyBy(fn($c) => strtolower(trim((string)$c->name)));

        // Detect period from metadata rows 1..5 if available
        $metaText = '';
        for ($r = 1; $r <= 5; $r++) {
            if (isset($rows[$r])) {
                $metaText .= ' ' . implode(' ', array_filter($rows[$r]));
            }
        }
        $detectedPeriod = $defaultPeriod;
        if (preg_match('/\b(20\d{2}-\d{2})\b/', $metaText, $pm)) {
            $detectedPeriod = $pm[1];
        }

        // Find Header Row (rows 1..10)
        $headerRowIndex = null;
        $colMap = [];
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex > 10) break;
            $rowVals = array_map(fn($v) => strtolower(trim((string)$v)), $row);

            $hasStudent = false;
            $hasMetrics = false;

            foreach ($rowVals as $colKey => $val) {
                if (str_contains($val, 'nis')) { $colMap['nis'] = $colKey; $hasStudent = true; }
                elseif (str_contains($val, 'nama')) { $colMap['name'] = $colKey; $hasStudent = true; }
                elseif (str_contains($val, 'rombel') || str_contains($val, 'kelas')) { $colMap['classroom'] = $colKey; }
                elseif (str_contains($val, 'periode') || str_contains($val, 'bulan') || str_contains($val, 'period')) { $colMap['period'] = $colKey; }
                elseif (str_contains($val, 'tanggal') || str_contains($val, 'tgl') || str_contains($val, 'date')) { $colMap['date'] = $colKey; }
                elseif (str_contains($val, 'tinggi') || str_contains($val, 'tb') || str_contains($val, 'height')) { $colMap['height'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'berat') || str_contains($val, 'bb') || str_contains($val, 'weight')) { $colMap['weight'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'kepala') || str_contains($val, 'lk') || str_contains($val, 'head')) { $colMap['head_circumference'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'lengan') || str_contains($val, 'lila') || str_contains($val, 'arm')) { $colMap['arm_circumference'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'mata kanan') || str_contains($val, 'visus kanan') || str_contains($val, 'visus r')) { $colMap['visus_right'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'mata kiri') || str_contains($val, 'visus kiri') || str_contains($val, 'visus l')) { $colMap['visus_left'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'kpsp')) { $colMap['kpsp_result'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'gigi') || str_contains($val, 'dental')) { $colMap['dental_health'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'gula') || str_contains($val, 'blood_sugar') || str_contains($val, 'hb') || str_contains($val, 'gda')) { $colMap['blood_sugar'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'kusta') || str_contains($val, 'leprosy')) { $colMap['has_leprosy'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'frambusia') || str_contains($val, 'frambesia')) { $colMap['has_frambesia'] = $colKey; $hasMetrics = true; }
                elseif (str_contains($val, 'catatan') || str_contains($val, 'keterangan') || str_contains($val, 'notes')) { $colMap['notes'] = $colKey; }
            }

            if ($hasStudent && $hasMetrics) {
                $headerRowIndex = $rowIndex;
                break;
            }
        }

        if (!$headerRowIndex) {
            $headerRowIndex = 6;
        }

        $successCount = 0;
        $skippedCount = 0;
        $importedPeriods = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headerRowIndex) continue;

                $rawNis = isset($colMap['nis']) ? trim((string)($row[$colMap['nis']] ?? '')) : (isset($row['B']) ? trim((string)$row['B']) : '');
                $rawName = isset($colMap['name']) ? trim((string)($row[$colMap['name']] ?? '')) : (isset($row['C']) ? trim((string)$row['C']) : '');
                $rawClass = isset($colMap['classroom']) ? trim((string)($row[$colMap['classroom']] ?? '')) : (isset($row['D']) ? trim((string)$row['D']) : '');
                $rawPeriod = isset($colMap['period']) ? trim((string)($row[$colMap['period']] ?? '')) : (isset($row['E']) ? trim((string)$row['E']) : '');
                $rawDate = isset($colMap['date']) ? trim((string)($row[$colMap['date']] ?? '')) : '';

                if (empty($rawNis) && empty($rawName)) continue;

                // Match student
                $student = null;
                if (!empty($rawNis) && isset($studentsByNis[$rawNis])) {
                    $student = $studentsByNis[$rawNis];
                } elseif (!empty($rawName)) {
                    $lowerName = strtolower(trim($rawName));
                    if (isset($studentsByName[$lowerName])) {
                        $student = $studentsByName[$lowerName];
                    } else {
                        $student = $allStudents->first(fn($s) => str_contains(strtolower($s->full_name), $lowerName) || str_contains($lowerName, strtolower($s->full_name)));
                    }
                }

                if (!$student) {
                    $skippedCount++;
                    $errors[] = "Baris {$rowIndex}: Murid '{$rawName}' (NIS: {$rawNis}) tidak ditemukan.";
                    continue;
                }

                // Resolve classroom
                $classroomId = $defaultClassroomId;
                if (!empty($rawClass) && isset($classroomsByName[strtolower(trim($rawClass))])) {
                    $classroomId = $classroomsByName[strtolower(trim($rawClass))]->id;
                } elseif ($student->classroom_id) {
                    $classroomId = $student->classroom_id;
                } elseif ($student->daycare_classroom_id) {
                    $classroomId = $student->daycare_classroom_id;
                } elseif ($student->tpq_classroom_id) {
                    $classroomId = $student->tpq_classroom_id;
                }

                // Resolve row period (supports Indonesian month-year or standard YYYY-MM)
                $rowPeriod = $this->parsePeriodString($rawPeriod) ?? $detectedPeriod ?? $defaultPeriod;

                // Resolve date
                $parsedDate = $defaultDate;
                if (!empty($rawDate)) {
                    try {
                        if (is_numeric($rawDate)) {
                            $parsedDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate))->format('Y-m-d');
                        } else {
                            $parsedDate = Carbon::parse($rawDate)->format('Y-m-d');
                        }
                    } catch (\Throwable $e) {
                        $parsedDate = $defaultDate;
                    }
                }

                // Extract fields
                $parseNumeric = function($key) use ($row, $colMap) {
                    if (!isset($colMap[$key])) return null;
                    $val = str_replace(',', '.', trim((string)($row[$colMap[$key]] ?? '')));
                    return is_numeric($val) ? (float)$val : null;
                };

                $parseString = function($key) use ($row, $colMap) {
                    if (!isset($colMap[$key])) return null;
                    $val = trim((string)($row[$colMap[$key]] ?? ''));
                    return $val !== '' ? $val : null;
                };

                $parseBool = function($key) use ($row, $colMap) {
                    if (!isset($colMap[$key])) return false;
                    $val = strtolower(trim((string)($row[$colMap[$key]] ?? '')));
                    return in_array($val, ['ya', 'yes', '1', 'true', 'positif']);
                };

                $height = $parseNumeric('height');
                $weight = $parseNumeric('weight');
                $headCirc = $parseNumeric('head_circumference');
                $armCirc = $parseNumeric('arm_circumference');
                $visusRight = $parseString('visus_right');
                $visusLeft = $parseString('visus_left');
                $kpsp = $parseString('kpsp_result');
                $dental = $parseString('dental_health');
                $bloodSugar = $parseNumeric('blood_sugar');
                $hasLeprosy = $parseBool('has_leprosy');
                $hasFrambesia = $parseBool('has_frambesia');
                $notes = $parseString('notes');

                $hasData = ($height !== null || $weight !== null || $headCirc !== null || $armCirc !== null ||
                            $visusRight !== null || $visusLeft !== null || $kpsp !== null || $dental !== null ||
                            $bloodSugar !== null || $hasLeprosy || $hasFrambesia || $notes !== null);

                if (!$hasData) {
                    continue;
                }

                $record = StudentDdtkRecord::firstOrNew([
                    'student_id' => $student->id,
                    'period' => $rowPeriod,
                ]);

                $record->classroom_id = $classroomId;
                $record->academic_year_id = $academicYearId;
                $record->date = $parsedDate;

                if ($height !== null) $record->height = $height;
                if ($weight !== null) $record->weight = $weight;
                if ($headCirc !== null) $record->head_circumference = $headCirc;
                if ($armCirc !== null) $record->arm_circumference = $armCirc;

                if ($visusRight !== null) $record->visus_right = $visusRight;
                if ($visusLeft !== null) $record->visus_left = $visusLeft;
                if ($kpsp !== null) $record->kpsp_result = $kpsp;
                if ($dental !== null) $record->dental_health = $dental;
                if ($bloodSugar !== null) $record->blood_sugar = $bloodSugar;
                if ($hasLeprosy) $record->has_leprosy = true;
                if ($hasFrambesia) $record->has_frambesia = true;
                if ($notes !== null) $record->notes = $notes;

                $record->recorded_by = $userId;
                $record->save();

                // Sync latest measurements to Student model
                $studentUpdates = [];
                if ($height !== null) $studentUpdates['height'] = $height;
                if ($weight !== null) $studentUpdates['weight'] = $weight;
                if ($headCirc !== null) $studentUpdates['head_circumference'] = $headCirc;
                if ($armCirc !== null) $studentUpdates['arm_circumference'] = $armCirc;
                if (!empty($studentUpdates)) {
                    $student->update($studentUpdates);
                }

                $importedPeriods[$rowPeriod] = true;
                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses data import: ' . $e->getMessage());
        }

        $importedCount = count($importedPeriods);
        if ($importedCount > 1) {
            $periodList = collect(array_keys($importedPeriods))->sort()->map(function($p) {
                return Carbon::hasFormat($p, 'Y-m') ? Carbon::createFromFormat('Y-m', $p)->translatedFormat('F Y') : $p;
            })->implode(', ');
            $message = "Berhasil mengimpor {$successCount} data pemeriksaan DDTK/SDIDTK murid lintas {$importedCount} periode ({$periodList}).";
        } else {
            $singleP = !empty($importedPeriods) ? array_key_first($importedPeriods) : $detectedPeriod;
            $pLabel = Carbon::hasFormat($singleP, 'Y-m') ? Carbon::createFromFormat('Y-m', $singleP)->translatedFormat('F Y') : $singleP;
            $message = "Berhasil mengimpor {$successCount} data pemeriksaan DDTK/SDIDTK murid (Periode: {$pLabel}).";
        }

        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} baris dilewati karena siswa tidak ditemukan).";
        }

        return redirect()->route('student-ddtk.index', [
            'classroom_id' => $defaultClassroomId,
            'period' => (!empty($importedPeriods) && isset($importedPeriods[$defaultPeriod])) ? $defaultPeriod : (array_key_first($importedPeriods) ?? $detectedPeriod),
            'tab' => $request->input('target_tab', 'inputDDTK'),
        ])->with('success', $message)->with('import_errors', array_slice($errors, 0, 10));
    }

    /**
     * Parse period string from various formats (Indonesian month names, YYYY-MM, MM/YYYY, Excel serial date)
     * into standard 'YYYY-MM' format.
     */
    private function parsePeriodString(?string $val): ?string
    {
        if ($val === null) return null;
        $raw = trim((string)$val);
        if ($raw === '') return null;

        // Check Excel date serial number (> 10000)
        if (is_numeric($raw) && (float)$raw > 10000) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($raw))->format('Y-m');
            } catch (\Throwable $e) {}
        }

        $clean = strtolower($raw);

        // Format: YYYY-MM or YYYY/MM (e.g. 2026-12, 2026/12)
        if (preg_match('/^(20\d{2})[-\/](0?[1-9]|1[0-2])$/', $clean, $m)) {
            return sprintf('%04d-%02d', (int)$m[1], (int)$m[2]);
        }

        // Format: MM-YYYY or MM/YYYY (e.g. 12/2026, 12-2026)
        if (preg_match('/^(0?[1-9]|1[0-2])[-\/](20\d{2})$/', $clean, $m)) {
            return sprintf('%04d-%02d', (int)$m[2], (int)$m[1]);
        }

        // Indonesian & English month map
        $monthMap = [
            'januari' => '01', 'january' => '01', 'jan' => '01',
            'februari' => '02', 'pebruari' => '02', 'february' => '02', 'feb' => '02',
            'maret' => '03', 'march' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'mei' => '05', 'may' => '05',
            'juni' => '06', 'june' => '06', 'jun' => '06',
            'juli' => '07', 'july' => '07', 'jul' => '07',
            'agustus' => '08', 'august' => '08', 'ags' => '08', 'agu' => '08', 'agt' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'oktober' => '10', 'october' => '10', 'okt' => '10', 'oct' => '10',
            'november' => '11', 'nopember' => '11', 'nov' => '11', 'nop' => '11',
            'desember' => '12', 'december' => '12', 'des' => '12', 'dec' => '12',
        ];

        // Find 4 digit year 20xx
        if (preg_match('/\b(20\d{2})\b/', $clean, $yMatch)) {
            $year = $yMatch[1];
            foreach ($monthMap as $mName => $mNum) {
                if (preg_match('/\b' . preg_quote($mName, '/') . '\b/i', $clean)) {
                    return "{$year}-{$mNum}";
                }
            }
        }

        // Try general Carbon parse as fallback
        try {
            return Carbon::parse($raw)->format('Y-m');
        } catch (\Throwable $e) {}

        return null;
    }
}
