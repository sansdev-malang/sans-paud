<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\HomeroomAssignment;
use App\Models\Jenjang;
use App\Models\Student;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Barryvdh\DomPDF\Facade\Pdf;

class AcademicSummaryController extends Controller
{
    /**
     * Display the Data Akademik table matrix.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id', $jenjangs->first()?->id);
        $selectedClassLevelId = $request->get('class_level_id');
        $search = $request->get('search');

        $data = $this->getAcademicSummaryData($selectedYearId, $selectedJenjangId, $selectedClassLevelId, $search);

        $classLevels = ClassLevel::with('jenjang')
            ->when($selectedJenjangId, function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId);
            })
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('admin.academic-summary.index', array_merge($data, [
            'academicYears' => $academicYears,
            'activeAcademicYear' => $activeAcademicYear,
            'selectedYearId' => $selectedYearId,
            'selectedYear' => $selectedYear,
            'selectedJenjangId' => $selectedJenjangId,
            'selectedClassLevelId' => $selectedClassLevelId,
            'jenjangs' => $jenjangs,
            'classLevels' => $classLevels,
        ]));
    }

    /**
     * Printable view of Data Akademik.
     */
    public function print(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $data = $this->getAcademicSummaryData($selectedYearId, $request->get('jenjang_id'), $request->get('class_level_id'), $request->get('search'));
        $principalInfo = $this->getPrincipalInfo();

        return view('admin.academic-summary.print', array_merge($data, [
            'selectedYear' => $selectedYear,
            'headmasterName' => $principalInfo['headmasterName'],
            'headmasterNiy' => $principalInfo['headmasterNiy'],
        ]));
    }

    /**
     * Export Data Akademik to Excel.
     */
    public function exportExcel(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $data = $this->getAcademicSummaryData($selectedYearId, $request->get('jenjang_id'), $request->get('class_level_id'), $request->get('search'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Akademik');

        // Document Title
        $sheet->setCellValue('A1', 'DATA AKADEMIK PAUD ANAK SALEH');
        $sheet->setCellValue('A2', 'REKAPITULASI ROMBEL, JUMLAH MURID & WALI KELAS');
        $sheet->setCellValue('A3', 'Tahun Ajaran: ' . ($selectedYear ? $selectedYear->name : 'Semua') . ' | Tanggal Cetak: ' . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF475569');

        // Headers
        $headers = [
            'No',
            'Tahun Ajaran',
            'Jenjang',
            'Kelas',
            'Rombel',
            'Jumlah Murid',
            'Wali Kelas'
        ];

        $startRow = 5;
        foreach ($headers as $colIndex => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        $currentRow = $startRow + 1;
        $no = 1;

        foreach ($data['academicRows'] as $row) {
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $row['academic_year_name']);
            $sheet->setCellValue("C{$currentRow}", $row['jenjang_name']);
            $sheet->setCellValue("D{$currentRow}", $row['class_level_name']);
            $sheet->setCellValue("E{$currentRow}", $row['classroom_name']);
            $sheet->setCellValue("F{$currentRow}", $row['student_count']);
            $sheet->setCellValue("G{$currentRow}", $row['homeroom_teacher_name']);

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Summary Total Row
        $sheet->setCellValue("A{$currentRow}", 'TOTAL');
        $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
        $sheet->setCellValue("F{$currentRow}", $data['stats']['total_students']);
        $sheet->setCellValue("G{$currentRow}", $data['stats']['total_assigned_teachers'] . ' Wali Kelas');

        $totalRange = "A{$currentRow}:{$lastCol}{$currentRow}";
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$currentRow}:G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Apply borders
        $sheet->getStyle("A{$startRow}:{$lastCol}{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Data_Akademik_PAUD_' . date('Ymd_His') . '.xlsx';

        if ($request->filled('download_token')) {
            @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
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
     * Export Data Akademik to PDF with download_token sync.
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id');
        $selectedJenjang = $jenjangs->firstWhere('id', $selectedJenjangId);

        $data = $this->getAcademicSummaryData($selectedYearId, $selectedJenjangId, $request->get('class_level_id'), $request->get('search'));

        $filename = 'Data_Akademik_PAUD_' . date('Ymd_His') . '.pdf';

        if ($request->filled('download_token')) {
            @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $principalInfo = $this->getPrincipalInfo();

        $pdf = Pdf::loadView('admin.academic-summary.pdf', array_merge($data, [
            'selectedYear' => $selectedYear,
            'selectedJenjang' => $selectedJenjang,
            'headmasterName' => $principalInfo['headmasterName'],
            'headmasterNiy' => $principalInfo['headmasterNiy'],
        ]))->setPaper('a4', 'landscape');

        $response = $pdf->download($filename);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Retrieve Principal (Kepala Sekolah) information with NIY from system.
     */
    private function getPrincipalInfo(): array
    {
        $principalUser = User::where('role', 'kepala_sekolah')->first();
        $principalEmp = null;

        if ($principalUser) {
            if ($principalUser->employee_id) {
                $principalEmp = Employee::find($principalUser->employee_id);
            }
            if (!$principalEmp && $principalUser->email) {
                $principalEmp = Employee::where('email', $principalUser->email)->first();
            }
        }

        if (!$principalEmp) {
            $principalEmp = Employee::where(function ($q) {
                $q->where('position', 'like', '%Kepala Sekolah%')
                  ->orWhere('position', 'like', '%Kepsek%');
            })->first();
        }

        $headmasterName = $principalEmp?->name ?? $principalUser?->name ?? 'Dra. Hj. Mike Supraptiwi, S.Psi, M.Pd.';
        $headmasterNiy = $principalEmp?->niy ?? '-';

        return [
            'headmasterName' => $headmasterName,
            'headmasterNiy' => $headmasterNiy,
        ];
    }

    /**
     * Compute comprehensive relational data rows for Data Akademik matrix.
     */
    private function getAcademicSummaryData($academicYearId, $jenjangId = null, $classLevelId = null, $search = null): array
    {
        $tapel = AcademicYear::find($academicYearId) ?? AcademicYear::where('is_active', true)->first();
        $tapelName = $tapel ? $tapel->name : '-';

        // 1. Fetch classrooms
        $classroomsQuery = Classroom::with([
            'jenjang',
            'classLevel.jenjang',
            'academicYear'
        ])->where('is_active', true);

        if ($academicYearId && $academicYearId !== 'all') {
            $classroomsQuery->where('academic_year_id', $academicYearId);
        }

        if ($jenjangId && $jenjangId !== 'all') {
            $classroomsQuery->where(function ($q) use ($jenjangId) {
                $q->where('jenjang_id', $jenjangId)
                  ->orWhereHas('classLevel', function ($lq) use ($jenjangId) {
                      $lq->where('jenjang_id', $jenjangId);
                  });
            });
        }

        if ($classLevelId && $classLevelId !== 'all') {
            $classroomsQuery->where('class_level_id', $classLevelId);
        }

        if ($search) {
            $classroomsQuery->where('name', 'like', "%{$search}%");
        }

        $classrooms = $classroomsQuery->orderBy('jenjang_id', 'asc')
            ->orderBy('class_level_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // 2. Fetch Active Students in selected academic year
        $studentQuery = Student::where('status', 'aktif');
        if ($academicYearId && $academicYearId !== 'all') {
            $studentQuery->where('academic_year_id', $academicYearId);
        }
        $students = $studentQuery->get();

        // 3. Fetch Homeroom Assignments in selected academic year
        $assignmentQuery = HomeroomAssignment::with('teacher')->where('is_active', true);
        if ($academicYearId && $academicYearId !== 'all') {
            $assignmentQuery->where('academic_year_id', $academicYearId);
        }
        $assignments = $assignmentQuery->get();

        $academicRows = [];
        $totalStudents = 0;
        $assignedTeachersCount = 0;

        foreach ($classrooms as $c) {
            // Student headcount for this classroom
            $enrolledStudents = $students->filter(function ($s) use ($c) {
                return $s->classroom_id == $c->id || 
                       ($c->sub_unit === 'DAYCARE' && $s->daycare_classroom_id == $c->id) ||
                       ($c->sub_unit === 'TPQ' && $s->tpq_classroom_id == $c->id);
            });

            $studentCount = $enrolledStudents->count();
            $totalStudents += $studentCount;

            // Assigned Homeroom Teacher for this academic year & classroom
            $assigned = $assignments->firstWhere('classroom_id', $c->id);
            $teacher = $assigned?->teacher ?? $c->homeroomTeacher;
            if ($teacher) {
                $assignedTeachersCount++;
            }

            $jenjangName = $c->jenjang?->name ?? $c->classLevel?->jenjang?->name ?? '-';
            $classLevelName = $c->classLevel?->name ?? '-';

            $academicRows[] = [
                'classroom_id' => $c->id,
                'academic_year_name' => $c->academicYear?->name ?? $tapelName,
                'jenjang_name' => $jenjangName,
                'class_level_name' => $classLevelName,
                'classroom_name' => $c->name,
                'student_count' => $studentCount,
                'homeroom_teacher_name' => $teacher?->name ?? 'Belum Ditugaskan',
                'teacher_photo' => $teacher?->photo,
                'teacher_phone' => $teacher?->phone,
                'capacity' => $c->capacity ?: 20,
            ];
        }

        $totalClassrooms = count($classrooms);
        $avgStudents = $totalClassrooms > 0 ? round($totalStudents / $totalClassrooms, 1) : 0;

        return [
            'academicRows' => $academicRows,
            'stats' => [
                'total_classrooms' => $totalClassrooms,
                'total_students' => $totalStudents,
                'total_assigned_teachers' => $assignedTeachersCount,
                'avg_students' => $avgStudents,
            ]
        ];
    }
}
