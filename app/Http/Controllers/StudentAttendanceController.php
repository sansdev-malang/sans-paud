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
     * Supports both Monthly Matrix (1..31 columns) and Daily/Multi-date List format.
     */
    public function downloadTemplate(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $format = $request->get('format', 'matrix'); // 'matrix' (default) or 'list'
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
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

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? $month;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($format === 'matrix') {
            // ==========================================
            // FORMAT MATRIKS BULANAN (TANGGAL 1 s/d 31)
            // ==========================================
            $sheet->setTitle('Template Matriks ' . substr($monthName, 0, 3));
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            // Title & Instruction
            $sheet->setCellValue('A1', 'TEMPLATE IMPORT ABSENSI MURID (BULANAN)');
            $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH'))));
            
            $subTitle = "Periode: {$monthName} {$year} | Format: Matriks Bulanan (Tanggal 1 s/d {$daysInMonth})";
            if ($classroom) {
                $subTitle .= " | Rombel: {$classroom->name} ({$classroom->sub_unit})";
            }
            $sheet->setCellValue('A3', $subTitle);
            $sheet->setCellValue('A4', 'Petunjuk: Isi sel tanggal dengan kode H (Hadir), S (Sakit), I (Izin), atau A (Alpa). Hari libur atau tanggal tanpa KBM cukup dikosongkan.');

            $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF1E293B');
            $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF475569');

            // Header Table Row 6
            $headerRow = 6;
            $sheet->setCellValue("A{$headerRow}", 'No');
            $sheet->setCellValue("B{$headerRow}", 'NIS');
            $sheet->setCellValue("C{$headerRow}", 'Nama Murid');
            $sheet->setCellValue("D{$headerRow}", 'Rombel');

            // Day columns (Col 5 / E onwards)
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $colLetter = Coordinate::stringFromColumnIndex(4 + $d);
                $sheet->setCellValue("{$colLetter}{$headerRow}", $d);
            }

            // Summary columns
            $colH = Coordinate::stringFromColumnIndex(5 + $daysInMonth);
            $colS = Coordinate::stringFromColumnIndex(6 + $daysInMonth);
            $colI = Coordinate::stringFromColumnIndex(7 + $daysInMonth);
            $colA = Coordinate::stringFromColumnIndex(8 + $daysInMonth);

            $sheet->setCellValue("{$colH}{$headerRow}", 'H');
            $sheet->setCellValue("{$colS}{$headerRow}", 'S');
            $sheet->setCellValue("{$colI}{$headerRow}", 'I');
            $sheet->setCellValue("{$colA}{$headerRow}", 'A');

            $lastColLetter = $colA;

            // Header Style
            $sheet->getStyle("A{$headerRow}:D{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);

            $sheet->getStyle("E{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);

            // Differentiate Sundays in header with rose tint
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayDate = Carbon::createFromDate($year, $month, $d);
                if ($dayDate->isSunday()) {
                    $cLetter = Coordinate::stringFromColumnIndex(4 + $d);
                    $sheet->getStyle("{$cLetter}{$headerRow}")->getFill()->getStartColor()->setARGB('FFE11D48');
                }
            }

            $sheet->getStyle("{$colH}{$headerRow}:{$colA}{$headerRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']],
            ]);

            $sheet->getRowDimension($headerRow)->setRowHeight(26);

            // Populate Student rows
            $currentRow = $headerRow + 1;
            if ($students->isNotEmpty()) {
                foreach ($students as $idx => $st) {
                    $sheet->setCellValue("A{$currentRow}", $idx + 1);
                    $sheet->setCellValueExplicit("B{$currentRow}", $st->nis ?: '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("C{$currentRow}", $st->full_name);
                    $sheet->setCellValue("D{$currentRow}", $classroom ? $classroom->name : '');

                    // Pre-fill 'H' on Monday-Saturday, empty on Sunday
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $colLetter = Coordinate::stringFromColumnIndex(4 + $d);
                        $dayDate = Carbon::createFromDate($year, $month, $d);
                        if ($dayDate->isSunday()) {
                            $sheet->setCellValue("{$colLetter}{$currentRow}", '');
                        } else {
                            $sheet->setCellValue("{$colLetter}{$currentRow}", 'H');
                        }
                    }

                    // Excel formulas for quick summary
                    $firstDayCol = 'E';
                    $lastDayCol = Coordinate::stringFromColumnIndex(4 + $daysInMonth);
                    $sheet->setCellValue("{$colH}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"H\")");
                    $sheet->setCellValue("{$colS}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"S\")");
                    $sheet->setCellValue("{$colI}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"I\")");
                    $sheet->setCellValue("{$colA}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"A\")");

                    $currentRow++;
                }
            } else {
                // 3 sample rows
                $sampleData = [
                    ['NIS' => '12345', 'Nama' => 'Ahmad Fulan', 'Rombel' => 'Kelompok A1'],
                    ['NIS' => '12346', 'Nama' => 'Fatimah Zahra', 'Rombel' => 'Kelompok A1'],
                    ['NIS' => '12347', 'Nama' => 'Ali Zainal', 'Rombel' => 'Kelompok A1'],
                ];
                foreach ($sampleData as $idx => $sample) {
                    $sheet->setCellValue("A{$currentRow}", $idx + 1);
                    $sheet->setCellValueExplicit("B{$currentRow}", $sample['NIS'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("C{$currentRow}", $sample['Nama']);
                    $sheet->setCellValue("D{$currentRow}", $sample['Rombel']);
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $colLetter = Coordinate::stringFromColumnIndex(4 + $d);
                        $dayDate = Carbon::createFromDate($year, $month, $d);
                        if (!$dayDate->isSunday()) {
                            $sheet->setCellValue("{$colLetter}{$currentRow}", 'H');
                        }
                    }
                    $firstDayCol = 'E';
                    $lastDayCol = Coordinate::stringFromColumnIndex(4 + $daysInMonth);
                    $sheet->setCellValue("{$colH}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"H\")");
                    $sheet->setCellValue("{$colS}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"S\")");
                    $sheet->setCellValue("{$colI}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"I\")");
                    $sheet->setCellValue("{$colA}{$currentRow}", "=COUNTIF({$firstDayCol}{$currentRow}:{$lastDayCol}{$currentRow}, \"A\")");
                    $currentRow++;
                }
            }

            $lastRow = max($headerRow + 1, $currentRow - 1);

            $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle("A7:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E7:{$lastColLetter}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Merge title cells
            $sheet->mergeCells("A1:{$lastColLetter}1");
            $sheet->mergeCells("A2:{$lastColLetter}2");
            $sheet->mergeCells("A3:{$lastColLetter}3");
            $sheet->mergeCells("A4:{$lastColLetter}4");

            $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);
            foreach (['B', 'C', 'D'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cLetter = Coordinate::stringFromColumnIndex(4 + $d);
                $sheet->getColumnDimension($cLetter)->setWidth(4.5);
            }
            foreach ([$colH, $colS, $colI, $colA] as $cSum) {
                $sheet->getColumnDimension($cSum)->setWidth(5.5);
            }

            $filename = 'Template_Absensi_Bulanan_' . ($classroom ? str_replace(' ', '_', $classroom->name) . '_' : '') . "{$monthName}_{$year}.xlsx";

        } else {
            // ==========================================
            // FORMAT DAFTAR BARIS / MULTI-TANGGAL
            // ==========================================
            $sheet->setTitle('Template Daftar Absensi');

            // Title & Instruction
            $sheet->setCellValue('A1', 'TEMPLATE IMPORT ABSENSI MURID (DAFTAR / MULTI-TANGGAL)');
            $sheet->setCellValue('A2', strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH'))));
            
            $subTitle = "Petunjuk: Isi Status dengan H (Hadir), S (Sakit), I (Izin), atau A (Alpa). Format Tanggal: YYYY-MM-DD. Satu file bisa berisi banyak baris & tanggal sekaligus.";
            if ($classroom) {
                $subTitle .= " | Rombel: {$classroom->name} ({$classroom->sub_unit})";
            }
            $sheet->setCellValue('A3', $subTitle);

            // Merge title cells across columns A to G
            $sheet->mergeCells('A1:G1');
            $sheet->mergeCells('A2:G2');
            $sheet->mergeCells('A3:G3');

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
                    'startColor' => ['rgb' => '059669'],
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                ],
            ]);
            $sheet->getRowDimension($headerRow)->setRowHeight(26);

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
                $sampleData = [
                    ['NIS' => '12345', 'Nama' => 'Ahmad Fulan', 'Rombel' => 'Kelompok A1', 'Tgl' => $date, 'Status' => 'H', 'Ket' => ''],
                    ['NIS' => '12346', 'Nama' => 'Fatimah Zahra', 'Rombel' => 'Kelompok A1', 'Tgl' => $date, 'Status' => 'S', 'Ket' => 'Demam flu'],
                    ['NIS' => '12347', 'Nama' => 'Ali Zainal', 'Rombel' => 'Kelompok A1', 'Tgl' => $date, 'Status' => 'I', 'Ket' => 'Acara keluarga'],
                ];

                foreach ($sampleData as $idx => $sample) {
                    $sheet->setCellValue("A{$currentRow}", $idx + 1);
                    $sheet->setCellValueExplicit("B{$currentRow}", $sample['NIS'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("C{$currentRow}", $sample['Nama']);
                    $sheet->setCellValue("D{$currentRow}", $sample['Rombel']);
                    $sheet->setCellValueExplicit("E{$currentRow}", $sample['Tgl'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("F{$currentRow}", $sample['Status']);
                    $sheet->setCellValue("G{$currentRow}", $sample['Ket']);
                    $currentRow++;
                }
            }

            $lastRow = max(6, $currentRow - 1);

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

            $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);
            foreach (range(2, 7) as $colIndex) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $filename = 'Template_Absensi_Daftar_' . ($classroom ? str_replace(' ', '_', $classroom->name) . '_' : '') . date('Ymd_His') . '.xlsx';
        }

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
     * Import Student Attendance from Excel file (.xlsx, .xls, .csv).
     * Automatically detects format:
     * 1. Monthly Matrix Format: Columns 1..31 (Imports whole month attendance at once!)
     * 2. Daily / Multi-Date List Format: Columns NIS, Nama, Tanggal, Status (Imports multiple dates per row)
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
            'fallback_date' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $defaultClassroomId = $request->input('classroom_id');
        $inputMonth = (int) $request->input('month');
        $inputYear = (int) $request->input('year');
        $fallbackDate = $request->input('fallback_date', Carbon::today()->format('Y-m-d'));

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
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

        // Preload active students and classrooms for fast lookups
        $allStudents = Student::where('status', 'aktif')->get();
        $studentsByNis = $allStudents->whereNotNull('nis')->keyBy(fn($s) => trim((string)$s->nis));
        $studentsByName = $allStudents->keyBy(fn($s) => strtolower(trim((string)$s->full_name)));
        $allClassrooms = Classroom::where('is_active', true)->get();
        $classroomsByName = $allClassrooms->keyBy(fn($c) => strtolower(trim((string)$c->name)));

        // Extract metadata text from rows 1 to 5 for Month & Year detection
        $metaText = '';
        for ($r = 1; $r <= 5; $r++) {
            if (isset($rows[$r])) {
                $metaText .= ' ' . implode(' ', array_filter($rows[$r]));
            }
        }

        $detectedYear = $inputYear;
        if (!$detectedYear && preg_match('/\b(20\d{2})\b/', $metaText, $ym)) {
            $detectedYear = (int)$ym[1];
        }
        $detectedYear = $detectedYear ?: Carbon::now()->year;

        $detectedMonth = $inputMonth;
        if (!$detectedMonth) {
            $indonesianMonths = [
                'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
                'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'jun' => 6,
                'jul' => 7, 'agu' => 8, 'ags' => 8, 'sep' => 9, 'okt' => 10, 'nov' => 11, 'des' => 12
            ];
            foreach ($indonesianMonths as $mName => $mNum) {
                if (stripos($metaText, $mName) !== false) {
                    $detectedMonth = $mNum;
                    break;
                }
            }
        }
        $detectedMonth = $detectedMonth ?: Carbon::now()->month;

        // Auto-detect Format: Scan rows 1..10 for Header Row
        $headerRowIndex = null;
        $isMatrixFormat = false;
        $matrixDayCols = []; // colKey => dayNumber
        $listColMap = [
            'nis' => null,
            'name' => null,
            'classroom' => null,
            'date' => null,
            'status' => null,
            'notes' => null,
        ];

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex > 10) break;

            // Check if row has multiple numeric day columns (e.g. 1..31)
            $dayCols = [];
            foreach ($row as $colKey => $cellVal) {
                $clean = trim((string)$cellVal);
                if (is_numeric($clean) && (int)$clean >= 1 && (int)$clean <= 31) {
                    $dayCols[$colKey] = (int)$clean;
                }
            }

            if (count($dayCols) >= 3) {
                $isMatrixFormat = true;
                $headerRowIndex = $rowIndex;
                $matrixDayCols = $dayCols;
                break;
            }

            // Check if row has list columns (Status + Name or NIS)
            $rowValues = array_map(fn($v) => strtolower(trim((string)$v)), $row);
            $hasStatus = false;
            $hasNameOrNis = false;

            foreach ($rowValues as $colKey => $val) {
                if (str_contains($val, 'nis')) {
                    $listColMap['nis'] = $colKey;
                    $hasNameOrNis = true;
                } elseif (str_contains($val, 'nama')) {
                    $listColMap['name'] = $colKey;
                    $hasNameOrNis = true;
                } elseif (str_contains($val, 'rombel') || str_contains($val, 'kelas')) {
                    $listColMap['classroom'] = $colKey;
                } elseif (str_contains($val, 'tanggal') || str_contains($val, 'tgl') || str_contains($val, 'date')) {
                    $listColMap['date'] = $colKey;
                } elseif (str_contains($val, 'status')) {
                    $listColMap['status'] = $colKey;
                    $hasStatus = true;
                } elseif (str_contains($val, 'keterangan') || str_contains($val, 'catatan') || str_contains($val, 'note')) {
                    $listColMap['notes'] = $colKey;
                }
            }

            if ($hasStatus && $hasNameOrNis) {
                $isMatrixFormat = false;
                $headerRowIndex = $rowIndex;
                break;
            }
        }

        // Helper to resolve student
        $resolveStudent = function($rawNis, $rawName) use ($studentsByNis, $studentsByName, $allStudents) {
            if (!empty($rawNis) && isset($studentsByNis[$rawNis])) {
                return $studentsByNis[$rawNis];
            }
            if (!empty($rawName)) {
                $lower = strtolower(trim($rawName));
                if (isset($studentsByName[$lower])) {
                    return $studentsByName[$lower];
                }
                return $allStudents->first(function ($s) use ($lower) {
                    return str_contains(strtolower($s->full_name), $lower) || str_contains($lower, strtolower($s->full_name));
                });
            }
            return null;
        };

        // Helper to resolve classroom
        $resolveClassroom = function($rawClass, $student) use ($classroomsByName, $defaultClassroomId) {
            if (!empty($rawClass) && isset($classroomsByName[strtolower(trim($rawClass))])) {
                return $classroomsByName[strtolower(trim($rawClass))]->id;
            }
            if ($defaultClassroomId) {
                return $defaultClassroomId;
            }
            if ($student) {
                return $student->classroom_id ?: ($student->daycare_classroom_id ?: $student->tpq_classroom_id);
            }
            return null;
        };

        // Helper to standardize status code
        $normalizeStatus = function($raw) {
            $raw = strtoupper(trim((string)$raw));
            if (in_array($raw, ['H', 'HADIR', 'PRESENT'])) return 'H';
            if (in_array($raw, ['S', 'SAKIT', 'SICK'])) return 'S';
            if (in_array($raw, ['I', 'IZIN', 'IJIN', 'PERMIT'])) return 'I';
            if (in_array($raw, ['A', 'ALPA', 'ALFA', 'ABSENT'])) return 'A';
            return null;
        };

        $successCount = 0;
        $skippedCount = 0;
        $processedStudents = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            if ($isMatrixFormat) {
                // ==========================================
                // 1. PROCESS MONTHLY MATRIX FORMAT
                // ==========================================
                // Identify NIS, Nama, Rombel columns in header row
                $headerRowValues = array_map(fn($v) => strtolower(trim((string)$v)), $rows[$headerRowIndex]);
                $nisCol = null; $nameCol = null; $classCol = null;
                foreach ($headerRowValues as $colKey => $val) {
                    if (str_contains($val, 'nis')) $nisCol = $colKey;
                    elseif (str_contains($val, 'nama')) $nameCol = $colKey;
                    elseif (str_contains($val, 'rombel') || str_contains($val, 'kelas')) $classCol = $colKey;
                }
                // Fallbacks if not explicitly found
                $nisCol = $nisCol ?: 'B';
                $nameCol = $nameCol ?: 'C';
                $classCol = $classCol ?: 'D';

                foreach ($rows as $rowIndex => $row) {
                    if ($rowIndex <= $headerRowIndex) continue;

                    $rawNis = isset($row[$nisCol]) ? trim((string)$row[$nisCol]) : '';
                    $rawName = isset($row[$nameCol]) ? trim((string)$row[$nameCol]) : '';
                    $rawClass = isset($row[$classCol]) ? trim((string)$row[$classCol]) : '';

                    if (empty($rawNis) && empty($rawName)) continue;

                    $student = $resolveStudent($rawNis, $rawName);
                    if (!$student) {
                        $skippedCount++;
                        $errors[] = "Baris {$rowIndex}: Murid '{$rawName}' (NIS: {$rawNis}) tidak ditemukan.";
                        continue;
                    }

                    $classroomId = $resolveClassroom($rawClass, $student);
                    if (!$classroomId) {
                        $skippedCount++;
                        $errors[] = "Baris {$rowIndex}: Rombel untuk murid '{$student->full_name}' tidak dapat ditentukan.";
                        continue;
                    }

                    $hasStudentAttendance = false;

                    // Loop through each day column in the month
                    foreach ($matrixDayCols as $colKey => $dayNum) {
                        $rawStatus = isset($row[$colKey]) ? trim((string)$row[$colKey]) : '';
                        if (empty($rawStatus) || $rawStatus === '-' || $rawStatus === '.') {
                            continue;
                        }

                        $status = $normalizeStatus($rawStatus);
                        if (!$status) {
                            continue;
                        }

                        if (!checkdate($detectedMonth, $dayNum, $detectedYear)) {
                            continue;
                        }

                        $targetDate = sprintf('%04d-%02d-%02d', $detectedYear, $detectedMonth, $dayNum);

                        StudentAttendance::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'date' => $targetDate,
                            ],
                            [
                                'classroom_id' => $classroomId,
                                'academic_year_id' => $academicYearId,
                                'status' => $status,
                                'notes' => null,
                                'recorded_by' => $userId,
                            ]
                        );

                        $successCount++;
                        $hasStudentAttendance = true;
                    }

                    if ($hasStudentAttendance) {
                        $processedStudents++;
                    }
                }

            } else {
                // ==========================================
                // 2. PROCESS DAILY / MULTI-DATE LIST FORMAT
                // ==========================================
                if (!$headerRowIndex) {
                    $headerRowIndex = 5;
                    $listColMap = [
                        'nis' => 'B',
                        'name' => 'C',
                        'classroom' => 'D',
                        'date' => 'E',
                        'status' => 'F',
                        'notes' => 'G',
                    ];
                }

                foreach ($rows as $rowIndex => $row) {
                    if ($rowIndex <= $headerRowIndex) continue;

                    $rawNis = isset($listColMap['nis']) ? trim((string)($row[$listColMap['nis']] ?? '')) : '';
                    $rawName = isset($listColMap['name']) ? trim((string)($row[$listColMap['name']] ?? '')) : '';
                    $rawClass = isset($listColMap['classroom']) ? trim((string)($row[$listColMap['classroom']] ?? '')) : '';
                    $rawDate = isset($listColMap['date']) ? trim((string)($row[$listColMap['date']] ?? '')) : '';
                    $rawStatus = isset($listColMap['status']) ? trim((string)($row[$listColMap['status']] ?? '')) : '';
                    $rawNotes = isset($listColMap['notes']) ? trim((string)($row[$listColMap['notes']] ?? '')) : '';

                    if (empty($rawNis) && empty($rawName) && empty($rawStatus)) continue;

                    $student = $resolveStudent($rawNis, $rawName);
                    if (!$student) {
                        $skippedCount++;
                        $errors[] = "Baris {$rowIndex}: Murid '{$rawName}' (NIS: {$rawNis}) tidak ditemukan.";
                        continue;
                    }

                    $status = $normalizeStatus($rawStatus);
                    if (!$status) {
                        $skippedCount++;
                        $errors[] = "Baris {$rowIndex}: Status '{$rawStatus}' tidak valid (Gunakan H, S, I, atau A).";
                        continue;
                    }

                    // Resolve Date for this row
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

                    $classroomId = $resolveClassroom($rawClass, $student);
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
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses data import: ' . $e->getMessage());
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $displayMonth = $monthNames[$detectedMonth] ?? $detectedMonth;

        if ($isMatrixFormat) {
            $message = "Berhasil mengimpor {$successCount} data absensi murid ({$processedStudents} siswa) untuk periode {$displayMonth} {$detectedYear}.";
        } else {
            $message = "Berhasil mengimpor {$successCount} data absensi murid dari file Excel.";
        }

        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} baris dilewati karena data tidak lengkap / siswa tidak ditemukan).";
        }

        return redirect()->route('student-attendances.index', [
            'classroom_id' => $defaultClassroomId,
            'month' => $detectedMonth,
            'year' => $detectedYear,
            'date' => $fallbackDate,
            'tab' => $isMatrixFormat ? 'monthly' : 'daily',
        ])->with('success', $message)->with('import_errors', array_slice($errors, 0, 10));
    }
}
