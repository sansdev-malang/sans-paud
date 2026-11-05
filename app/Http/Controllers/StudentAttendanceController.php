<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentAttendanceController extends Controller
{
    /**
     * Display the Student Attendance dashboard (Daily input & Monthly recap).
     */
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

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
            ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->when(!$isAdminUser, fn($q) => $q->whereIn('id', !empty($assignedClassroomIds) ? $assignedClassroomIds : [-1]))
            ->orderBy('sub_unit', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Determine default classroom
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
        if (!$isAdminUser && !empty($assignedClassroomIds) && !in_array($selectedClassroomId, $assignedClassroomIds)) {
            $selectedClassroomId = $defaultClassroomId;
        }
        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);

        // Date selection
        $selectedDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        try {
            $carbonDate = Carbon::parse($selectedDate);
        } catch (\Exception $e) {
            $carbonDate = Carbon::today();
            $selectedDate = $carbonDate->format('Y-m-d');
        }

        // Active tab: 'daily' or 'monthly'
        $activeTab = $request->get('tab', 'daily');

        // Month & Year for recap
        $selectedMonth = (int) $request->get('month', $carbonDate->month);
        $selectedYear = (int) $request->get('year', $carbonDate->year);

        // Get active academic year
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

        // --- 1. DATA TAB INPUT HARIAN ---
        $existingDailyAttendances = collect();
        $dailyStats = [
            'total' => $students->count(),
            'h' => 0,
            's' => 0,
            'i' => 0,
            'a' => 0,
            'recorded' => 0,
            'unrecorded' => $students->count(),
        ];

        if ($selectedClassroom && $students->isNotEmpty()) {
            $existingDailyAttendances = StudentAttendance::where('classroom_id', $selectedClassroom->id)
                ->whereDate('date', $selectedDate)
                ->get()
                ->keyBy('student_id');

            foreach ($students as $st) {
                if (isset($existingDailyAttendances[$st->id])) {
                    $stStatus = $existingDailyAttendances[$st->id]->status;
                    if ($stStatus === 'H') $dailyStats['h']++;
                    elseif ($stStatus === 'S') $dailyStats['s']++;
                    elseif ($stStatus === 'I') $dailyStats['i']++;
                    elseif ($stStatus === 'A') $dailyStats['a']++;
                    $dailyStats['recorded']++;
                }
            }
            $dailyStats['unrecorded'] = max(0, $dailyStats['total'] - $dailyStats['recorded']);
        }

        // --- 2. DATA TAB REKAP BULANAN ---
        $monthlyRecap = [];
        $monthlyStats = [
            'total_students' => $students->count(),
            'effective_days' => 0,
            'total_h' => 0,
            'total_s' => 0,
            'total_i' => 0,
            'total_a' => 0,
            'avg_attendance_rate' => 0,
        ];

        if ($selectedClassroom && $students->isNotEmpty()) {
            $monthlyRecords = StudentAttendance::where('classroom_id', $selectedClassroom->id)
                ->whereYear('date', $selectedYear)
                ->whereMonth('date', $selectedMonth)
                ->get();

            // Count distinct effective days recorded
            $effectiveDays = $monthlyRecords->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->values();
            $monthlyStats['effective_days'] = $effectiveDays->count();

            $totalRatesSum = 0;

            foreach ($students as $st) {
                $stRecords = $monthlyRecords->where('student_id', $st->id);
                $cntH = $stRecords->where('status', 'H')->count();
                $cntS = $stRecords->where('status', 'S')->count();
                $cntI = $stRecords->where('status', 'I')->count();
                $cntA = $stRecords->where('status', 'A')->count();
                $totalPertemuan = $cntH + $cntS + $cntI + $cntA;

                $rate = $totalPertemuan > 0 ? round(($cntH / $totalPertemuan) * 100, 1) : 0;
                $totalRatesSum += $rate;

                $monthlyStats['total_h'] += $cntH;
                $monthlyStats['total_s'] += $cntS;
                $monthlyStats['total_i'] += $cntI;
                $monthlyStats['total_a'] += $cntA;

                $monthlyRecap[] = [
                    'student' => $st,
                    'h' => $cntH,
                    's' => $cntS,
                    'i' => $cntI,
                    'a' => $cntA,
                    'total' => $totalPertemuan,
                    'rate' => $rate,
                ];
            }

            if ($students->count() > 0) {
                $monthlyStats['avg_attendance_rate'] = round($totalRatesSum / $students->count(), 1);
            }
        }

        // Indonesian Month Names
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        // Available years (5 years window)
        $currentYear = (int) Carbon::now()->year;
        $yearsList = range($currentYear - 3, $currentYear + 2);

        return view('admin.student-attendances.index', compact(
            'classrooms',
            'selectedClassroom',
            'selectedClassroomId',
            'selectedDate',
            'carbonDate',
            'activeTab',
            'selectedMonth',
            'selectedYear',
            'activeAcademicYear',
            'students',
            'existingDailyAttendances',
            'dailyStats',
            'monthlyRecap',
            'monthlyStats',
            'monthNames',
            'yearsList'
        ));
    }

    /**
     * Store or update daily attendance inline form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'date' => 'required|date',
            'attendances' => 'nullable|array',
            'attendances.*.status' => 'nullable|in:H,S,I,A',
            'attendances.*.notes' => 'nullable|string|max:255',
        ]);

        $classroomId = $validated['classroom_id'];
        $date = Carbon::parse($validated['date'])->format('Y-m-d');
        $attendancesInput = $request->input('attendances', []);

        $classroom = Classroom::find($classroomId);
        $academicYearId = $classroom?->academic_year_id ?? AcademicYear::where('is_active', true)->value('id');
        $userId = auth()->id();

        $savedCount = 0;

        DB::transaction(function () use ($attendancesInput, $classroomId, $academicYearId, $date, $userId, &$savedCount) {
            foreach ($attendancesInput as $studentId => $item) {
                $status = $item['status'] ?? null;
                $notes = isset($item['notes']) ? trim($item['notes']) : null;

                if (!empty($status)) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'date' => $date,
                        ],
                        [
                            'classroom_id' => $classroomId,
                            'academic_year_id' => $academicYearId,
                            'status' => $status,
                            'notes' => $notes,
                            'recorded_by' => $userId,
                        ]
                    );
                    $savedCount++;
                } else {
                    // If status was cleared, remove the attendance record for this day
                    StudentAttendance::where('student_id', $studentId)
                        ->whereDate('date', $date)
                        ->delete();
                }
            }
        });

        $dateFormatted = Carbon::parse($date)->translatedFormat('l, d F Y');
        $message = "Berhasil menyimpan absensi {$savedCount} murid untuk tanggal {$dateFormatted}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'saved_count' => $savedCount,
            ]);
        }

        return redirect()->route('student-attendances.index', [
            'classroom_id' => $classroomId,
            'date' => $date,
            'tab' => 'daily',
        ])->with('success', $message);
    }

    /**
     * Export Monthly Recap to Excel (.xlsx).
     */
    public function exportMonthlyExcel(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);

        $classroom = Classroom::with(['homeroomTeacher', 'classLevel'])->findOrFail($classroomId);

        // Fetch students
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

        // Fetch monthly records
        $monthlyRecords = StudentAttendance::where('classroom_id', $classroom->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? $month;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Absensi ' . substr($monthName, 0, 3));

        // Header Title
        $sheet->setCellValue('A1', 'REKAPITULASI ABSENSI MURID BULANAN');
        $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'))));
        $sheet->setCellValue('A3', "Kelas / Rombel: {$classroom->name} ({$classroom->sub_unit}) | Periode: {$monthName} {$year} | Dicetak: " . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF475569');

        // Table Headers
        $headers = ['No', 'NIS', 'Nama Murid', 'Hadir (H)', 'Sakit (S)', 'Izin (I)', 'Alpa (A)', 'Total Pertemuan', '% Kehadiran'];
        $startRow = 5;

        foreach ($headers as $colIndex => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF10B981'); // Emerald green
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        $currentRow = $startRow + 1;
        $no = 1;
        $totalH = 0; $totalS = 0; $totalI = 0; $totalA = 0; $totalMeetings = 0;

        foreach ($students as $st) {
            $stRecords = $monthlyRecords->where('student_id', $st->id);
            $cntH = $stRecords->where('status', 'H')->count();
            $cntS = $stRecords->where('status', 'S')->count();
            $cntI = $stRecords->where('status', 'I')->count();
            $cntA = $stRecords->where('status', 'A')->count();
            $tot = $cntH + $cntS + $cntI + $cntA;
            $rate = $tot > 0 ? round(($cntH / $tot) * 100, 1) : 0;

            $totalH += $cntH;
            $totalS += $cntS;
            $totalI += $cntI;
            $totalA += $cntA;
            $totalMeetings += $tot;

            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValueExplicit("B{$currentRow}", $st->nis ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$currentRow}", $st->full_name);
            $sheet->setCellValue("D{$currentRow}", $cntH);
            $sheet->setCellValue("E{$currentRow}", $cntS);
            $sheet->setCellValue("F{$currentRow}", $cntI);
            $sheet->setCellValue("G{$currentRow}", $cntA);
            $sheet->setCellValue("H{$currentRow}", $tot);
            $sheet->setCellValue("I{$currentRow}", $rate . '%');

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Summary Total Row
        $sheet->setCellValue("A{$currentRow}", 'TOTAL KELAS');
        $sheet->mergeCells("A{$currentRow}:C{$currentRow}");
        $sheet->setCellValue("D{$currentRow}", $totalH);
        $sheet->setCellValue("E{$currentRow}", $totalS);
        $sheet->setCellValue("F{$currentRow}", $totalI);
        $sheet->setCellValue("G{$currentRow}", $totalA);
        $sheet->setCellValue("H{$currentRow}", $totalMeetings);
        $avgRate = $totalMeetings > 0 ? round(($totalH / $totalMeetings) * 100, 1) : 0;
        $sheet->setCellValue("I{$currentRow}", $avgRate . '%');

        $totalRange = "A{$currentRow}:{$lastCol}{$currentRow}";
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Borders
        $sheet->getStyle("A{$startRow}:{$lastCol}{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        // Teacher signature area
        $signRow = $currentRow + 3;
        $sheet->setCellValue("B{$signRow}", "Mengetahui,\nKepala Sekolah");
        $city = setting('school_city', 'Malang');
        $sheet->setCellValue("G{$signRow}", "{$city}, " . \Carbon\Carbon::now()->translatedFormat('d F Y') . "\nWali Kelas");
        $sheet->getStyle("B{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $principalName = setting('headmaster_name', setting('school_principal_name', '( ................................ )'));
        $sheet->setCellValue("B" . ($signRow + 4), $principalName);
        $sheet->getStyle("B" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("B" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $teacherName = $classroom->homeroomTeacher?->name ?? '( ................................ )';
        $sheet->setCellValue("G" . ($signRow + 4), $teacherName);
        $sheet->getStyle("G" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("G" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $cleanClassName = preg_replace('/[^A-Za-z0-9_-]/', '_', $classroom->name);
        $filename = "Rekap_Absensi_{$cleanClassName}_{$monthName}_{$year}.xlsx";

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
     * Printable Monthly Recap view.
     */
    public function printMonthly(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);

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

        $monthlyRecords = StudentAttendance::where('classroom_id', $classroom->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? $month;

        $monthlyRecap = [];
        $totalH = 0; $totalS = 0; $totalI = 0; $totalA = 0; $totalMeetings = 0;

        foreach ($students as $st) {
            $stRecords = $monthlyRecords->where('student_id', $st->id);
            $cntH = $stRecords->where('status', 'H')->count();
            $cntS = $stRecords->where('status', 'S')->count();
            $cntI = $stRecords->where('status', 'I')->count();
            $cntA = $stRecords->where('status', 'A')->count();
            $tot = $cntH + $cntS + $cntI + $cntA;
            $rate = $tot > 0 ? round(($cntH / $tot) * 100, 1) : 0;

            $totalH += $cntH;
            $totalS += $cntS;
            $totalI += $cntI;
            $totalA += $cntA;
            $totalMeetings += $tot;

            $monthlyRecap[] = [
                'student' => $st,
                'h' => $cntH,
                's' => $cntS,
                'i' => $cntI,
                'a' => $cntA,
                'total' => $tot,
                'rate' => $rate,
            ];
        }

        $summaryTotals = [
            'h' => $totalH,
            's' => $totalS,
            'i' => $totalI,
            'a' => $totalA,
            'meetings' => $totalMeetings,
            'avg_rate' => $totalMeetings > 0 ? round(($totalH / $totalMeetings) * 100, 1) : 0,
        ];

        return view('admin.student-attendances.print-monthly', compact(
            'classroom',
            'students',
            'month',
            'year',
            'monthName',
            'monthlyRecap',
            'summaryTotals'
        ));
    }

    /**
     * Download Excel Template for importing student attendance.
     */
    public function downloadTemplate(Request $request)
    {
        $classroomId = $request->get('classroom_id');
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Absensi');

        // Title & Instruction
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT ABSENSI MURID');
        $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH'))));
        
        $subTitle = "Petunjuk: Isi Status dengan H (Hadir), S (Sakit), I (Izin), atau A (Alpa). Format Tanggal: YYYY-MM-DD (contoh: {$date}).";
        if ($classroom) {
            $subTitle .= " | Rombel: {$classroom->name} ({$classroom->sub_unit})";
        }
        $sheet->setCellValue('A3', $subTitle);

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF475569');

        // Header Table
        $headers = ['No', 'NIS', 'Nama Murid', 'Rombel', 'Tanggal (YYYY-MM-DD)', 'Status (H/S/I/A)', 'Keterangan'];
        $headerRow = 5;

        foreach ($headers as $colIndex => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $h);
        }

        // Header Style (Emerald Theme)
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // emerald-600
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        // Pre-fill student rows
        $currentRow = 6;
        if ($students->isNotEmpty()) {
            foreach ($students as $idx => $st) {
                $sheet->setCellValue("A{$currentRow}", $idx + 1);
                $sheet->setCellValueExplicit("B{$currentRow}", $st->nis ?: '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$currentRow}", $st->full_name);
                $sheet->setCellValue("D{$currentRow}", $classroom ? $classroom->name : '');
                $sheet->setCellValueExplicit("E{$currentRow}", $date, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("F{$currentRow}", 'H');
                $sheet->setCellValue("G{$currentRow}", '');
                $currentRow++;
            }
        } else {
            // Provide 3 sample rows
            $sampleData = [
                ['NIS' => '12345', 'Nama' => 'Ahmad Fulan', 'Rombel' => 'Kelompok A1', 'Status' => 'H', 'Ket' => ''],
                ['NIS' => '12346', 'Nama' => 'Fatimah Zahra', 'Rombel' => 'Kelompok A1', 'Status' => 'S', 'Ket' => 'Demam flu'],
                ['NIS' => '12347', 'Nama' => 'Ali Zainal', 'Rombel' => 'Kelompok A1', 'Status' => 'I', 'Ket' => 'Acara keluarga'],
            ];

            foreach ($sampleData as $idx => $sample) {
                $sheet->setCellValue("A{$currentRow}", $idx + 1);
                $sheet->setCellValueExplicit("B{$currentRow}", $sample['NIS'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$currentRow}", $sample['Nama']);
                $sheet->setCellValue("D{$currentRow}", $sample['Rombel']);
                $sheet->setCellValueExplicit("E{$currentRow}", $date, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("F{$currentRow}", $sample['Status']);
                $sheet->setCellValue("G{$currentRow}", $sample['Ket']);
                $currentRow++;
            }
        }

        $lastRow = max(6, $currentRow - 1);

        // Borders & Alignments
        $sheet->getStyle("A6:G{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B6:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E6:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F6:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Auto-size columns
        foreach (range(1, 7) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $filename = 'Template_Absensi_Murid_' . ($classroom ? str_replace(' ', '_', $classroom->name) . '_' : '') . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import Student Attendance from Excel file (.xlsx, .xls, .csv).
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'fallback_date' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $defaultClassroomId = $request->input('classroom_id');
        $fallbackDate = $request->input('fallback_date', Carbon::today()->format('Y-m-d'));

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        // Find header row (must contain 'NIS' or 'Nama' or 'Status')
        $headerRowIndex = null;
        $colMap = [
            'nis' => null,
            'name' => null,
            'classroom' => null,
            'date' => null,
            'status' => null,
            'notes' => null,
        ];

        foreach ($rows as $rowIndex => $row) {
            $rowValues = array_map(fn($v) => strtolower(trim((string)$v)), $row);
            
            $hasStatus = false;
            $hasNameOrNis = false;

            foreach ($rowValues as $colKey => $val) {
                if (str_contains($val, 'nis')) {
                    $colMap['nis'] = $colKey;
                    $hasNameOrNis = true;
                } elseif (str_contains($val, 'nama')) {
                    $colMap['name'] = $colKey;
                    $hasNameOrNis = true;
                } elseif (str_contains($val, 'rombel') || str_contains($val, 'kelas')) {
                    $colMap['classroom'] = $colKey;
                } elseif (str_contains($val, 'tanggal') || str_contains($val, 'tgl') || str_contains($val, 'date')) {
                    $colMap['date'] = $colKey;
                } elseif (str_contains($val, 'status')) {
                    $colMap['status'] = $colKey;
                    $hasStatus = true;
                } elseif (str_contains($val, 'keterangan') || str_contains($val, 'catatan') || str_contains($val, 'note')) {
                    $colMap['notes'] = $colKey;
                }
            }

            if ($hasStatus && $hasNameOrNis) {
                $headerRowIndex = $rowIndex;
                break;
            }
        }

        // Fallback default column mapping if header text matching missed
        if (!$headerRowIndex) {
            $headerRowIndex = 5;
            $colMap = [
                'nis' => 'B',
                'name' => 'C',
                'classroom' => 'D',
                'date' => 'E',
                'status' => 'F',
                'notes' => 'G',
            ];
        }

        // Active Academic Year
        $activeYear = AcademicYear::where('is_active', true)->first();
        $academicYearId = $activeYear?->id;
        $userId = auth()->id();

        // Preload active students and classrooms for fast lookups
        $allStudents = Student::where('status', 'aktif')->get();
        $studentsByNis = $allStudents->whereNotNull('nis')->keyBy(fn($s) => trim((string)$s->nis));
        $studentsByName = $allStudents->keyBy(fn($s) => strtolower(trim((string)$s->full_name)));
        $allClassrooms = Classroom::where('is_active', true)->get();
        $classroomsByName = $allClassrooms->keyBy(fn($c) => strtolower(trim((string)$c->name)));

        $successCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex <= $headerRowIndex) {
                    continue;
                }

                $rawNis = isset($colMap['nis']) ? trim((string)($row[$colMap['nis']] ?? '')) : '';
                $rawName = isset($colMap['name']) ? trim((string)($row[$colMap['name']] ?? '')) : '';
                $rawClass = isset($colMap['classroom']) ? trim((string)($row[$colMap['classroom']] ?? '')) : '';
                $rawDate = isset($colMap['date']) ? trim((string)($row[$colMap['date']] ?? '')) : '';
                $rawStatus = isset($colMap['status']) ? strtoupper(trim((string)($row[$colMap['status']] ?? ''))) : '';
                $rawNotes = isset($colMap['notes']) ? trim((string)($row[$colMap['notes']] ?? '')) : '';

                // Skip completely empty rows
                if (empty($rawNis) && empty($rawName) && empty($rawStatus)) {
                    continue;
                }

                // Match Student
                $student = null;
                if (!empty($rawNis) && isset($studentsByNis[$rawNis])) {
                    $student = $studentsByNis[$rawNis];
                } elseif (!empty($rawName)) {
                    $lowerName = strtolower($rawName);
                    if (isset($studentsByName[$lowerName])) {
                        $student = $studentsByName[$lowerName];
                    } else {
                        // Fuzzy search by substring
                        $student = $allStudents->first(function ($s) use ($lowerName) {
                            return str_contains(strtolower($s->full_name), $lowerName) || str_contains($lowerName, strtolower($s->full_name));
                        });
                    }
                }

                if (!$student) {
                    $skippedCount++;
                    $errors[] = "Baris {$rowIndex}: Murid '{$rawName}' (NIS: {$rawNis}) tidak ditemukan.";
                    continue;
                }

                // Resolve Status (H, S, I, A)
                $status = null;
                if (in_array($rawStatus, ['H', 'HADIR', 'PRESENT'])) $status = 'H';
                elseif (in_array($rawStatus, ['S', 'SAKIT', 'SICK'])) $status = 'S';
                elseif (in_array($rawStatus, ['I', 'IZIN', 'IJIN', 'PERMIT'])) $status = 'I';
                elseif (in_array($rawStatus, ['A', 'ALPA', 'ALFA', 'ABSENT'])) $status = 'A';

                if (!$status) {
                    $skippedCount++;
                    $errors[] = "Baris {$rowIndex}: Status '{$rawStatus}' tidak valid (Gunakan H, S, I, atau A).";
                    continue;
                }

                // Resolve Date
                $parsedDate = null;
                if (!empty($rawDate)) {
                    try {
                        if (is_numeric($rawDate)) {
                            $parsedDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate))->format('Y-m-d');
                        } else {
                            $parsedDate = Carbon::parse($rawDate)->format('Y-m-d');
                        }
                    } catch (\Throwable $e) {
                        $parsedDate = $fallbackDate;
                    }
                } else {
                    $parsedDate = $fallbackDate;
                }

                // Resolve Classroom ID
                $classroomId = $defaultClassroomId;
                if (!empty($rawClass) && isset($classroomsByName[strtolower($rawClass)])) {
                    $classroomId = $classroomsByName[strtolower($rawClass)]->id;
                } elseif ($student->classroom_id) {
                    $classroomId = $student->classroom_id;
                } elseif ($student->daycare_classroom_id) {
                    $classroomId = $student->daycare_classroom_id;
                } elseif ($student->tpq_classroom_id) {
                    $classroomId = $student->tpq_classroom_id;
                }

                if (!$classroomId) {
                    $skippedCount++;
                    $errors[] = "Baris {$rowIndex}: Rombel untuk murid '{$student->full_name}' tidak dapat ditentukan.";
                    continue;
                }

                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => $parsedDate,
                    ],
                    [
                        'classroom_id' => $classroomId,
                        'academic_year_id' => $academicYearId,
                        'status' => $status,
                        'notes' => !empty($rawNotes) ? $rawNotes : null,
                        'recorded_by' => $userId,
                    ]
                );

                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses data import: ' . $e->getMessage());
        }

        $message = "Berhasil mengimpor {$successCount} data absensi murid.";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} baris dilewati karena data tidak lengkap / siswa tidak ditemukan).";
        }

        return redirect()->route('student-attendances.index', [
            'classroom_id' => $defaultClassroomId,
            'date' => $fallbackDate,
            'tab' => 'daily',
        ])->with('success', $message)->with('import_errors', array_slice($errors, 0, 10));
    }
}
