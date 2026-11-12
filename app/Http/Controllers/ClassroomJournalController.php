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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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
            ->where('is_active', true)
            ->when($selectedAcademicYearId, fn($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->when(!$isAdminUser, fn($q) => $q->whereIn('id', $assignedClassroomIds))
            ->orderBy('sub_unit', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Default Classroom: User request "saat pertama diakses filter yang terpilih itu 'Semua Kelas' jadi admin bisa langsung melihat semua datanya"
        $selectedClassroomId = $request->get('classroom_id');
        if ($selectedClassroomId === null) {
            if ($isAdminUser || empty($assignedClassroomIds) || count($assignedClassroomIds) > 1) {
                $selectedClassroomId = 'all';
            } else {
                $selectedClassroomId = $assignedClassroomIds[0] ?? 'all';
            }
        }

        $isAllClassrooms = ($selectedClassroomId === 'all' || empty($selectedClassroomId));
        $selectedClassroom = $isAllClassrooms ? null : $classrooms->firstWhere('id', $selectedClassroomId);

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

        // Fetch Journals Query
        $journalsQuery = ClassroomJournal::with(['teacher', 'classroom', 'recordedBy'])
            ->when(!$isAllClassrooms && $selectedClassroom, function($q) use ($selectedClassroom) {
                $q->where('classroom_id', $selectedClassroom->id);
            }, function($q) use ($classrooms) {
                $q->whereIn('classroom_id', $classrooms->pluck('id'));
            })
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
            ->orderBy('start_time', 'desc');

        // Summary Stats across the full filtered dataset (sebelum dipaginasi)
        $allFilteredJournals = (clone $journalsQuery)->get();
        $totalEntries = $allFilteredJournals->count();
        $distinctDays = $allFilteredJournals->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->unique()->count();
        $involvedTeachers = $allFilteredJournals->pluck('teacher_id')->filter()->unique()->count();

        // Pagination & Per-Page Selection
        $perPageParam = $request->get('per_page', '25');
        if ($perPageParam === 'all') {
            $journals = $allFilteredJournals;
        } else {
            $perPage = is_numeric($perPageParam) ? (int)$perPageParam : 25;
            $journals = $journalsQuery->paginate($perPage)->withQueryString();
        }

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
            'isAllClassrooms',
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
            'schoolCity',
            'perPageParam'
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
     * Download Excel template for importing Classroom Journals.
     */
    public function downloadTemplate(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $classroom = $classroomId ? Classroom::find($classroomId) : null;
        $className = $classroom ? $classroom->name : 'Semua Rombel';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Jurnal');
        $sheet->setShowGridLines(true);

        // Header Titles
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT JURNAL KELAS');
        $sheet->setCellValue('A2', 'PAUD TERPADU ANAK SALEH MALANG');
        $sheet->setCellValue('A3', 'Target Rombel: ' . $className . ' | Petunjuk: Kolom Rombel, Tanggal, Jam Mulai, Jam Selesai, dan Uraian wajib diisi. Topik opsional. Gunakan format tanggal YYYY-MM-DD.');

        // Merge Title
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setName('Arial')->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E3A8A'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setName('Arial')->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setName('Arial')->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

        // Table Header
        $headers = [
            'A5' => 'No',
            'B5' => 'Rombel / Kelas',
            'C5' => 'Tanggal (YYYY-MM-DD)',
            'D5' => 'Jam Mulai (HH:MM)',
            'E5' => 'Jam Selesai (HH:MM)',
            'F5' => 'Topik / Tema Pembelajaran',
            'G5' => 'Uraian Kegiatan & Kondisi Kelas'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0284C7']], // Sky-600
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF94A3B8']]]
        ];
        $sheet->getStyle('A5:G5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Sample Data Rows
        $sampleClass = $classroom ? $classroom->name : 'KB B1';
        $sampleData = [
            [1, $sampleClass, '2026-07-27', '07:30', '10:30', 'Pengenalan Motorik Halus', "Hari ini {$sampleClass} melaksanakan KBM sesuai rencana: latihan memegang krayon dengan benar, dilanjutkan bermain balok lego."],
            [2, $sampleClass, '2026-07-28', '07:30', '10:45', 'Karakter & Bermain Peran', "Bermain meronce sedotan, membaca ikrar, panca karakter, jurnal pagi menggambar bebas sesuai ide masing-masing anak."],
            [3, $sampleClass, '2026-07-29', '07:30', '10:45', 'Sensori Motor', "Bermain playdough: menguatkan otot jari dengan teknik meremas, mencubit, dan memilin."],
        ];

        $rowIdx = 6;
        foreach ($sampleData as $row) {
            $sheet->setCellValue('A' . $rowIdx, $row[0]);
            $sheet->setCellValue('B' . $rowIdx, $row[1]);
            $sheet->setCellValueExplicit('C' . $rowIdx, $row[2], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowIdx, $row[3], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $rowIdx, $row[4], DataType::TYPE_STRING);
            $sheet->setCellValue('F' . $rowIdx, $row[5]);
            $sheet->setCellValue('G' . $rowIdx, $row[6]);

            $sheet->getStyle('A' . $rowIdx . ':G' . $rowIdx)->applyFromArray([
                'font' => ['size' => 9.5, 'name' => 'Arial'],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
            ]);

            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $rowIdx)->getAlignment()->setWrapText(true);

            $sheet->getRowDimension($rowIdx)->setRowHeight(45);
            $rowIdx++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(26);
        $sheet->getColumnDimension('G')->setWidth(65);

        $filename = 'Template_Import_Jurnal_Kelas_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $className) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Import Classroom Journals from uploaded Excel file.
     */
    public function importExcel(Request $request)
    {
        @ini_set('max_execution_time', 300);
        @set_time_limit(300);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:15360',
            'default_classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ], [
            'file.required' => 'Silakan pilih berkas Excel (.xlsx / .xls) untuk diimpor.',
            'file.mimes' => 'Format berkas harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran berkas tidak boleh lebih dari 15 MB.',
        ]);

        try {
            $uploadedFile = $request->file('file');
            $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();

            if ($highestRow < 2) {
                return back()->withErrors(['file' => 'Berkas Excel kosong atau tidak memiliki baris data.']);
            }

            // Cache classrooms and academic years
            $classrooms = Classroom::with(['homeroomTeacher'])->get();
            $academicYears = AcademicYear::all();
            $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
            $defaultClassroom = $request->default_classroom_id ? $classrooms->firstWhere('id', $request->default_classroom_id) : null;

            // Preload existing journals to memory for high-performance duplicate detection (prevents timeout)
            $existingJournals = ClassroomJournal::whereIn('classroom_id', $classrooms->pluck('id'))->get();
            $existingByKey = [];
            foreach ($existingJournals as $ej) {
                $timeKey = substr($ej->start_time ?? '', 0, 5);
                $k1 = $ej->classroom_id . '_' . $ej->date . '_' . $timeKey;
                $existingByKey[$k1] = $ej;
                $k2 = $ej->classroom_id . '_' . $ej->date . '_' . md5(trim($ej->class_condition));
                $existingByKey[$k2] = $ej;
            }

            // Step 1: Detect Header Row and Column Mapping
            $headerRowIndex = 1;
            $colMap = [
                'kelas' => null,
                'tanggal' => null,
                'jam_mulai' => null,
                'jam_selesai' => null,
                'topik' => null,
                'uraian' => null,
            ];

            // Scan rows 1 through 10 to find header
            for ($r = 1; $r <= min(10, $highestRow); $r++) {
                $rowLetters = range('A', $highestCol);
                $rowMap = [
                    'kelas' => null,
                    'tanggal' => null,
                    'jam_mulai' => null,
                    'jam_selesai' => null,
                    'topik' => null,
                    'uraian' => null,
                ];

                foreach ($rowLetters as $col) {
                    $val = strtolower(trim((string)$sheet->getCell($col . $r)->getValue()));
                    if (strlen($val) > 40) continue;

                    if (str_contains($val, 'uraian') || (str_contains($val, 'kegiatan') && !str_contains($val, 'agenda')) || str_contains($val, 'kondisi') || $val === 'class_condition') {
                        $rowMap['uraian'] = $col;
                    } elseif (str_contains($val, 'kelas') || str_contains($val, 'rombel')) {
                        $rowMap['kelas'] = $col;
                    } elseif (str_contains($val, 'tanggal') || str_contains($val, 'tgl') || $val === 'date') {
                        $rowMap['tanggal'] = $col;
                    } elseif (str_contains($val, 'jam_mulai') || (str_contains($val, 'mulai') && !str_contains($val, 'selesai')) || $val === 'start_time') {
                        $rowMap['jam_mulai'] = $col;
                    } elseif (str_contains($val, 'jam_selesai') || str_contains($val, 'selesai') || $val === 'end_time') {
                        $rowMap['jam_selesai'] = $col;
                    } elseif (str_contains($val, 'topik') || str_contains($val, 'tema') || $val === 'topic') {
                        $rowMap['topik'] = $col;
                    }
                }

                if ($rowMap['tanggal'] !== null && ($rowMap['uraian'] !== null || $rowMap['kelas'] !== null)) {
                    $colMap = $rowMap;
                    $headerRowIndex = $r;
                    break;
                }
            }

            // Fallback column map if headers weren't found cleanly
            if ($colMap['tanggal'] === null && $colMap['uraian'] === null) {
                if ($highestCol >= 'G') {
                    $colMap = [
                        'kelas' => 'B',
                        'tanggal' => 'C',
                        'jam_mulai' => 'D',
                        'jam_selesai' => 'E',
                        'topik' => 'F',
                        'uraian' => 'G',
                    ];
                } else {
                    $colMap = [
                        'kelas' => 'A',
                        'tanggal' => 'B',
                        'jam_mulai' => 'C',
                        'jam_selesai' => 'D',
                        'topik' => null,
                        'uraian' => 'E',
                    ];
                }
                $headerRowIndex = 1;
            }

            // Helpers for parsing time & date
            $parseTime = function($cell) {
                if (!$cell) return null;
                $val = $cell->getValue();
                if (is_numeric($val) && $val >= 0 && $val < 1) {
                    return ExcelDate::excelToDateTimeObject($val)->format('H:i');
                }
                $fmt = trim((string)$cell->getFormattedValue());
                if (preg_match('/^(\d{1,2}):(\d{2})/', $fmt, $m)) {
                    return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
                }
                return null;
            };

            $parseDate = function($cell) {
                if (!$cell) return null;
                $val = $cell->getValue();
                if (is_numeric($val) && $val > 1000) {
                    return ExcelDate::excelToDateTimeObject((float)$val)->format('Y-m-d');
                }
                $fmt = trim((string)$cell->getFormattedValue());
                if ($fmt !== '') {
                    try {
                        return Carbon::parse($fmt)->format('Y-m-d');
                    } catch (\Exception $e) {
                        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $fmt, $m)) {
                            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                        }
                    }
                }
                return null;
            };

            $successCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $targetClassroomIds = [];

            // Wrap in database transaction for speed & consistency
            \Illuminate\Support\Facades\DB::transaction(function() use (
                $sheet, $highestRow, $headerRowIndex, $colMap, $classrooms, $defaultClassroom,
                $request, $activeAcademicYear, $parseDate, $parseTime, &$successCount, &$updatedCount,
                &$skippedCount, &$errors, &$targetClassroomIds, &$existingByKey
            ) {
                $startDataRow = $headerRowIndex + 1;

                for ($r = $startDataRow; $r <= $highestRow; $r++) {
                    $rawKelas = $colMap['kelas'] ? trim((string)$sheet->getCell($colMap['kelas'] . $r)->getFormattedValue()) : '';
                    $rawTanggal = $colMap['tanggal'] ? $sheet->getCell($colMap['tanggal'] . $r) : null;
                    $rawJamMulai = $colMap['jam_mulai'] ? $sheet->getCell($colMap['jam_mulai'] . $r) : null;
                    $rawJamSelesai = $colMap['jam_selesai'] ? $sheet->getCell($colMap['jam_selesai'] . $r) : null;
                    $rawTopik = $colMap['topik'] ? trim((string)$sheet->getCell($colMap['topik'] . $r)->getFormattedValue()) : null;
                    $rawUraian = $colMap['uraian'] ? trim((string)$sheet->getCell($colMap['uraian'] . $r)->getFormattedValue()) : '';

                    // Skip if entire row or vital fields are empty
                    if ($rawKelas === '' && (!$rawTanggal || trim((string)$rawTanggal->getValue()) === '') && $rawUraian === '') {
                        continue;
                    }

                    if ($rawUraian === '') {
                        $skippedCount++;
                        continue;
                    }

                    // Match Classroom
                    $targetClass = null;
                    if ($rawKelas !== '') {
                        $normalizedKelas = preg_replace('/\s+/', ' ', strtolower($rawKelas));
                        $targetClass = $classrooms->first(function ($c) use ($rawKelas, $normalizedKelas) {
                            $normName = preg_replace('/\s+/', ' ', strtolower($c->name));
                            return $normName === $normalizedKelas || str_contains($normName, $normalizedKelas);
                        });
                    }
                    if (!$targetClass) {
                        $targetClass = $defaultClassroom;
                    }

                    if (!$targetClass) {
                        $errors[] = "Baris {$r}: Rombel/Kelas '{$rawKelas}' tidak ditemukan dalam sistem.";
                        $skippedCount++;
                        continue;
                    }

                    $targetClassroomIds[$targetClass->id] = true;

                    // Parse Date
                    $dateFormatted = $parseDate($rawTanggal);
                    if (!$dateFormatted) {
                        $errors[] = "Baris {$r}: Format tanggal tidak valid.";
                        $skippedCount++;
                        continue;
                    }

                    // Parse Start & End Time
                    $startTime = $parseTime($rawJamMulai);
                    $endTime = $parseTime($rawJamSelesai);

                    // Academic Year
                    $academicYearId = $request->academic_year_id ?: ($targetClass->academic_year_id ?: $activeAcademicYear?->id);

                    // Teacher: homeroom teacher or auth user employee_id
                    $teacherId = $targetClass->homeroom_teacher_id ?: (auth()->user()?->employee_id ?? null);

                    // In-memory key check for duplicates (instant performance)
                    $timeKey = substr($startTime ?? '', 0, 5);
                    $k1 = $targetClass->id . '_' . $dateFormatted . '_' . $timeKey;
                    $k2 = $targetClass->id . '_' . $dateFormatted . '_' . md5($rawUraian);

                    $existing = ($startTime && isset($existingByKey[$k1])) ? $existingByKey[$k1] : ($existingByKey[$k2] ?? null);

                    if ($existing) {
                        $existing->update([
                            'academic_year_id' => $academicYearId,
                            'teacher_id' => $teacherId ?: $existing->teacher_id,
                            'start_time' => $startTime ?: $existing->start_time,
                            'end_time' => $endTime ?: $existing->end_time,
                            'topic' => $rawTopik ?: $existing->topic,
                            'class_condition' => $rawUraian,
                        ]);
                        $updatedCount++;
                    } else {
                        $created = ClassroomJournal::create([
                            'classroom_id' => $targetClass->id,
                            'academic_year_id' => $academicYearId,
                            'teacher_id' => $teacherId,
                            'date' => $dateFormatted,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'topic' => $rawTopik ?: null,
                            'class_condition' => $rawUraian,
                            'recorded_by' => auth()->id(),
                        ]);
                        $existingByKey[$k1] = $created;
                        $existingByKey[$k2] = $created;
                        $successCount++;
                    }
                }
            });

            $totalProcessed = $successCount + $updatedCount;
            $msg = "Proses import selesai: {$totalProcessed} entri jurnal kelas berhasil diproses ({$successCount} baru, {$updatedCount} diperbarui).";
            if ($skippedCount > 0) {
                $msg .= " Sebanyak {$skippedCount} baris dilewati karena format tidak lengkap.";
            }

            $redirectClassroomId = $defaultClassroom?->id ?? (count($targetClassroomIds) === 1 ? array_key_first($targetClassroomIds) : 'all');

            return redirect()->route('classroom-journals.index', array_filter([
                'classroom_id' => $redirectClassroomId,
                'academic_year_id' => $request->academic_year_id,
            ]))->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal membaca berkas Excel: ' . $e->getMessage()]);
        }
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

        $classroomId = $request->get('classroom_id');
        $isAll = ($classroomId === 'all' || empty($classroomId));
        $classroom = $isAll ? null : Classroom::with(['homeroomTeacher', 'classLevel'])->find($classroomId);
        $className = $classroom ? $classroom->name : 'Semua Rombel';

        $journals = ClassroomJournal::with(['teacher', 'classroom'])
            ->when(!$isAll && $classroom, function($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id);
            })
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
        $classroomLabel = $classroom ? "{$classroom->name} ({$classroom->sub_unit})" : "Semua Rombel";
        $sheet->setCellValue('A1', 'AGENDA / JURNAL PEMBELAJARAN KELAS');
        $sheet->setCellValue('A2', strtoupper($appName));
        $sheet->setCellValue('A3', "Kelas / Rombel: {$classroomLabel} | T.A: {$taName} | Bulan: {$monthName} {$year} | Dicetak: " . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9.5)->getColor()->setARGB('FF475569');

        // Table Headers
        $headers = $classroom
            ? ['No', 'Hari & Tanggal', 'Waktu KBM', 'Guru / Pendidik', 'Topik / Tema Pembelajaran', 'Uraian Kegiatan & Kondisi Kelas']
            : ['No', 'Rombel', 'Hari & Tanggal', 'Waktu KBM', 'Guru / Pendidik', 'Topik / Tema Pembelajaran', 'Uraian Kegiatan & Kondisi Kelas'];
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
            $teacherName = $j->teacher?->name ?? ($j->classroom?->homeroomTeacher?->name ?? ($classroom?->homeroomTeacher?->name ?? '-'));

            if ($classroom) {
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
            } else {
                $sheet->setCellValue("A{$currentRow}", $no++);
                $sheet->setCellValue("B{$currentRow}", $j->classroom?->name ?? '-');
                $sheet->setCellValue("C{$currentRow}", $dateFormatted);
                $sheet->setCellValue("D{$currentRow}", $timeFormatted);
                $sheet->setCellValue("E{$currentRow}", $teacherName);
                $sheet->setCellValue("F{$currentRow}", $j->topic ?: '-');
                $sheet->setCellValue("G{$currentRow}", $j->class_condition);

                $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$currentRow}")->getAlignment()->setWrapText(true);
            }

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Borders
        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        if ($classroom) {
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(24);
            $sheet->getColumnDimension('E')->setWidth(28);
            $sheet->getColumnDimension('F')->setWidth(45);
        } else {
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(16);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(18);
            $sheet->getColumnDimension('E')->setWidth(24);
            $sheet->getColumnDimension('F')->setWidth(28);
            $sheet->getColumnDimension('G')->setWidth(45);
        }

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
        $tName = $classroom?->homeroomTeacher?->name ?? 'Wali Kelas';
        $tNip = $classroom?->homeroomTeacher?->nip ?? ($classroom?->homeroomTeacher?->niy ?? '-');
        $sheet->setCellValue("E" . ($signRow + 4), $tName);
        $sheet->getStyle("E" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("E" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("E" . ($signRow + 5), "NIP/NIY: " . $tNip);
        $sheet->getStyle("E" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("E" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $cleanClassName = preg_replace('/[^A-Za-z0-9_-]/', '_', $className);
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
        $isAll = ($classroomId === 'all' || empty($classroomId));
        $month = (int) $request->get('month', Carbon::now()->month);

        $academicYears = AcademicYear::all();
        $selectedYearId = $request->get('academic_year_id');
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedYearId) ?? $academicYears->firstWhere('is_active', true);

        $parts = explode('/', $selectedAcademicYear?->name ?? '');
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)Carbon::now()->year;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $calculatedYear = ($month >= 7) ? $startYear : $endYear;
        $year = (int) $request->get('year', $calculatedYear);

        $classroom = $isAll ? null : Classroom::with(['homeroomTeacher', 'classLevel'])->find($classroomId);
        $className = $classroom ? $classroom->name : 'Semua Rombel';

        $journals = ClassroomJournal::with(['teacher', 'classroom', 'recordedBy'])
            ->when(!$isAll && $classroom, function($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id);
            })
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

        $cleanClassName = preg_replace('/[^A-Za-z0-9_-]/', '_', $className);
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
        $isAll = ($classroomId === 'all' || empty($classroomId));
        $month = (int) $request->get('month', Carbon::now()->month);

        $academicYears = AcademicYear::all();
        $selectedYearId = $request->get('academic_year_id');
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedYearId) ?? $academicYears->firstWhere('is_active', true);

        $parts = explode('/', $selectedAcademicYear?->name ?? '');
        $startYear = isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : (int)Carbon::now()->year;
        $endYear = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : ($startYear + 1);
        $calculatedYear = ($month >= 7) ? $startYear : $endYear;
        $year = (int) $request->get('year', $calculatedYear);

        $classroom = $isAll ? null : Classroom::with(['homeroomTeacher', 'classLevel'])->find($classroomId);
        $className = $classroom ? $classroom->name : 'Semua Rombel';

        $journals = ClassroomJournal::with(['teacher', 'classroom', 'recordedBy'])
            ->when(!$isAll && $classroom, function($q) use ($classroom) {
                $q->where('classroom_id', $classroom->id);
            })
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

