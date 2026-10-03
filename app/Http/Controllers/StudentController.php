<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Jenjang;
use App\Models\Student;
use App\Models\StudentClassroomHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentController extends Controller
{
    /**
     * Display a listing of students with filters & pagination.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        // Default to active academic year if not explicitly selected
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;
        $selectedYearId = $selectedYear?->id;

        // Master lists for filter dropdowns & modal selects
        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id');
        if (!$selectedJenjangId && $jenjangs->isNotEmpty()) {
            $selectedJenjangId = $jenjangs->first()->id;
        }

        // Compute active students count for each Jenjang tab
        foreach ($jenjangs as $j) {
            $countQuery = Student::where('status', 'aktif')
                ->where(function ($q) use ($j) {
                    $q->where('jenjang_id', $j->id)
                      ->orWhereHas('classroom', function ($sq) use ($j) {
                          $sq->where('jenjang_id', $j->id);
                      })
                      ->orWhereHas('classLevel', function ($sq) use ($j) {
                          $sq->where('jenjang_id', $j->id);
                      });
                });
            if ($selectedYearId && $selectedYearId !== 'all') {
                $countQuery->where('academic_year_id', $selectedYearId);
            }
            $j->students_count = $countQuery->count();
        }

        $query = Student::with([
            'jenjang',
            'classroom.classLevel',
            'classroom.jenjang',
            'daycareClassroom',
            'tpqClassroom',
            'classLevel.jenjang',
            'academicYear',
            'spmbCandidate'
        ]);

        // Jenjang Filter (Active Tab)
        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $query->where(function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId)
                  ->orWhereHas('classroom', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  })
                  ->orWhereHas('classLevel', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  });
            });
        }

        // Academic Year Filter
        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
        }

        // Search query
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        // Filter: Kelas (ClassLevel)
        $selectedClassLevelId = $request->get('class_level_id');
        if ($selectedClassLevelId && $selectedClassLevelId !== 'all') {
            $query->where(function ($q) use ($selectedClassLevelId) {
                $q->where('class_level_id', $selectedClassLevelId)
                  ->orWhereHas('classroom', function ($sq) use ($selectedClassLevelId) {
                      $sq->where('class_level_id', $selectedClassLevelId);
                  });
            });
        }

        // Filter: Kelompok (Classroom)
        $selectedClassroomId = $request->get('classroom_id');
        if ($selectedClassroomId && $selectedClassroomId !== 'all') {
            $query->where(function ($q) use ($selectedClassroomId) {
                $q->where('classroom_id', $selectedClassroomId)
                  ->orWhere('daycare_classroom_id', $selectedClassroomId)
                  ->orWhere('tpq_classroom_id', $selectedClassroomId);
            });
        }

        // Filter: Status
        $selectedStatus = $request->get('status', 'all');
        if ($selectedStatus && $selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        // Filter: Gender
        $selectedGender = $request->get('gender', 'all');
        if ($selectedGender && $selectedGender !== 'all') {
            $query->where('gender', $selectedGender);
        }

        // Fast Single Aggregation Query for all Student Stats in the active Jenjang
        $statsAgg = Student::query();
        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $statsAgg->where(function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId)
                  ->orWhereHas('classroom', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  })
                  ->orWhereHas('classLevel', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  });
            });
        }
        if ($selectedYearId && $selectedYearId !== 'all') {
            $statsAgg->where('academic_year_id', $selectedYearId);
        }
        $aggregated = $statsAgg->selectRaw("
            COUNT(*) as total_all,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as total_active,
            SUM(CASE WHEN status = 'aktif' AND gender IN ('L', 'Laki-laki', 'Male') THEN 1 ELSE 0 END) as male,
            SUM(CASE WHEN status = 'aktif' AND gender IN ('P', 'Perempuan', 'Female') THEN 1 ELSE 0 END) as female
        ")->first();

        $rombelQuery = Classroom::where('is_active', true);
        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $rombelQuery->where('jenjang_id', $selectedJenjangId);
        }
        if ($selectedYearId && $selectedYearId !== 'all') {
            $rombelQuery->where('academic_year_id', $selectedYearId);
        }
        if ($selectedClassLevelId && $selectedClassLevelId !== 'all') {
            $rombelQuery->where('class_level_id', $selectedClassLevelId);
        }
        $totalClassrooms = $rombelQuery->count();

        $stats = [
            'total_active' => (int) ($aggregated->total_active ?? 0),
            'total_all' => (int) ($aggregated->total_all ?? 0),
            'male' => (int) ($aggregated->male ?? 0),
            'female' => (int) ($aggregated->female ?? 0),
            'classrooms' => $totalClassrooms,
        ];

        // Filter dropdowns: ClassLevels and Classrooms filtered by the active Jenjang tab & selected Kelas
        $classLevels = ClassLevel::where('is_active', true)
            ->when($selectedJenjangId && $selectedJenjangId !== 'all', fn($q) => $q->where('jenjang_id', $selectedJenjangId))
            ->orderBy('order')
            ->get();

        $classrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true)
            ->when($selectedJenjangId && $selectedJenjangId !== 'all', fn($q) => $q->where('jenjang_id', $selectedJenjangId))
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($selectedClassLevelId && $selectedClassLevelId !== 'all', fn($q) => $q->where('class_level_id', $selectedClassLevelId))
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();

        // Master lists for Modal dropdowns
        $allClassLevels = ClassLevel::with('jenjang')->where('is_active', true)->orderBy('order')->get();
        $allClassrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])->where('is_active', true)->orderBy('academic_year_id', 'desc')->orderBy('name')->get();

        $daycareJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'DAYCARE')
              ->orWhere('code', 'TPA')
              ->orWhere('name', 'like', '%Daycare%')
              ->orWhere('name', 'like', '%TPA%');
        })->pluck('id');

        $daycareClassrooms = Classroom::with(['classLevel', 'academicYear', 'jenjang'])
            ->where('is_active', true)
            ->whereIn('jenjang_id', $daycareJenjangIds)
            ->orderBy('name')
            ->get();

        $tpqJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'TPQ')
              ->orWhere('name', 'like', '%TPQ%');
        })->pluck('id');

        $tpqClassrooms = Classroom::with(['classLevel', 'academicYear', 'jenjang'])
            ->where('is_active', true)
            ->whereIn('jenjang_id', $tpqJenjangIds)
            ->orderBy('name')
            ->get();

        $students = $query->orderBy('status', 'asc')->orderBy('full_name', 'asc')->paginate(20)->withQueryString();

        return view('admin.students.index', compact(
            'students',
            'stats',
            'jenjangs',
            'classLevels',
            'classrooms',
            'allClassLevels',
            'allClassrooms',
            'daycareClassrooms',
            'tpqClassrooms',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedYear',
            'selectedJenjangId',
            'selectedClassLevelId',
            'selectedClassroomId',
            'selectedStatus',
            'selectedGender'
        ));
    }

    /**
     * Show single student detail (JSON) with complete multi-year history journey.
     */
    public function show($id): JsonResponse
    {
        $student = Student::with([
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'daycareClassroom.homeroomTeacher',
            'tpqClassroom.homeroomTeacher',
            'classLevel',
            'academicYear',
            'spmbCandidate',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'asc');
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'student' => $student,
            'formatted_gender' => $student->formatted_gender,
            'age' => $student->age,
            'whatsapp_url' => $student->whatsapp_url,
            'clean_phone' => $student->clean_parent_phone,
            'histories' => $student->classroomHistories,
        ]);
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students,nis',
            'nisn' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|string|in:L,P,Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'sub_unit' => 'nullable|string|in:PG,TK,DAYCARE,TPQ',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'daycare_classroom_id' => 'nullable|exists:classrooms,id',
            'is_tpq' => 'nullable|boolean',
            'tpq_classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'parent_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'pin_access' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'enrolled_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['academic_year_id'])) {
            $activeAY = AcademicYear::where('is_active', true)->first();
            $validated['academic_year_id'] = $activeAY ? $activeAY->id : null;
        }

        if (empty($validated['enrolled_date'])) {
            $validated['enrolled_date'] = now()->toDateString();
        }

        if (!empty($validated['classroom_id'])) {
            $cls = Classroom::find($validated['classroom_id']);
            if ($cls) {
                if (empty($validated['jenjang_id'])) {
                    $validated['jenjang_id'] = $cls->jenjang_id;
                }
                if (empty($validated['class_level_id'])) {
                    $validated['class_level_id'] = $cls->class_level_id;
                }
                if (empty($validated['sub_unit'])) {
                    $validated['sub_unit'] = $cls->sub_unit ?? 'TK';
                }
            }
        }

        $student = Student::create($validated);

        // Sync history
        if ($student->classroom_id) {
            $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
            StudentClassroomHistory::create([
                'student_id' => $student->id,
                'academic_year_id' => $student->academic_year_id,
                'classroom_id' => $student->classroom_id,
                'sub_unit' => $student->sub_unit,
                'classroom_name' => $student->classroom?->name,
                'grade_level' => $student->classroom?->classLevel?->name,
                'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name,
                'status' => 'aktif',
                'start_date' => $student->enrolled_date ?? now()->toDateString(),
                'notes' => 'Pendaftaran Murid Baru',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Data murid {$student->full_name} berhasil ditambahkan.",
            'student' => $student,
        ]);
    }

    /**
     * Update student details.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students,nis,' . $student->id,
            'nisn' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|string|in:L,P,Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'sub_unit' => 'nullable|string|in:PG,TK,DAYCARE,TPQ',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'daycare_classroom_id' => 'nullable|exists:classrooms,id',
            'is_tpq' => 'nullable|boolean',
            'tpq_classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'parent_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'pin_access' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['classroom_id'])) {
            $cls = Classroom::find($validated['classroom_id']);
            if ($cls) {
                if (empty($validated['jenjang_id'])) {
                    $validated['jenjang_id'] = $cls->jenjang_id;
                }
                if (empty($validated['class_level_id'])) {
                    $validated['class_level_id'] = $cls->class_level_id;
                }
                if (empty($validated['sub_unit'])) {
                    $validated['sub_unit'] = $cls->sub_unit ?? 'TK';
                }
            }
        }

        $student->update($validated);

        // Sync or record history with immutable text snapshots
        if ($student->classroom_id) {
            $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
            $isInactiveOrExit = in_array($student->status, ['mutasi', 'keluar', 'lulus', 'nonaktif']);

            StudentClassroomHistory::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $student->academic_year_id,
                    'classroom_id' => $student->classroom_id,
                ],
                [
                    'sub_unit' => $student->sub_unit ?: ($student->classroom?->sub_unit ?? 'TK'),
                    'classroom_name' => $student->classroom?->name,
                    'grade_level' => $student->classroom?->classLevel?->name,
                    'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name,
                    'status' => $student->status,
                    'start_date' => $student->enrolled_date ?? now()->toDateString(),
                    'end_date' => $isInactiveOrExit ? now()->toDateString() : null,
                    'notes' => 'Pembaruan Data Murid (' . ucfirst($student->status) . ')',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Data murid {$student->full_name} berhasil diperbarui.",
            'student' => $student,
        ]);
    }

    /**
     * Delete student.
     */
    public function destroy($id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $name = $student->full_name;

        // If linked to SPMB candidate, unlink it
        if ($student->spmb_candidate_id) {
            $candidate = \App\Models\SpmbCandidate::find($student->spmb_candidate_id);
            if ($candidate) {
                $candidate->is_enrolled = false;
                $candidate->enrolled_at = null;
                $candidate->student_id = null;
                $candidate->save();
            }
        }

        $student->classroomHistories()->delete();
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => "Data murid {$name} berhasil dihapus.",
        ]);
    }

    /**
     * Export students data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id');
        $selectedJenjang = $jenjangs->firstWhere('id', $selectedJenjangId);

        $query = Student::with(['jenjang', 'classroom.classLevel', 'classroom.jenjang', 'daycareClassroom', 'tpqClassroom', 'classLevel', 'academicYear']);

        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $query->where(function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId)
                  ->orWhereHas('classroom', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  })
                  ->orWhereHas('classLevel', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  });
            });
        }

        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        if ($classLevelId = $request->get('class_level_id')) {
            if ($classLevelId !== 'all') {
                $query->where(function ($q) use ($classLevelId) {
                    $q->where('class_level_id', $classLevelId)
                      ->orWhereHas('classroom', function ($sq) use ($classLevelId) {
                          $sq->where('class_level_id', $classLevelId);
                      });
                });
            }
        }

        if ($classroomId = $request->get('classroom_id')) {
            if ($classroomId !== 'all') {
                $query->where(function ($q) use ($classroomId) {
                    $q->where('classroom_id', $classroomId)
                      ->orWhere('daycare_classroom_id', $classroomId)
                      ->orWhere('tpq_classroom_id', $classroomId);
                });
            }
        }

        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($gender = $request->get('gender')) {
            if ($gender !== 'all') {
                $query->where('gender', $gender);
            }
        }

        $students = $query->orderBy('classroom_id', 'asc')
            ->orderBy('full_name', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Murid');

        // Document Title
        $sheet->setCellValue('A1', 'DAFTAR DATA MURID (PESERTA DIDIK)');
        $sheet->setCellValue('A2', 'KB - TK - DAYCARE - TPQ ANAK SALEH MALANG');
        $sheet->setCellValue('A3', 'Tahun Ajaran: ' . ($selectedYear ? $selectedYear->name : 'Semua') . ' | Jenjang: ' . ($selectedJenjang ? $selectedJenjang->name : 'Semua') . ' | Total Murid: ' . $students->count() . ' | Tanggal Unduh: ' . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF475569');

        // Table Headers
        $headers = [
            'No',
            'NIS',
            'NISN',
            'NIK',
            'Nama Lengkap',
            'Nama Panggilan',
            'L/P',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Usia',
            'Sub Unit',
            'Jenjang',
            'Kelompok (Rombel)',
            'Kelompok Daycare',
            'Status TPQ',
            'Kelompok TPQ',
            'Nama Ayah',
            'No. HP Ayah',
            'Nama Ibu',
            'No. HP Ibu',
            'No. WhatsApp Kontak',
            'Alamat',
            'Kota',
            'PIN Akses',
            'Status'
        ];

        $startRow = 5;
        foreach ($headers as $colIndex => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $header);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4F46E5'); // Indigo 600
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(26);

        // Populate Data
        $currentRow = $startRow + 1;
        foreach ($students as $index => $s) {
            $genderLabel = in_array(strtoupper($s->gender), ['L', 'LAKI-LAKI', 'MALE']) ? 'L' : 'P';
            $birthDateFormatted = $s->birth_date ? date('Y-m-d', strtotime($s->birth_date)) : '-';
            $jenjangName = $s->classroom?->classLevel?->name ?? $s->classLevel?->name ?? '-';
            $rombelName = $s->classroom?->name ?? '-';
            $daycareName = $s->daycareClassroom?->name ?? '-';
            $tpqStatus = ($s->is_tpq || $s->tpq_classroom_id || $s->sub_unit === 'TPQ') ? 'Ya' : 'Tidak';
            $tpqName = $s->tpqClassroom?->name ?? ($tpqStatus === 'Ya' ? 'TPQ' : '-');

            $sheet->setCellValue("A{$currentRow}", $index + 1);
            $sheet->setCellValueExplicit("B{$currentRow}", (string)$s->nis, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string)($s->nisn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currentRow}", (string)($s->nik ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$currentRow}", $s->full_name);
            $sheet->setCellValue("F{$currentRow}", $s->nickname ?? '-');
            $sheet->setCellValue("G{$currentRow}", $genderLabel);
            $sheet->setCellValue("H{$currentRow}", $s->birth_place ?? '-');
            $sheet->setCellValue("I{$currentRow}", $birthDateFormatted);
            $sheet->setCellValue("J{$currentRow}", $s->age ?? '-');
            $sheet->setCellValue("K{$currentRow}", $s->sub_unit ?? 'TK');
            $sheet->setCellValue("L{$currentRow}", $jenjangName);
            $sheet->setCellValue("M{$currentRow}", $rombelName);
            $sheet->setCellValue("N{$currentRow}", $daycareName);
            $sheet->setCellValue("O{$currentRow}", $tpqStatus);
            $sheet->setCellValue("P{$currentRow}", $tpqName);
            $sheet->setCellValue("Q{$currentRow}", $s->father_name ?? '-');
            $sheet->setCellValueExplicit("R{$currentRow}", (string)($s->father_phone ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("S{$currentRow}", $s->mother_name ?? '-');
            $sheet->setCellValueExplicit("T{$currentRow}", (string)($s->mother_phone ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("U{$currentRow}", (string)($s->clean_parent_phone ?? $s->parent_phone ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("V{$currentRow}", $s->address ?? '-');
            $sheet->setCellValue("W{$currentRow}", $s->city ?? 'Malang');
            $sheet->setCellValueExplicit("X{$currentRow}", (string)($s->pin_access ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("Y{$currentRow}", ucfirst($s->status ?? 'aktif'));

            // Center align for certain columns
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$currentRow}:L{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("X{$currentRow}:Y{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Zebra striping
            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Apply borders
        $dataEndRow = max($currentRow - 1, $startRow);
        $sheet->getStyle("A{$startRow}:{$lastCol}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE2E8F0'));

        // Auto-fit column widths
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Data_Murid_PAUD_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Download Excel template for importing students.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Murid');

        $headers = [
            'NIS',
            'NISN',
            'NIK',
            'Nama Lengkap',
            'Nama Panggilan',
            'Jenis Kelamin (L/P)',
            'Tempat Lahir',
            'Tanggal Lahir (YYYY-MM-DD)',
            'Agama',
            'Sub Unit (PG/TK/DAYCARE/TPQ)',
            'Jenjang (KB-A/KB-B/TK-A/TK-B/Daycare/TPQ)',
            'Nama Kelompok / Rombel',
            'Kelompok Daycare (opsional)',
            'Ikut TPQ (Ya/Tidak)',
            'Kelompok TPQ (opsional)',
            'Nama Ayah',
            'No HP Ayah',
            'Nama Ibu',
            'No HP Ibu',
            'No WhatsApp Kontak',
            'Alamat',
            'Kota',
            'PIN Akses Wali',
            'Status (aktif/lulus/mutasi/keluar/nonaktif)'
        ];

        // Header row
        foreach ($headers as $colIndex => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        // Example data rows
        $example1 = [
            '0101',
            '0192837465',
            '3573010101210001',
            'Muhammad Fatih Al-Faruq',
            'Fatih',
            'L',
            'Malang',
            '2021-03-15',
            'Islam',
            'TK',
            'TK-A',
            'TK A-1 (Bintang)',
            'Daycare Toddler',
            'Ya',
            'TPQ Al-Fatih',
            'Ahmad Fauzi',
            '081234567890',
            'Siti Aminah',
            '081234567891',
            '081234567890',
            'Jl. Candi Mendut No. 12, Lowokwaru',
            'Malang',
            '1234',
            'aktif'
        ];

        $example2 = [
            '0102',
            '0192837466',
            '3573010101220002',
            'Aisyah Humaira Putri',
            'Aisyah',
            'P',
            'Malang',
            '2022-06-20',
            'Islam',
            'PG',
            'KB-A',
            'KB Bulan',
            '',
            'Tidak',
            '',
            'Bambang Susanto',
            '082198765432',
            'Nurul Hidayah',
            '082198765433',
            '082198765432',
            'Jl. Soekarno Hatta No. 45',
            'Malang',
            '5678',
            'aktif'
        ];

        foreach ($example1 as $colIndex => $val) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValueExplicit($colLetter . '2', (string)$val, DataType::TYPE_STRING);
        }

        foreach ($example2 as $colIndex => $val) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValueExplicit($colLetter . '3', (string)$val, DataType::TYPE_STRING);
        }

        // Header style
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Auto-size columns
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Template_Impor_Murid_PAUD.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import students from Excel spreadsheet.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'default_academic_year_id' => 'nullable|exists:academic_years,id',
            'default_classroom_id' => 'nullable|exists:classrooms,id',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file wajib berupa .xlsx, .xls, atau .csv.'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        if (count($rows) < 2) {
            return redirect()->back()->with('error', 'File Excel kosong atau tidak memiliki data murid.');
        }

        // Shift headers
        array_shift($rows);

        $activeAY = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        $defaultAYId = $request->input('default_academic_year_id', $activeAY?->id);
        $defaultClassroomId = $request->input('default_classroom_id');

        $errors = [];
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            // Name is at column index 3 (or column index 0 if format is alternate)
            $nis = !empty($row[0]) ? trim((string)$row[0]) : null;
            $nisn = !empty($row[1]) ? trim((string)$row[1]) : null;
            $nik = !empty($row[2]) ? trim((string)$row[2]) : null;
            $fullName = !empty($row[3]) ? trim((string)$row[3]) : null;

            // Skip empty rows
            if (empty($fullName)) {
                continue;
            }

            $nickname = !empty($row[4]) ? trim((string)$row[4]) : null;
            $genderRaw = !empty($row[5]) ? trim((string)$row[5]) : 'L';
            $birthPlace = !empty($row[6]) ? trim((string)$row[6]) : null;
            $birthDateRaw = !empty($row[7]) ? $row[7] : null;
            $religion = !empty($row[8]) ? trim((string)$row[8]) : 'Islam';
            $subUnitRaw = !empty($row[9]) ? strtoupper(trim((string)$row[9])) : 'TK';
            $jenjangRaw = !empty($row[10]) ? trim((string)$row[10]) : null;
            $kelompokRaw = !empty($row[11]) ? trim((string)$row[11]) : null;
            $daycareRaw = !empty($row[12]) ? trim((string)$row[12]) : null;
            $tpqRaw = !empty($row[13]) ? trim((string)$row[13]) : null;
            $tpqKelompokRaw = !empty($row[14]) ? trim((string)$row[14]) : null;
            $fatherName = !empty($row[15]) ? trim((string)$row[15]) : null;
            $fatherPhone = !empty($row[16]) ? trim((string)$row[16]) : null;
            $motherName = !empty($row[17]) ? trim((string)$row[17]) : null;
            $motherPhone = !empty($row[18]) ? trim((string)$row[18]) : null;
            $parentPhone = !empty($row[19]) ? trim((string)$row[19]) : ($fatherPhone ?: $motherPhone);
            $address = !empty($row[20]) ? trim((string)$row[20]) : null;
            $city = !empty($row[21]) ? trim((string)$row[21]) : 'Malang';
            $pinAccess = !empty($row[22]) ? trim((string)$row[22]) : null;
            $statusRaw = !empty($row[23]) ? strtolower(trim((string)$row[23])) : 'aktif';

            // Parse Gender
            $gender = 'L';
            if (in_array(strtoupper($genderRaw), ['P', 'PEREMPUAN', 'FEMALE', 'W'])) {
                $gender = 'P';
            }

            // Parse Birth Date
            $birthDate = null;
            if (!empty($birthDateRaw)) {
                if (is_numeric($birthDateRaw)) {
                    try {
                        $birthDate = ExcelDate::excelToDateTimeObject($birthDateRaw)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $birthDate = null;
                    }
                } else {
                    $ts = strtotime(str_replace('/', '-', $birthDateRaw));
                    if ($ts !== false) {
                        $birthDate = date('Y-m-d', $ts);
                    }
                }
            }

            // Resolve Classroom dynamically from Classroom table & relations
            $classroomId = $defaultClassroomId;
            $classLevelId = null;
            $jenjangId = null;
            $subUnit = null;

            if (!empty($kelompokRaw)) {
                $matchedClassroom = Classroom::with(['jenjang', 'classLevel.jenjang'])->where('name', 'like', "%{$kelompokRaw}%")->first();
                if ($matchedClassroom) {
                    $classroomId = $matchedClassroom->id;
                    $classLevelId = $matchedClassroom->class_level_id;
                    $jenjangId = $matchedClassroom->jenjang_id ?? $matchedClassroom->classLevel?->jenjang_id;
                    $subUnit = $matchedClassroom->jenjang?->code ?? $matchedClassroom->sub_unit;
                }
            }

            // Resolve Class Level if not set by classroom
            if (!$classLevelId && !empty($jenjangRaw)) {
                $matchedLevel = ClassLevel::with('jenjang')->where('name', 'like', "%{$jenjangRaw}%")->first();
                if ($matchedLevel) {
                    $classLevelId = $matchedLevel->id;
                    if (!$jenjangId) {
                        $jenjangId = $matchedLevel->jenjang_id;
                    }
                    if (!$subUnit) {
                        $subUnit = $matchedLevel->jenjang?->code;
                    }
                }
            }

            // Resolve Jenjang directly if still not set
            if (!$jenjangId && !empty($jenjangRaw)) {
                $matchedJenjang = Jenjang::where('name', 'like', "%{$jenjangRaw}%")->orWhere('code', 'like', "%{$jenjangRaw}%")->first();
                if ($matchedJenjang) {
                    $jenjangId = $matchedJenjang->id;
                    $subUnit = $matchedJenjang->code;
                }
            }

            // Resolve Daycare Classroom dynamically via Jenjang
            $daycareClassroomId = null;
            if (!empty($daycareRaw)) {
                $dc = Classroom::whereHas('jenjang', function($jq) {
                    $jq->whereIn('code', ['DAYCARE', 'TPA'])->orWhere('name', 'like', '%Daycare%')->orWhere('name', 'like', '%TPA%');
                })->where('name', 'like', "%{$daycareRaw}%")->first();
                
                if (!$dc) {
                    $dc = Classroom::where('name', 'like', "%{$daycareRaw}%")->first();
                }
                $daycareClassroomId = $dc?->id;
            }

            // Resolve TPQ dynamically via Jenjang
            $isTpq = in_array(strtolower($tpqRaw ?? ''), ['ya', 'true', '1', 'tpq', 'ikut']);
            $tpqClassroomId = null;
            if (!empty($tpqKelompokRaw)) {
                $tpqCls = Classroom::whereHas('jenjang', function($jq) {
                    $jq->where('code', 'TPQ')->orWhere('name', 'like', '%TPQ%');
                })->where('name', 'like', "%{$tpqKelompokRaw}%")->first();

                if (!$tpqCls) {
                    $tpqCls = Classroom::where('name', 'like', "%{$tpqKelompokRaw}%")->first();
                }
                $tpqClassroomId = $tpqCls?->id;
                if ($tpqClassroomId) {
                    $isTpq = true;
                }
            }

            // Generate fallback NIS if empty
            if (empty($nis)) {
                $yearPrefix = date('y');
                $lastStudent = Student::orderBy('id', 'desc')->first();
                $seq = $lastStudent ? ($lastStudent->id + 1) : 1;
                $nis = sprintf('%s%04d', $yearPrefix, $seq);
            }

            // Find existing student by NIS or exact Name + Birth Date
            $student = Student::where('nis', $nis)->first();
            if (!$student && $birthDate) {
                $student = Student::where('full_name', $fullName)->where('birth_date', $birthDate)->first();
            }

            $data = [
                'nis' => $nis,
                'nisn' => $nisn,
                'nik' => $nik,
                'full_name' => $fullName,
                'nickname' => $nickname,
                'gender' => $gender,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'religion' => $religion,
                'jenjang_id' => $jenjangId,
                'sub_unit' => $subUnit,
                'class_level_id' => $classLevelId,
                'classroom_id' => $classroomId,
                'daycare_classroom_id' => $daycareClassroomId,
                'is_tpq' => $isTpq,
                'tpq_classroom_id' => $tpqClassroomId,
                'academic_year_id' => $defaultAYId,
                'father_name' => $fatherName,
                'father_phone' => $fatherPhone,
                'mother_name' => $motherName,
                'mother_phone' => $motherPhone,
                'parent_phone' => $parentPhone,
                'address' => $address,
                'city' => $city,
                'pin_access' => $pinAccess,
                'status' => $status,
                'enrolled_date' => now()->toDateString(),
            ];

            if ($student) {
                $student->update($data);
                $updatedCount++;
            } else {
                $student = Student::create($data);
                $importedCount++;
            }

            // Record classroom history
            if ($student->classroom_id) {
                $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
                StudentClassroomHistory::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $student->academic_year_id,
                        'classroom_id' => $student->classroom_id,
                    ],
                    [
                        'sub_unit' => $student->sub_unit,
                        'classroom_name' => $student->classroom?->name,
                        'grade_level' => $student->classroom?->classLevel?->name,
                        'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name,
                        'status' => 'aktif',
                        'start_date' => $student->enrolled_date ?? now()->toDateString(),
                        'notes' => 'Impor Data Excel',
                    ]
                );
            }
        }

        $msg = "Proses impor selesai. {$importedCount} data murid baru ditambahkan, {$updatedCount} data diperbarui.";

        if (!empty($errors)) {
            return redirect()->route('students.index')
                ->with('success', $msg)
                ->with('import_errors', $errors);
        }

        return redirect()->route('students.index')->with('success', $msg);
    }
}

