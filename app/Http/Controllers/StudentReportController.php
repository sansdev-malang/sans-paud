<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StudentReportController extends Controller
{
    /**
     * Display the Rekapitulasi Rombel & Kesiswaan dashboard.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $data = $this->getReportData($selectedYearId);

        return view('admin.reports.students.index', array_merge($data, [
            'academicYears' => $academicYears,
            'activeAcademicYear' => $activeAcademicYear,
            'selectedYearId' => $selectedYearId,
            'selectedYear' => $selectedYear,
        ]));
    }

    /**
     * Printable view of the Rekapitulasi report.
     */
    public function print(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $data = $this->getReportData($selectedYearId);

        return view('admin.reports.students.print', array_merge($data, [
            'selectedYear' => $selectedYear,
        ]));
    }

    /**
     * Export Rekapitulasi to Excel.
     */
    public function exportExcel(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $data = $this->getReportData($selectedYearId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekapitulasi Rombel');

        // Document Title
        $sheet->setCellValue('A1', 'REKAPITULASI ROMBONGAN BELAJAR & KESISWAAN');
        $sheet->setCellValue('A2', 'KB - TK - DAYCARE - TPQ ANAK SALEH MALANG');
        $sheet->setCellValue('A3', 'Tahun Ajaran: ' . ($selectedYear ? $selectedYear->name : 'Semua') . ' | Tanggal Cetak: ' . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF475569');

        // Table Header
        $headers = ['No', 'Sub-Unit', 'Jenjang', 'Nama Kelompok / Rombel', 'Wali Kelas / Pendidik', 'Kapasitas', 'Laki-laki (L)', 'Perempuan (P)', 'Total Murid', 'Sisa Kuota', '% Keterisian'];
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

        foreach ($data['classroomStats'] as $item) {
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $item['sub_unit']);
            $sheet->setCellValue("C{$currentRow}", $item['class_level_name']);
            $sheet->setCellValue("D{$currentRow}", $item['name']);
            $sheet->setCellValue("E{$currentRow}", $item['teacher_name']);
            $sheet->setCellValue("F{$currentRow}", $item['capacity']);
            $sheet->setCellValue("G{$currentRow}", $item['male_count']);
            $sheet->setCellValue("H{$currentRow}", $item['female_count']);
            $sheet->setCellValue("I{$currentRow}", $item['total_count']);
            $sheet->setCellValue("J{$currentRow}", $item['remaining_capacity']);
            $sheet->setCellValue("K{$currentRow}", $item['occupancy_rate'] . '%');

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$currentRow}:K{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Summary Total Row
        $sheet->setCellValue("A{$currentRow}", 'TOTAL');
        $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
        $sheet->setCellValue("F{$currentRow}", $data['stats']['total_capacity']);
        $sheet->setCellValue("G{$currentRow}", $data['stats']['male']);
        $sheet->setCellValue("H{$currentRow}", $data['stats']['female']);
        $sheet->setCellValue("I{$currentRow}", $data['stats']['total_active']);
        $sheet->setCellValue("J{$currentRow}", max(0, $data['stats']['total_capacity'] - $data['stats']['total_active']));
        $sheet->setCellValue("K{$currentRow}", $data['stats']['occupancy_pct'] . '%');

        $totalRange = "A{$currentRow}:{$lastCol}{$currentRow}";
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$currentRow}:K{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Border styling
        $sheet->getStyle("A{$startRow}:{$lastCol}{$currentRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Rekapitulasi_Rombel_PAUD_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Compute comprehensive statistics and classroom roster data.
     */
    private function getReportData($academicYearId): array
    {
        $studentQuery = Student::where('status', 'aktif');
        if ($academicYearId) {
            $studentQuery->where('academic_year_id', $academicYearId);
        }

        $activeStudents = (clone $studentQuery)->get();
        $totalActive = $activeStudents->count();
        $maleCount = $activeStudents->whereIn('gender', ['L', 'Laki-laki', 'Male'])->count();
        $femaleCount = $activeStudents->whereIn('gender', ['P', 'Perempuan', 'Female'])->count();

        // Sub-unit counts
        $countPg = $activeStudents->where('sub_unit', 'PG')->count();
        $countTk = $activeStudents->where('sub_unit', 'TK')->count();
        $countDaycare = $activeStudents->filter(fn($s) => $s->sub_unit === 'DAYCARE' || !empty($s->daycare_classroom_id))->count();
        $countTpq = $activeStudents->filter(fn($s) => $s->sub_unit === 'TPQ' || $s->is_tpq || !empty($s->tpq_classroom_id))->count();

        // Classrooms query
        $classroomsQuery = Classroom::with(['classLevel', 'homeroomTeacher', 'students' => function ($q) {
            $q->where('status', 'aktif');
        }])->where('is_active', true);

        if ($academicYearId) {
            $classroomsQuery->where('academic_year_id', $academicYearId);
        }

        $classrooms = $classroomsQuery->orderBy('sub_unit', 'asc')->orderBy('class_level_id', 'asc')->orderBy('name', 'asc')->get();

        $totalCapacity = $classrooms->sum('capacity');
        $occupancyPct = $totalCapacity > 0 ? round(($totalActive / $totalCapacity) * 100, 1) : 0;

        $classroomStats = [];
        foreach ($classrooms as $cls) {
            // Compute in-memory from $activeStudents collection without extra DB queries
            if ($cls->sub_unit === 'DAYCARE') {
                $stds = $activeStudents->filter(fn($s) => $s->classroom_id == $cls->id || $s->daycare_classroom_id == $cls->id);
            } elseif ($cls->sub_unit === 'TPQ') {
                $stds = $activeStudents->filter(fn($s) => $s->classroom_id == $cls->id || $s->tpq_classroom_id == $cls->id);
            } else {
                $stds = $activeStudents->filter(fn($s) => $s->classroom_id == $cls->id);
            }

            $m = $stds->whereIn('gender', ['L', 'Laki-laki', 'Male'])->count();
            $f = $stds->whereIn('gender', ['P', 'Perempuan', 'Female'])->count();
            $tot = $stds->count();
            $cap = $cls->capacity ?: 15;
            $rem = max(0, $cap - $tot);
            $rate = $cap > 0 ? round(($tot / $cap) * 100, 1) : 0;

            $classroomStats[] = [
                'id' => $cls->id,
                'name' => $cls->name,
                'sub_unit' => $cls->sub_unit,
                'class_level_name' => $cls->classLevel?->name ?? $cls->sub_unit,
                'teacher_name' => $cls->homeroomTeacher?->name ?? 'Belum Ditugaskan',
                'teacher_photo' => $cls->homeroomTeacher?->photo,
                'capacity' => $cap,
                'male_count' => $m,
                'female_count' => $f,
                'total_count' => $tot,
                'remaining_capacity' => $rem,
                'occupancy_rate' => $rate,
                'students' => $stds,
            ];
        }

        // Group by Sub-Unit for tabular breakdown
        $groupedBySubUnit = [
            'PG' => array_values(array_filter($classroomStats, fn($c) => $c['sub_unit'] === 'PG')),
            'TK' => array_values(array_filter($classroomStats, fn($c) => $c['sub_unit'] === 'TK')),
            'DAYCARE' => array_values(array_filter($classroomStats, fn($c) => $c['sub_unit'] === 'DAYCARE')),
            'TPQ' => array_values(array_filter($classroomStats, fn($c) => $c['sub_unit'] === 'TPQ')),
        ];

        // Age Breakdown
        $ageDistribution = [];
        foreach ($activeStudents as $s) {
            $age = $s->age;
            if ($age > 0) {
                $ageGroup = $age . ' Tahun';
                if (!isset($ageDistribution[$ageGroup])) {
                    $ageDistribution[$ageGroup] = ['male' => 0, 'female' => 0, 'total' => 0];
                }
                $isMale = in_array($s->gender, ['L', 'Laki-laki', 'Male']);
                if ($isMale) {
                    $ageDistribution[$ageGroup]['male']++;
                } else {
                    $ageDistribution[$ageGroup]['female']++;
                }
                $ageDistribution[$ageGroup]['total']++;
            }
        }
        ksort($ageDistribution);

        return [
            'stats' => [
                'total_active' => $totalActive,
                'male' => $maleCount,
                'female' => $femaleCount,
                'total_classrooms' => $classrooms->count(),
                'total_capacity' => $totalCapacity,
                'occupancy_pct' => $occupancyPct,
                'pg' => $countPg,
                'tk' => $countTk,
                'daycare' => $countDaycare,
                'tpq' => $countTpq,
            ],
            'classroomStats' => $classroomStats,
            'groupedBySubUnit' => $groupedBySubUnit,
            'ageDistribution' => $ageDistribution,
        ];
    }
}
