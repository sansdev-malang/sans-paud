<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomJournal;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ClassroomJournalController extends Controller
{
    /**
     * Display Classroom Journals list with month & classroom filter.
     */
    public function index(Request $request)
    {
        // Academic Years List & Selection
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAcademicYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedAcademicYearId) ?? $activeAcademicYear;

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
            ->withCount(['students' => function ($q) use ($selectedAcademicYearId) {
                $q->where('status', 'aktif');
                if ($selectedAcademicYearId) {
                    $q->where('academic_year_id', $selectedAcademicYearId);
                }
            }])
            ->where('is_active', true)
            ->when($selectedAcademicYearId, fn($q) => $q->where('academic_year_id', $selectedAcademicYearId))
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

        // Filter Month & Calculate Calendar Year based on Academic Year
        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        
        $parts = explode('/', $selectedAcademicYear?->name ?? '');
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)Carbon::now()->year;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $calculatedYear = ($selectedMonth >= 7) ? $startYear : $endYear;
        $selectedYear = (int) $request->get('year', $calculatedYear);

        // Teachers list for dropdown assignment
        $teachers = Employee::whereIn('status', ['Active', 'aktif', 'active', 'Aktif'])
            ->orderBy('name', 'asc')
            ->get();

        // Fetch Journals
        $journals = collect();
        if ($selectedClassroom) {
            $journals = ClassroomJournal::with(['teacher', 'classroom', 'recordedBy'])
                ->where('classroom_id', $selectedClassroom->id)
                ->where(function($q) use ($selectedAcademicYearId, $selectedYear, $selectedMonth) {
                    $q->where(function($q1) use ($selectedAcademicYearId, $selectedMonth) {
                        if ($selectedAcademicYearId) {
                            $q1->where('academic_year_id', $selectedAcademicYearId)
                               ->whereMonth('date', $selectedMonth);
                        }
                    })->orWhere(function($q2) use ($selectedYear, $selectedMonth) {
                        $q2->whereYear('date', $selectedYear)
                           ->whereMonth('date', $selectedMonth);
                    });
                })
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();
        }

        // Summary Stats
        $totalEntries = $journals->count();
        $distinctDays = $journals->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->count();
        $involvedTeachers = $journals->pluck('teacher_id')->filter()->unique()->count();

        // Dynamic School & Principal Information
        $principal = Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $principalName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $principalNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');

        // Indonesian Month Names
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('admin.classroom-journals.index', compact(
            'classrooms',
            'academicYears',
            'activeAcademicYear',
            'selectedAcademicYear',
            'selectedAcademicYearId',
            'selectedClassroom',
            'selectedClassroomId',
            'selectedMonth',
            'selectedYear',
            'teachers',
            'journals',
            'totalEntries',
            'distinctDays',
            'involvedTeachers',
            'monthNames',
            'principalName',
            'principalNip',
            'schoolCity'
        ));
    }

    /**
     * Store a new Classroom Journal entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'teacher_id' => 'nullable|exists:employees,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'topic' => 'nullable|string|max:255',
            'class_condition' => 'required|string',
            'attachment_photo' => 'nullable|image|max:3072',
        ]);

        $classroom = Classroom::find($validated['classroom_id']);
        $academicYearId = $validated['academic_year_id'] ?? ($classroom?->academic_year_id ?? AcademicYear::where('is_active', true)->value('id'));

        // Handle Photo Upload
        $photoPath = null;
        if ($request->hasFile('attachment_photo')) {
            $photoPath = $request->file('attachment_photo')->store('classroom-journals', 'public');
        }

        // Default teacher to classroom homeroom if not specified
        $teacherId = ($validated['teacher_id'] ?? null) ?: ($classroom?->homeroom_teacher_id ?: auth()->user()->employee_id);

        ClassroomJournal::create([
            'classroom_id' => $validated['classroom_id'],
            'academic_year_id' => $academicYearId,
            'teacher_id' => $teacherId,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'topic' => $validated['topic'] ?? null,
            'class_condition' => $validated['class_condition'],
            'attachment_photo' => $photoPath,
            'recorded_by' => auth()->id(),
        ]);

        $dateFormatted = Carbon::parse($validated['date'])->translatedFormat('l, d F Y');
        $message = "Jurnal kelas tanggal {$dateFormatted} berhasil disimpan.";

        return redirect()->route('classroom-journals.index', [
            'classroom_id' => $validated['classroom_id'],
            'academic_year_id' => $academicYearId,
            'month' => Carbon::parse($validated['date'])->month,
        ])->with('success', $message);
    }

    /**
     * Update an existing Classroom Journal entry.
     */
    public function update(Request $request, $id)
    {
        $journal = ClassroomJournal::findOrFail($id);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'teacher_id' => 'nullable|exists:employees,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'topic' => 'nullable|string|max:255',
            'class_condition' => 'required|string',
            'attachment_photo' => 'nullable|image|max:3072',
        ]);

        // Handle Photo Upload / Replacement
        if ($request->hasFile('attachment_photo')) {
            if ($journal->attachment_photo && Storage::disk('public')->exists($journal->attachment_photo)) {
                Storage::disk('public')->delete($journal->attachment_photo);
            }
            $journal->attachment_photo = $request->file('attachment_photo')->store('classroom-journals', 'public');
        }

        $classroom = Classroom::find($validated['classroom_id']);
        $academicYearId = $validated['academic_year_id'] ?? ($journal->academic_year_id ?? ($classroom?->academic_year_id ?? AcademicYear::where('is_active', true)->value('id')));

        $journal->classroom_id = $validated['classroom_id'];
        $journal->academic_year_id = $academicYearId;
        $journal->teacher_id = ($validated['teacher_id'] ?? null) ?: $journal->teacher_id;
        $journal->date = $validated['date'];
        $journal->start_time = $validated['start_time'] ?? null;
        $journal->end_time = $validated['end_time'] ?? null;
        $journal->topic = $validated['topic'] ?? null;
        $journal->class_condition = $validated['class_condition'];
        $journal->save();

        $message = "Jurnal kelas berhasil diperbarui.";

        return redirect()->route('classroom-journals.index', [
            'classroom_id' => $journal->classroom_id,
            'academic_year_id' => $journal->academic_year_id,
            'month' => Carbon::parse($journal->date)->month,
        ])->with('success', $message);
    }

    /**
     * Delete a Classroom Journal entry.
     */
    public function destroy($id)
    {
        $journal = ClassroomJournal::findOrFail($id);
        $classroomId = $journal->classroom_id;
        $academicYearId = $journal->academic_year_id;
        $month = Carbon::parse($journal->date)->month;

        if ($journal->attachment_photo && Storage::disk('public')->exists($journal->attachment_photo)) {
            Storage::disk('public')->delete($journal->attachment_photo);
        }

        $journal->delete();

        return redirect()->route('classroom-journals.index', [
            'classroom_id' => $classroomId,
            'academic_year_id' => $academicYearId,
            'month' => $month,
        ])->with('success', 'Entri jurnal kelas berhasil dihapus.');
    }

    /**
     * Export Monthly Classroom Journals to Excel with download_token sync.
     */
    public function exportExcel(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $month = (int) $request->get('month', Carbon::now()->month);
        
        $academicYears = AcademicYear::all();
        $selectedYearId = $request->get('academic_year_id');
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedYearId) ?? $academicYears->firstWhere('is_active', true);
        
        $parts = explode('/', $selectedAcademicYear?->name ?? '');
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)Carbon::now()->year;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $calculatedYear = ($month >= 7) ? $startYear : $endYear;
        $year = (int) $request->get('year', $calculatedYear);

        $classroom = Classroom::with(['homeroomTeacher', 'classLevel'])->findOrFail($classroomId);

        $journals = ClassroomJournal::with(['teacher'])
            ->where('classroom_id', $classroom->id)
            ->where(function($q) use ($selectedYearId, $year, $month) {
                $q->where(function($q1) use ($selectedYearId, $month) {
                    if ($selectedYearId) {
                        $q1->where('academic_year_id', $selectedYearId)
                           ->whereMonth('date', $month);
                    }
                })->orWhere(function($q2) use ($year, $month) {
                    $q2->whereYear('date', $year)
                       ->whereMonth('date', $month);
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? $month;

        // Dynamic System Data
        $principal = Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $principalName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $principalNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');
        $appName = setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'));
        $taName = $selectedAcademicYear?->name ?? "{$year}";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jurnal ' . substr($monthName, 0, 3));

        // Header Title
        $sheet->setCellValue('A1', 'AGENDA / JURNAL PEMBELAJARAN KELAS');
        $sheet->setCellValue('A2', strtoupper($appName));
        $sheet->setCellValue('A3', "Kelas / Rombel: {$classroom->name} ({$classroom->sub_unit}) | T.A: {$taName} | Bulan: {$monthName} {$year} | Dicetak: " . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9.5)->getColor()->setARGB('FF475569');

        // Table Headers
        $headers = ['No', 'Hari & Tanggal', 'Waktu KBM', 'Guru / Pendidik', 'Topik / Tema Pembelajaran', 'Uraian Kegiatan & Kondisi Kelas'];
        $startRow = 5;

        foreach ($headers as $colIndex => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB'); // Blue 600
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        $currentRow = $startRow + 1;
        $no = 1;

        foreach ($journals as $j) {
            $dateFormatted = Carbon::parse($j->date)->translatedFormat('l, d/m/Y');
            $timeFormatted = $j->formatted_time;
            $teacherName = $j->teacher?->name ?? ($classroom->homeroomTeacher?->name ?? '-');

            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $dateFormatted);
            $sheet->setCellValue("C{$currentRow}", $timeFormatted);
            $sheet->setCellValue("D{$currentRow}", $teacherName);
            $sheet->setCellValue("E{$currentRow}", $j->topic ?: '-');
            $sheet->setCellValue("F{$currentRow}", $j->class_condition);

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setWrapText(true);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Borders
        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(28);
        $sheet->getColumnDimension('F')->setWidth(45);

        // Signatures
        $signRow = $currentRow + 2;
        $sheet->setCellValue("B{$signRow}", "Mengetahui,\nKepala Sekolah");
        $sheet->setCellValue("E{$signRow}", "{$schoolCity}, " . Carbon::now()->translatedFormat('d F Y') . "\nWali Kelas");
        $sheet->getStyle("B{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Principal Name & NIP
        $sheet->setCellValue("B" . ($signRow + 4), $principalName);
        $sheet->getStyle("B" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("B" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("B" . ($signRow + 5), "NIP/NIY: " . $principalNip);
        $sheet->getStyle("B" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("B" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Homeroom Teacher Name & NIP
        $teacherName = $classroom->homeroomTeacher?->name ?? '( ................................ )';
        $teacherNip = $classroom->homeroomTeacher?->nip ?? $classroom->homeroomTeacher?->niy ?? '-';
        $sheet->setCellValue("E" . ($signRow + 4), $teacherName);
        $sheet->getStyle("E" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("E" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("E" . ($signRow + 5), "NIP/NIY: " . $teacherNip);
        $sheet->getStyle("E" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("E" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $cleanClassName = preg_replace('/[^A-Za-z0-9_-]/', '_', $classroom->name);
        $cleanTaName = preg_replace('/[^A-Za-z0-9_-]/', '_', $taName);
        $filename = "Jurnal_Kelas_{$cleanClassName}_{$cleanTaName}_{$monthName}_{$year}.xlsx";

        // Download Token Handling for Toast & Progress Bar Synchronization
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
     * Download Monthly Classroom Journals directly as PDF.
     */
    public function downloadPdf(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $month = (int) $request->get('month', Carbon::now()->month);

        $academicYears = AcademicYear::all();
        $selectedYearId = $request->get('academic_year_id');
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedYearId) ?? $academicYears->firstWhere('is_active', true);

        $parts = explode('/', $selectedAcademicYear?->name ?? '');
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)Carbon::now()->year;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $calculatedYear = ($month >= 7) ? $startYear : $endYear;
        $year = (int) $request->get('year', $calculatedYear);

        $classroom = Classroom::with(['homeroomTeacher', 'classLevel'])->findOrFail($classroomId);

        $journals = ClassroomJournal::with(['teacher', 'recordedBy'])
            ->where('classroom_id', $classroom->id)
            ->where(function($q) use ($selectedYearId, $year, $month) {
                $q->where(function($q1) use ($selectedYearId, $month) {
                    if ($selectedYearId) {
                        $q1->where('academic_year_id', $selectedYearId)
                           ->whereMonth('date', $month);
                    }
                })->orWhere(function($q2) use ($year, $month) {
                    $q2->whereYear('date', $year)
                       ->whereMonth('date', $month);
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? $month;

        // Dynamic System Data
        $principal = Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $principalName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $principalNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');
        $appName = setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'));
        $taName = $selectedAcademicYear?->name ?? "{$year}";

        $cleanClassName = preg_replace('/[^A-Za-z0-9_-]/', '_', $classroom->name);
        $cleanTaName = preg_replace('/[^A-Za-z0-9_-]/', '_', $taName);
        $fileName = "Jurnal_Kelas_{$cleanClassName}_{$cleanTaName}_{$monthName}_{$year}.pdf";

        if ($request->filled('download_token')) {
            if (!headers_sent()) {
                @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
            }
        }

        $pdf = Pdf::loadView('admin.classroom-journals.pdf', compact(
            'classroom',
            'journals',
            'month',
            'year',
            'monthName',
            'selectedAcademicYear',
            'principalName',
            'principalNip',
            'schoolCity',
            'appName',
            'taName'
        ))->setPaper('a4', 'portrait');

        $response = $pdf->download($fileName);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Printable view of Classroom Journals.
     */
    public function print(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $month = (int) $request->get('month', Carbon::now()->month);

        $academicYears = AcademicYear::all();
        $selectedYearId = $request->get('academic_year_id');
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedYearId) ?? $academicYears->firstWhere('is_active', true);

        $parts = explode('/', $selectedAcademicYear?->name ?? '');
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)Carbon::now()->year;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $calculatedYear = ($month >= 7) ? $startYear : $endYear;
        $year = (int) $request->get('year', $calculatedYear);

        $classroom = Classroom::with(['homeroomTeacher', 'classLevel'])->findOrFail($classroomId);

        $journals = ClassroomJournal::with(['teacher', 'recordedBy'])
            ->where('classroom_id', $classroom->id)
            ->where(function($q) use ($selectedYearId, $year, $month) {
                $q->where(function($q1) use ($selectedYearId, $month) {
                    if ($selectedYearId) {
                        $q1->where('academic_year_id', $selectedYearId)
                           ->whereMonth('date', $month);
                    }
                })->orWhere(function($q2) use ($year, $month) {
                    $q2->whereYear('date', $year)
                       ->whereMonth('date', $month);
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$month] ?? $month;

        // Dynamic System Data
        $principal = Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $principalName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $principalNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');
        $appName = setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'));
        $taName = $selectedAcademicYear?->name ?? "{$year}";

        return view('admin.classroom-journals.print', compact(
            'classroom',
            'journals',
            'month',
            'year',
            'monthName',
            'selectedAcademicYear',
            'principalName',
            'principalNip',
            'schoolCity',
            'appName',
            'taName'
        ));
    }
}

