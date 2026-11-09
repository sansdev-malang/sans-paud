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
     * Display the Rekapitulasi Rombel & Kesiswaan report matrix.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        if (!$selectedYearId || $selectedYearId === 'all') {
            $selectedYearId = $activeAcademicYear?->id;
        }
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id', 0); // 0 = Semua Jenjang
        $selectedClassLevelId = $request->get('class_level_id');
        $search = $request->get('search');

        $reportData = $this->calculateReportData($selectedYearId, $selectedJenjangId, $selectedClassLevelId, $search);

        $classLevels = ClassLevel::with('jenjang')
            ->when($selectedJenjangId && $selectedJenjangId != 0, function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId);
            })
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('admin.academic-summary.index', array_merge($reportData, [
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
     * Printable view of Rekapitulasi Rombel.
     */
    public function print(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $reportData = $this->calculateReportData($selectedYearId, $request->get('jenjang_id'), $request->get('class_level_id'), $request->get('search'));
        $principalInfo = $this->getPrincipalInfo();

        return view('admin.academic-summary.print', array_merge($reportData, [
            'selectedYear' => $selectedYear,
            'selectedYearId' => $selectedYearId,
            'headmasterName' => $principalInfo['headmasterName'],
            'headmasterNiy' => $principalInfo['headmasterNiy'],
        ]));
    }

    /**
     * Export Rekapitulasi Rombel to Excel.
     */
    public function exportExcel(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $reportData = $this->calculateReportData($selectedYearId, $request->get('jenjang_id'), $request->get('class_level_id'), $request->get('search'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekapitulasi Rombel');

        // Document Title
        $sheet->setCellValue('A1', 'REKAPITULASI ROMBEL & DISTRIBUSI MURID');
        $sheet->setCellValue('A2', 'PAUD TERPADU ANAK SALEH MALANG');
        $sheet->setCellValue('A3', 'Tahun Ajaran: ' . ($selectedYear ? $selectedYear->name : 'Semua') . ' | Tanggal Cetak: ' . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF475569');

        // Headers
        $headers = [
            'No',
            'Jenjang',
            'Tingkat Kelas',
            'Nama Kelompok',
            'Laki-Laki',
            'Perempuan',
            'Jumlah Murid',
            'Inklusi (PDBK)',
            'Wali Kelas',
            'Kontak Wali Kelas'
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

        foreach ($reportData['levelReports'] as $lvl) {
            foreach ($lvl['classrooms'] as $cr) {
                $sheet->setCellValue("A{$currentRow}", $no++);
                $sheet->setCellValue("B{$currentRow}", $cr['jenjang_name']);
                $sheet->setCellValue("C{$currentRow}", $cr['class_level_name']);
                $sheet->setCellValue("D{$currentRow}", $cr['name']);
                $sheet->setCellValue("E{$currentRow}", $cr['male']);
                $sheet->setCellValue("F{$currentRow}", $cr['female']);
                $sheet->setCellValue("G{$currentRow}", $cr['total']);
                $sheet->setCellValue("H{$currentRow}", $cr['pdbk'] > 0 ? $cr['pdbk'] : '-');
                $sheet->setCellValue("I{$currentRow}", $cr['homeroom_teacher']);
                $sheet->setCellValue("J{$currentRow}", $cr['homeroom_teacher_phone'] ?: '-');

                $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($no % 2 === 0) {
                    $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                }

                $currentRow++;
            }
        }

        // Unassigned students row if any
        if ($reportData['unassignedTotal'] > 0) {
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", '-');
            $sheet->setCellValue("C{$currentRow}", 'Belum Masuk Rombel');
            $sheet->setCellValue("D{$currentRow}", 'Belum Ditentukan');
            $sheet->setCellValue("E{$currentRow}", $reportData['unassignedMale']);
            $sheet->setCellValue("F{$currentRow}", $reportData['unassignedFemale']);
            $sheet->setCellValue("G{$currentRow}", $reportData['unassignedTotal']);
            $sheet->setCellValue("H{$currentRow}", $reportData['unassignedPdbk'] > 0 ? $reportData['unassignedPdbk'] : '-');
            $sheet->setCellValue("I{$currentRow}", '-');
            $sheet->setCellValue("J{$currentRow}", '-');

            $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
            $currentRow++;
        }

        // Summary Total Row
        $sheet->setCellValue("A{$currentRow}", 'TOTAL KESELURUHAN');
        $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
        $sheet->setCellValue("E{$currentRow}", $reportData['grandTotalMale']);
        $sheet->setCellValue("F{$currentRow}", $reportData['grandTotalFemale']);
        $sheet->setCellValue("G{$currentRow}", $reportData['grandTotalStudents']);
        $sheet->setCellValue("H{$currentRow}", $reportData['grandTotalPdbk']);
        $sheet->setCellValue("I{$currentRow}", $reportData['grandTotalClassrooms'] . ' Kelompok');
        $sheet->setCellValue("J{$currentRow}", "Rasio: {$reportData['malePercent']}% L / {$reportData['femalePercent']}% P");

        $totalRange = "A{$currentRow}:{$lastCol}{$currentRow}";
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Apply borders
        $sheet->getStyle("A{$startRow}:{$lastCol}{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        // Column A compact width
        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);

        foreach (range(2, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Rekapitulasi_Rombel_PAUD_' . date('Ymd_His') . '.xlsx';

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
     * Export Rekapitulasi Rombel to PDF with download_token sync.
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id', 0);
        $selectedJenjang = $selectedJenjangId != 0 ? $jenjangs->firstWhere('id', $selectedJenjangId) : null;

        $reportData = $this->calculateReportData($selectedYearId, $selectedJenjangId, $request->get('class_level_id'), $request->get('search'));

        $filename = 'Rekapitulasi_Rombel_PAUD_' . date('Ymd_His') . '.pdf';

        if ($request->filled('download_token')) {
            @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $principalInfo = $this->getPrincipalInfo();

        $pdf = Pdf::loadView('admin.academic-summary.print', array_merge($reportData, [
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
     * Calculate all report structures and aggregates for Rekapitulasi Rombel.
     */
    private function calculateReportData($academicYearId, $jenjangId = 0, $classLevelId = null, $search = null): array
    {
        // 1. Fetch Class Levels
        $levelsQuery = ClassLevel::with('jenjang')
            ->where('is_active', true)
            ->orderBy('order', 'asc');

        if ($jenjangId && $jenjangId != 0 && $jenjangId !== 'all') {
            $levelsQuery->where('jenjang_id', $jenjangId);
        }

        if ($classLevelId && $classLevelId !== 'all') {
            $levelsQuery->where('id', $classLevelId);
        }

        $classLevels = $levelsQuery->get();

        // 2. Fetch Classrooms for selected Academic Year
        $classroomsQuery = Classroom::with(['jenjang', 'classLevel.jenjang', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true);

        if ($academicYearId && $academicYearId !== 'all') {
            $classroomsQuery->where('academic_year_id', $academicYearId);
        }

        if ($jenjangId && $jenjangId != 0 && $jenjangId !== 'all') {
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

        $allClassrooms = $classroomsQuery->orderBy('name', 'asc')->get();

        // 3. Fetch Active Students in selected academic year
        $studentQuery = Student::where('status', 'aktif');
        if ($academicYearId && $academicYearId !== 'all') {
            $studentQuery->where('academic_year_id', $academicYearId);
        }
        $students = $studentQuery->get();

        // 4. Fetch Homeroom Assignments
        $assignmentQuery = HomeroomAssignment::with('teacher')->where('is_active', true);
        if ($academicYearId && $academicYearId !== 'all') {
            $assignmentQuery->where('academic_year_id', $academicYearId);
        }
        $assignments = $assignmentQuery->get();

        $levelReports = [];
        $grandTotalMale = 0;
        $grandTotalFemale = 0;
        $grandTotalStudents = 0;
        $grandTotalPdbk = 0;
        $grandTotalClassrooms = 0;

        foreach ($classLevels as $level) {
            $levelClassrooms = $allClassrooms->where('class_level_id', $level->id);
            if ($levelClassrooms->isEmpty() && ($search || $classLevelId)) {
                continue;
            }

            $classroomsData = [];
            $subMale = 0;
            $subFemale = 0;
            $subTotal = 0;
            $subPdbk = 0;

            foreach ($levelClassrooms as $cr) {
                // Students belonging to this classroom
                $crStudents = $students->filter(function ($s) use ($cr) {
                    return $s->classroom_id == $cr->id ||
                           ($cr->sub_unit === 'DAYCARE' && $s->daycare_classroom_id == $cr->id) ||
                           ($cr->sub_unit === 'TPQ' && $s->tpq_classroom_id == $cr->id);
                });

                $male = $crStudents->where('gender', 'L')->count();
                $female = $crStudents->where('gender', 'P')->count();
                $total = $crStudents->count();

                // PDBK / Inklusi check
                $pdbk = $crStudents->filter(function ($s) {
                    return in_array($s->special_needs, ['02) Ya', 'Ya']) || 
                           ($s->special_needs && !str_contains(strtolower($s->special_needs), 'tidak'));
                })->count();

                $assigned = $assignments->firstWhere('classroom_id', $cr->id);
                $teacher = $assigned?->teacher ?? $cr->homeroomTeacher;

                $jenjangName = $cr->jenjang?->name ?? $cr->classLevel?->jenjang?->name ?? '-';

                $classroomsData[] = [
                    'id' => $cr->id,
                    'name' => $cr->name,
                    'code' => $cr->code ?: '-',
                    'jenjang_name' => $jenjangName,
                    'class_level_name' => $level->name,
                    'academic_year_name' => $cr->academicYear?->name ?? '-',
                    'capacity' => $cr->capacity ?: 15,
                    'homeroom_teacher' => $teacher?->name ?: 'Belum Ditugaskan',
                    'homeroom_teacher_photo' => $teacher?->photo_url ?? ($teacher?->photo ? asset('storage/' . $teacher->photo) : null),
                    'homeroom_teacher_phone' => $teacher?->phone ?: ($teacher?->niy ?: null),
                    'male' => $male,
                    'female' => $female,
                    'total' => $total,
                    'pdbk' => $pdbk,
                ];

                $subMale += $male;
                $subFemale += $female;
                $subTotal += $total;
                $subPdbk += $pdbk;
                $grandTotalClassrooms++;
            }

            $levelReports[] = [
                'level' => $level,
                'classrooms' => $classroomsData,
                'subtotal_male' => $subMale,
                'subtotal_female' => $subFemale,
                'subtotal_students' => $subTotal,
                'subtotal_pdbk' => $subPdbk,
            ];

            $grandTotalMale += $subMale;
            $grandTotalFemale += $subFemale;
            $grandTotalStudents += $subTotal;
            $grandTotalPdbk += $subPdbk;
        }

        // 5. Murid Aktif yang belum memiliki Kelompok Belajar (Unassigned)
        $assignedStudentIds = [];
        foreach ($allClassrooms as $cr) {
            $enrolled = $students->filter(function ($s) use ($cr) {
                return $s->classroom_id == $cr->id ||
                       ($cr->sub_unit === 'DAYCARE' && $s->daycare_classroom_id == $cr->id) ||
                       ($cr->sub_unit === 'TPQ' && $s->tpq_classroom_id == $cr->id);
            })->pluck('id')->toArray();
            $assignedStudentIds = array_merge($assignedStudentIds, $enrolled);
        }
        $assignedStudentIds = array_unique($assignedStudentIds);

        $unassignedStudents = $students->whereNotIn('id', $assignedStudentIds);
        if ($jenjangId && $jenjangId != 0 && $jenjangId !== 'all') {
            $unassignedStudents = $unassignedStudents->where('jenjang_id', $jenjangId);
        }

        $unassignedMale = $unassignedStudents->where('gender', 'L')->count();
        $unassignedFemale = $unassignedStudents->where('gender', 'P')->count();
        $unassignedTotal = $unassignedStudents->count();
        $unassignedPdbk = $unassignedStudents->filter(function ($s) {
            return in_array($s->special_needs, ['02) Ya', 'Ya']) || 
                   ($s->special_needs && !str_contains(strtolower($s->special_needs), 'tidak'));
        })->count();

        $grandTotalMale += $unassignedMale;
        $grandTotalFemale += $unassignedFemale;
        $grandTotalStudents += $unassignedTotal;
        $grandTotalPdbk += $unassignedPdbk;

        $malePercent = $grandTotalStudents > 0 ? round(($grandTotalMale / $grandTotalStudents) * 100, 1) : 0;
        $femalePercent = $grandTotalStudents > 0 ? round(($grandTotalFemale / $grandTotalStudents) * 100, 1) : 0;
        $pdbkPercent = $grandTotalStudents > 0 ? round(($grandTotalPdbk / $grandTotalStudents) * 100, 1) : 0;

        return [
            'levelReports' => $levelReports,
            'unassignedStudents' => $unassignedStudents,
            'unassignedMale' => $unassignedMale,
            'unassignedFemale' => $unassignedFemale,
            'unassignedTotal' => $unassignedTotal,
            'unassignedPdbk' => $unassignedPdbk,
            'grandTotalMale' => $grandTotalMale,
            'grandTotalFemale' => $grandTotalFemale,
            'grandTotalStudents' => $grandTotalStudents,
            'grandTotalPdbk' => $grandTotalPdbk,
            'grandTotalClassrooms' => $grandTotalClassrooms,
            'malePercent' => $malePercent,
            'femalePercent' => $femalePercent,
            'pdbkPercent' => $pdbkPercent,
        ];
    }
}
