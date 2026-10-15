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
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
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

        // Filter dropdowns: ClassLevels and Classrooms filtered by the active Jenjang tab & selected Academic Year
        $classLevels = ClassLevel::whereNotNull('jenjang_id')->where('is_active', true)
            ->when($selectedJenjangId && $selectedJenjangId !== 'all', fn($q) => $q->where('jenjang_id', $selectedJenjangId))
            ->when($selectedYearId && $selectedYearId !== 'all', function ($q) use ($selectedYearId) {
                $q->where(function ($sub) use ($selectedYearId) {
                    $sub->whereHas('classrooms', fn($cq) => $cq->where('academic_year_id', $selectedYearId))
                        ->orWhereHas('students', fn($sq) => $sq->where('academic_year_id', $selectedYearId));
                });
            })
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
        $allClassLevels = ClassLevel::with('jenjang')->whereNotNull('jenjang_id')->where('is_active', true)->orderBy('order')->get();
        $allClassrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true)
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('name')
            ->get();

        $daycareJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'DAYCARE')
              ->orWhere('code', 'TPA')
              ->orWhere('name', 'like', '%Daycare%')
              ->orWhere('name', 'like', '%TPA%');
        })->pluck('id');

        $daycareClassrooms = Classroom::with(['classLevel', 'academicYear', 'jenjang'])
            ->where('is_active', true)
            ->whereIn('jenjang_id', $daycareJenjangIds)
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();

        $tpqJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'TPQ')
              ->orWhere('name', 'like', '%TPQ%');
        })->pluck('id');

        $tpqClassrooms = Classroom::with(['classLevel', 'academicYear', 'jenjang'])
            ->where('is_active', true)
            ->whereIn('jenjang_id', $tpqJenjangIds)
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
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
            'jenjang',
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'classroom.jenjang',
            'daycareClassroom.homeroomTeacher',
            'tpqClassroom.homeroomTeacher',
            'classLevel.jenjang',
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
        // Normalize empty string foreign keys / dates / numbers to null
        $input = $request->all();
        $nullableKeys = [
            'jenjang_id', 'class_level_id', 'classroom_id', 'academic_year_id', 'daycare_classroom_id', 'tpq_classroom_id',
            'nis', 'nisn', 'nik', 'no_kk', 'no_akta', 'citizenship', 'birth_date', 'notes', 'parent_phone', 'parent_email',
            'father_name', 'father_phone', 'father_job', 'mother_name', 'mother_phone', 'mother_job', 'guardian_name', 'guardian_phone',
            'nickname', 'birth_place', 'religion', 'address', 'rt', 'rw', 'kelurahan', 'kecamatan', 'city', 'province', 'postal_code',
            'living_with', 'transportation', 'child_order', 'siblings_count', 'blood_type', 'special_needs',
            'height', 'weight', 'head_circumference', 'arm_circumference', 'distance_to_school', 'travel_time_minutes',
            'pin_access'
        ];
        foreach ($nullableKeys as $k) {
            if (isset($input[$k]) && ($input[$k] === '' || $input[$k] === 'null')) {
                $input[$k] = null;
            }
        }
        $request->merge($input);

        $validated = $request->validate([
            'nis' => 'nullable|string|max:50|unique:students,nis',
            'nisn' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'no_kk' => 'nullable|string|max:50',
            'no_akta' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|string|in:L,P,Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'living_with' => 'nullable|string|max:50',
            'transportation' => 'nullable|string|max:50',
            'child_order' => 'nullable|integer|min:1|max:30',
            'siblings_count' => 'nullable|integer|min:0|max:30',
            'blood_type' => 'nullable|string|max:10',
            'special_needs' => 'nullable|string|max:100',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:200',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'arm_circumference' => 'nullable|numeric|min:0|max:100',
            'distance_to_school' => 'nullable|string|max:50',
            'travel_time_minutes' => 'nullable|integer|min:0|max:300',
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
            'father_job' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'mother_job' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:50',
            'parent_phone' => 'nullable|string|max:50',
            'parent_email' => 'nullable|string|max:100',
            'pin_access' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'enrolled_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'family_data' => 'nullable|array',
            'health_habits' => 'nullable|array',
            'development_psychology' => 'nullable|array',
        ]);

        $validated['nis'] = !empty($validated['nis']) ? trim($validated['nis']) : null;

        // Synchronize legacy parent fields from family_data if provided
        if (!empty($validated['family_data'])) {
            $fd = $validated['family_data'];
            if (!empty($fd['father']['name']) && empty($validated['father_name'])) {
                $validated['father_name'] = $fd['father']['name'];
            }
            if (!empty($fd['father']['phone']) && empty($validated['father_phone'])) {
                $validated['father_phone'] = $fd['father']['phone'];
            }
            if (!empty($fd['father']['job']) && empty($validated['father_job'])) {
                $validated['father_job'] = $fd['father']['job'];
            }
            if (!empty($fd['mother']['name']) && empty($validated['mother_name'])) {
                $validated['mother_name'] = $fd['mother']['name'];
            }
            if (!empty($fd['mother']['phone']) && empty($validated['mother_phone'])) {
                $validated['mother_phone'] = $fd['mother']['phone'];
            }
            if (!empty($fd['mother']['job']) && empty($validated['mother_job'])) {
                $validated['mother_job'] = $fd['mother']['job'];
            }
            if (!empty($fd['guardian']['name']) && empty($validated['guardian_name'])) {
                $validated['guardian_name'] = $fd['guardian']['name'];
            }
            if (!empty($fd['guardian']['phone']) && empty($validated['guardian_phone'])) {
                $validated['guardian_phone'] = $fd['guardian']['phone'];
            }
        }

        if (empty($validated['parent_phone'])) {
            $validated['parent_phone'] = $validated['father_phone'] ?? $validated['mother_phone'] ?? $validated['guardian_phone'] ?? null;
        }

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

        // Normalize empty string foreign keys / dates / numbers to null
        $input = $request->all();
        $nullableKeys = [
            'jenjang_id', 'class_level_id', 'classroom_id', 'academic_year_id', 'daycare_classroom_id', 'tpq_classroom_id',
            'nis', 'nisn', 'nik', 'no_kk', 'no_akta', 'citizenship', 'birth_date', 'notes', 'parent_phone', 'parent_email',
            'father_name', 'father_phone', 'father_job', 'mother_name', 'mother_phone', 'mother_job', 'guardian_name', 'guardian_phone',
            'nickname', 'birth_place', 'religion', 'address', 'rt', 'rw', 'kelurahan', 'kecamatan', 'city', 'province', 'postal_code',
            'living_with', 'transportation', 'child_order', 'siblings_count', 'blood_type', 'special_needs',
            'height', 'weight', 'head_circumference', 'arm_circumference', 'distance_to_school', 'travel_time_minutes',
            'pin_access'
        ];
        foreach ($nullableKeys as $k) {
            if (isset($input[$k]) && ($input[$k] === '' || $input[$k] === 'null')) {
                $input[$k] = null;
            }
        }
        $request->merge($input);

        $validated = $request->validate([
            'nis' => 'nullable|string|max:50|unique:students,nis,' . $student->id,
            'nisn' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'no_kk' => 'nullable|string|max:50',
            'no_akta' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|string|in:L,P,Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'living_with' => 'nullable|string|max:50',
            'transportation' => 'nullable|string|max:50',
            'child_order' => 'nullable|integer|min:1|max:30',
            'siblings_count' => 'nullable|integer|min:0|max:30',
            'blood_type' => 'nullable|string|max:10',
            'special_needs' => 'nullable|string|max:100',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:200',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'arm_circumference' => 'nullable|numeric|min:0|max:100',
            'distance_to_school' => 'nullable|string|max:50',
            'travel_time_minutes' => 'nullable|integer|min:0|max:300',
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
            'father_job' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'mother_job' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:50',
            'parent_phone' => 'nullable|string|max:50',
            'parent_email' => 'nullable|string|max:100',
            'pin_access' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'notes' => 'nullable|string',
            'family_data' => 'nullable|array',
            'health_habits' => 'nullable|array',
            'development_psychology' => 'nullable|array',
        ]);

        $validated['nis'] = !empty($validated['nis']) ? trim($validated['nis']) : null;

        // Synchronize legacy parent fields from family_data if provided
        if (!empty($validated['family_data'])) {
            $fd = $validated['family_data'];
            if (!empty($fd['father']['name']) && empty($validated['father_name'])) {
                $validated['father_name'] = $fd['father']['name'];
            }
            if (!empty($fd['father']['phone']) && empty($validated['father_phone'])) {
                $validated['father_phone'] = $fd['father']['phone'];
            }
            if (!empty($fd['father']['job']) && empty($validated['father_job'])) {
                $validated['father_job'] = $fd['father']['job'];
            }
            if (!empty($fd['mother']['name']) && empty($validated['mother_name'])) {
                $validated['mother_name'] = $fd['mother']['name'];
            }
            if (!empty($fd['mother']['phone']) && empty($validated['mother_phone'])) {
                $validated['mother_phone'] = $fd['mother']['phone'];
            }
            if (!empty($fd['mother']['job']) && empty($validated['mother_job'])) {
                $validated['mother_job'] = $fd['mother']['job'];
            }
            if (!empty($fd['guardian']['name']) && empty($validated['guardian_name'])) {
                $validated['guardian_name'] = $fd['guardian']['name'];
            }
            if (!empty($fd['guardian']['phone']) && empty($validated['guardian_phone'])) {
                $validated['guardian_phone'] = $fd['guardian']['phone'];
            }
        }

        if (empty($validated['parent_phone'])) {
            $validated['parent_phone'] = $validated['father_phone'] ?? $validated['mother_phone'] ?? $validated['guardian_phone'] ?? $student->parent_phone;
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
     * Helper to get filtered students query for export.
     */
    protected function getFilteredStudentsQuery(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedJenjangId = $request->get('jenjang_id');

        $query = Student::with([
            'jenjang',
            'classroom.classLevel',
            'classroom.jenjang',
            'classroom.homeroomTeacher',
            'daycareClassroom',
            'tpqClassroom',
            'classLevel',
            'academicYear',
            'spmbCandidate',
        ]);

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

        return $query->orderBy('classroom_id', 'asc')->orderBy('full_name', 'asc');
    }

    /**
     * Resolve document export metadata, dynamic titles, and filenames based on active filters.
     */
    protected function resolveExportMeta(Request $request, $students)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $selectedYearId === 'all' ? null : ($academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear);

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id');
        $selectedJenjang = ($selectedJenjangId && $selectedJenjangId !== 'all') ? $jenjangs->firstWhere('id', $selectedJenjangId) : null;

        $selectedClassLevel = null;
        if ($clId = $request->get('class_level_id')) {
            if ($clId !== 'all') {
                $selectedClassLevel = ClassLevel::find($clId);
            }
        }

        $selectedClassroom = null;
        if ($crId = $request->get('classroom_id')) {
            if ($crId !== 'all') {
                $selectedClassroom = Classroom::find($crId);
            }
        }

        $selectedStatus = $request->get('status');
        $selectedGender = $request->get('gender');

        // Dynamic Document Title based on active filters
        $titleParts = ['DAFTAR DATA LENGKAP MURID'];
        $fileContextParts = ['Data_Lengkap_Murid'];

        if ($selectedClassroom) {
            $titleParts[] = 'KELOMPOK ' . strtoupper($selectedClassroom->name);
            $fileContextParts[] = str_replace([' ', '/', '\\'], '_', $selectedClassroom->name);
        } elseif ($selectedClassLevel) {
            $titleParts[] = 'KELAS ' . strtoupper($selectedClassLevel->name);
            $fileContextParts[] = str_replace([' ', '/', '\\'], '_', $selectedClassLevel->name);
        } elseif ($selectedJenjang) {
            $titleParts[] = strtoupper($selectedJenjang->name);
            $fileContextParts[] = str_replace([' ', '/', '\\'], '_', $selectedJenjang->name);
        } else {
            $titleParts[] = 'SEMUA JENJANG';
            $fileContextParts[] = 'Semua_Jenjang';
        }

        if ($selectedGender && $selectedGender !== 'all') {
            $isL = in_array(strtoupper($selectedGender), ['L', 'LAKI-LAKI']);
            $titleParts[] = $isL ? '(PUTRA)' : '(PUTRI)';
            $fileContextParts[] = $isL ? 'Putra' : 'Putri';
        }

        if ($selectedStatus && !in_array($selectedStatus, ['all', 'aktif'])) {
            $titleParts[] = '(' . strtoupper($selectedStatus) . ')';
            $fileContextParts[] = ucfirst($selectedStatus);
        }

        $mainTitle = implode(' - ', $titleParts);

        // Filter Details Array for meta info
        $filterDetails = [];
        if ($selectedClassLevel) {
            $filterDetails['Kelas'] = $selectedClassLevel->name;
        }
        if ($selectedClassroom) {
            $filterDetails['Kelompok'] = $selectedClassroom->name;
        }
        if ($selectedStatus && $selectedStatus !== 'all') {
            $filterDetails['Status'] = ucfirst($selectedStatus);
        }
        if ($selectedGender && $selectedGender !== 'all') {
            $filterDetails['Gender'] = in_array(strtoupper($selectedGender), ['L', 'LAKI-LAKI']) ? 'Putra' : 'Putri';
        }

        $maleCount = $students->where('gender', 'L')->count();
        $femaleCount = $students->where('gender', 'P')->count();
        $totalCount = $students->count();

        // Subtitle text for Excel
        $excelSubtitleParts = [];
        $excelSubtitleParts[] = 'Tahun Ajaran: ' . ($selectedYear ? $selectedYear->name : 'Semua T.A.');
        $excelSubtitleParts[] = 'Jenjang: ' . ($selectedJenjang ? $selectedJenjang->name : 'Semua Jenjang');
        foreach ($filterDetails as $k => $v) {
            $excelSubtitleParts[] = "{$k}: {$v}";
        }
        $excelSubtitleParts[] = "Total: {$totalCount} Murid (L: {$maleCount}, P: {$femaleCount})";
        $excelSubtitleParts[] = "Tanggal Unduh: " . date('d/m/Y H:i') . " WIB";
        $excelSubtitle = implode(' | ', $excelSubtitleParts);

        $filePrefix = implode('_', $fileContextParts) . '_' . date('Ymd_His');

        return [
            'mainTitle' => $mainTitle,
            'institution' => 'KB - TK - DAYCARE - TPQ ISLAM ANAK SALEH MALANG',
            'excelSubtitle' => $excelSubtitle,
            'selectedYear' => $selectedYear,
            'selectedJenjang' => $selectedJenjang,
            'selectedClassLevel' => $selectedClassLevel,
            'selectedClassroom' => $selectedClassroom,
            'filterDetails' => $filterDetails,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'totalCount' => $totalCount,
            'filePrefix' => $filePrefix,
        ];
    }

    /**
     * Export students data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $students = $this->getFilteredStudentsQuery($request)->get();
        $meta = $this->resolveExportMeta($request, $students);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Murid');

        // CATEGORY HEADER (ROW 4) - Reordered with Biodata & Identitas first
        $categories = [
            ['range' => 'A4:O4', 'title' => '1. BIODATA & IDENTITAS UTAMA', 'color' => 'FF0284C7'], // Sky 600
            ['range' => 'P4:AA4', 'title' => '2. AKADEMIK & PENEMPATAN KELAS', 'color' => 'FF4F46E5'], // Indigo 600
            ['range' => 'AB4:AM4', 'title' => '3. DOMISILI & TRANSPORTASI', 'color' => 'FF0D9488'], // Teal 600
            ['range' => 'AN4:BE4', 'title' => '4. DATA ORANG TUA & WALI', 'color' => 'FF059669'], // Emerald 600
            ['range' => 'BF4:BO4', 'title' => '5. FISIK & KESEHATAN', 'color' => 'FFD97706'], // Amber 600
            ['range' => 'BP4:BQ4', 'title' => '6. AKUN & CATATAN KHUSUS', 'color' => 'FF475569'], // Slate 600
        ];

        // Document Title Banner - Merged across full category width (A1:BQ1, A2:BQ2, A3:BQ3)
        // Merging prevents truncation at the freeze pane boundary (Column C) while keeping clean layout
        $lastCategory = end($categories);
        $lastCol = explode(':', $lastCategory['range'])[1];
        $lastCol = preg_replace('/[0-9]/', '', $lastCol) ?: 'BQ';

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");

        $sheet->setCellValue('A1', $meta['mainTitle']);
        $sheet->setCellValue('A2', $meta['institution']);
        $sheet->setCellValue('A3', $meta['excelSubtitle']);

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E1B4B'));
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true)->setSize(10.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF4338CA'));
        $sheet->getStyle("A3:{$lastCol}3")->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

        $sheet->getStyle("A1:{$lastCol}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        foreach ($categories as $cat) {
            $sheet->mergeCells($cat['range']);
            $firstCell = explode(':', $cat['range'])[0];
            $sheet->setCellValue($firstCell, $cat['title']);
            $sheet->getStyle($cat['range'])->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($cat['range'])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($cat['color']);
            $sheet->getStyle($cat['range'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        // Table Headers (Comprehensive - 69 Columns) - Reordered with Biodata & Identitas first
        $headers = [
            // 1-15: Biodata & Identitas Utama
            'No',
            'Nama Lengkap',
            'Nama Panggilan',
            'L/P',
            'NIS',
            'NISN',
            'NIK Ananda',
            'No. KK',
            'No. Akta Kelahiran',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Usia',
            'Agama',
            'Kewarganegaraan',
            'Asal Sekolah Sebelumnya',

            // 16-27: Penempatan Kelas & Akademik
            'Status Murid',
            'Tahun Ajaran',
            'Sub Unit',
            'Jenjang',
            'Tingkat Kelas',
            'Kelompok Belajar',
            'Wali Kelas',
            'Kelompok Daycare',
            'Status TPQ',
            'Kelompok TPQ',
            'Tanggal Masuk / Diterima',
            'No. SPMB',

            // 28-39: Domisili & Transportasi
            'Alamat Jalan',
            'RT',
            'RW',
            'Kelurahan / Desa',
            'Kecamatan',
            'Kota / Kab',
            'Provinsi',
            'Kode Pos',
            'Tempat Tinggal',
            'Moda Transportasi',
            'Jarak Sekolah',
            'Waktu Tempuh (menit)',

            // 40-57: Data Orang Tua & Wali
            'Nama Ayah',
            'NIK Ayah',
            'Pendidikan Ayah',
            'Pekerjaan Ayah',
            'Penghasilan Ayah',
            'No. HP Ayah',
            'Nama Ibu',
            'NIK Ibu',
            'Pendidikan Ibu',
            'Pekerjaan Ibu',
            'Penghasilan Ibu',
            'No. HP Ibu',
            'Nama Wali',
            'Hubungan Wali',
            'Pekerjaan Wali',
            'No. HP Wali',
            'No. WhatsApp Kontak Utama',
            'Email Orang Tua',

            // 58-67: Fisik & Kesehatan
            'Anak Ke-',
            'Jumlah Saudara',
            'Gol. Darah',
            'Kebutuhan Khusus',
            'TB (cm)',
            'BB (kg)',
            'LK (cm)',
            'LiLa (cm)',
            'Riwayat Alergi & Penyakit',
            'Kebiasaan Makan & Toilet Training',

            // 68-69: Akun & Catatan Khusus
            'PIN Akses Ortu',
            'Catatan Khusus'
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
            ->getStartColor()->setARGB('FF334155'); // Slate 700
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(26);

        // Populate Data Rows
        $currentRow = $startRow + 1;
        foreach ($students as $index => $s) {
            $genderLabel = in_array(strtoupper((string)$s->gender), ['L', 'LAKI-LAKI', 'MALE']) ? 'L' : 'P';
            $birthDateFormatted = $s->birth_date ? date('Y-m-d', strtotime($s->birth_date)) : '-';
            $jenjangName = $s->jenjang?->name ?? $s->classroom?->jenjang?->name ?? $s->classLevel?->jenjang?->name ?? $s->sub_unit ?? '-';
            $classLevelName = $s->classroom?->classLevel?->name ?? $s->classLevel?->name ?? '-';
            $rombelName = $s->classroom?->name ?? '-';
            $homeroomName = $s->classroom?->homeroomTeacher?->name ?? '-';
            $daycareName = $s->daycareClassroom?->name ?? '-';
            $tpqStatus = ($s->is_tpq || $s->tpq_classroom_id || $s->sub_unit === 'TPQ') ? 'Ya' : 'Tidak';
            $tpqName = $s->tpqClassroom?->name ?? ($tpqStatus === 'Ya' ? 'TPQ' : '-');
            $spmbNumber = $s->spmbCandidate?->registration_number ?? ($s->spmb_candidate_id ? '#' . $s->spmb_candidate_id : '-');
            $enrolledDateFormatted = $s->enrolled_date ? date('Y-m-d', strtotime($s->enrolled_date)) : '-';

            $fd = is_array($s->family_data) ? $s->family_data : [];
            $fatherName = $s->father_name ?? ($fd['father']['name'] ?? '-');
            $fatherNik = $fd['father']['nik'] ?? '-';
            $fatherEdu = $fd['father']['education'] ?? '-';
            $fatherJob = $s->father_job ?? ($fd['father']['job'] ?? '-');
            $fatherIncome = $fd['father']['income'] ?? '-';
            $fatherPhone = $s->father_phone ?? ($fd['father']['phone'] ?? '');

            $motherName = $s->mother_name ?? ($fd['mother']['name'] ?? '-');
            $motherNik = $fd['mother']['nik'] ?? '-';
            $motherEdu = $fd['mother']['education'] ?? '-';
            $motherJob = $s->mother_job ?? ($fd['mother']['job'] ?? '-');
            $motherIncome = $fd['mother']['income'] ?? '-';
            $motherPhone = $s->mother_phone ?? ($fd['mother']['phone'] ?? '');

            $guardianName = $s->guardian_name ?? ($fd['guardian']['name'] ?? '-');
            $guardianRelation = $fd['guardian']['relation'] ?? ($s->guardian_name ? 'Wali' : '-');
            $guardianJob = $fd['guardian']['job'] ?? '-';
            $guardianPhone = $s->guardian_phone ?? ($fd['guardian']['phone'] ?? '');

            $hh = is_array($s->health_habits) ? $s->health_habits : [];
            $allergy = ($hh['allergy']['has_allergies'] ?? false) ? ($hh['allergy']['types'] ?? 'Ada Alergi') : 'Tidak ada';
            $illness = !empty($hh['illness_history']) ? $hh['illness_history'] : '';
            $healthNotes = trim(($allergy !== 'Tidak ada' ? "Alergi: {$allergy}. " : '') . ($illness ? "Penyakit: {$illness}" : ''));
            if (empty($healthNotes)) $healthNotes = ($allergy === 'Tidak ada' ? 'Tidak ada riwayat alergi/penyakit' : '-');

            $habits = [];
            if (!empty($hh['diet']['eating_morning']) || !empty($hh['diet']['eating_afternoon'])) {
                $habits[] = 'Makan: ' . ($hh['diet']['eating_morning'] ?? '-') . '/' . ($hh['diet']['eating_afternoon'] ?? '-');
            }
            if (!empty($hh['habits']['toilet_training'])) {
                $habits[] = 'Toilet: ' . $hh['habits']['toilet_training'];
            }
            $habitsStr = !empty($habits) ? implode('; ', $habits) : '-';

            // 1-15: Biodata & Identitas Utama
            $sheet->setCellValue("A{$currentRow}", $index + 1);
            $sheet->setCellValue("B{$currentRow}", $s->full_name);
            $sheet->setCellValue("C{$currentRow}", $s->nickname ?? '-');
            $sheet->setCellValue("D{$currentRow}", $genderLabel);
            $sheet->setCellValueExplicit("E{$currentRow}", (string)($s->nis ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$currentRow}", (string)($s->nisn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$currentRow}", (string)($s->nik ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$currentRow}", (string)($s->no_kk ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$currentRow}", (string)($s->no_akta ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("J{$currentRow}", $s->birth_place ?? '-');
            $sheet->setCellValue("K{$currentRow}", $birthDateFormatted);
            $sheet->setCellValue("L{$currentRow}", $s->age ?? '-');
            $sheet->setCellValue("M{$currentRow}", $s->religion ?? 'Islam');
            $sheet->setCellValue("N{$currentRow}", $s->citizenship ?? 'WNI');
            $sheet->setCellValue("O{$currentRow}", $s->previous_school ?? '-');

            // 16-27: Penempatan Kelas & Akademik
            $sheet->setCellValue("P{$currentRow}", ucfirst($s->status ?? 'aktif'));
            $sheet->setCellValue("Q{$currentRow}", $s->academicYear?->name ?? ($selectedYear?->name ?? '-'));
            $sheet->setCellValue("R{$currentRow}", $s->sub_unit ?? 'TK');
            $sheet->setCellValue("S{$currentRow}", $jenjangName);
            $sheet->setCellValue("T{$currentRow}", $classLevelName);
            $sheet->setCellValue("U{$currentRow}", $rombelName);
            $sheet->setCellValue("V{$currentRow}", $homeroomName);
            $sheet->setCellValue("W{$currentRow}", $daycareName);
            $sheet->setCellValue("X{$currentRow}", $tpqStatus);
            $sheet->setCellValue("Y{$currentRow}", $tpqName);
            $sheet->setCellValue("Z{$currentRow}", $enrolledDateFormatted);
            $sheet->setCellValueExplicit("AA{$currentRow}", (string)$spmbNumber, DataType::TYPE_STRING);

            // 28-39: Domisili & Transportasi
            $sheet->setCellValue("AB{$currentRow}", $s->address ?? '-');
            $sheet->setCellValue("AC{$currentRow}", $s->rt ?? '-');
            $sheet->setCellValue("AD{$currentRow}", $s->rw ?? '-');
            $sheet->setCellValue("AE{$currentRow}", $s->kelurahan ?? '-');
            $sheet->setCellValue("AF{$currentRow}", $s->kecamatan ?? '-');
            $sheet->setCellValue("AG{$currentRow}", $s->city ?? 'Malang');
            $sheet->setCellValue("AH{$currentRow}", $s->province ?? 'Jawa Timur');
            $sheet->setCellValue("AI{$currentRow}", $s->postal_code ?? '-');
            $sheet->setCellValue("AJ{$currentRow}", $s->living_with ?? 'Bersama orang tua');
            $sheet->setCellValue("AK{$currentRow}", $s->transportation ?? '-');
            $sheet->setCellValue("AL{$currentRow}", $s->distance_to_school ?? '-');
            $sheet->setCellValue("AM{$currentRow}", $s->travel_time_minutes ?? '-');

            // 40-57: Data Orang Tua & Wali
            $sheet->setCellValue("AN{$currentRow}", $fatherName);
            $sheet->setCellValueExplicit("AO{$currentRow}", (string)$fatherNik, DataType::TYPE_STRING);
            $sheet->setCellValue("AP{$currentRow}", $fatherEdu);
            $sheet->setCellValue("AQ{$currentRow}", $fatherJob);
            $sheet->setCellValue("AR{$currentRow}", $fatherIncome);
            $sheet->setCellValueExplicit("AS{$currentRow}", (string)$fatherPhone, DataType::TYPE_STRING);
            $sheet->setCellValue("AT{$currentRow}", $motherName);
            $sheet->setCellValueExplicit("AU{$currentRow}", (string)$motherNik, DataType::TYPE_STRING);
            $sheet->setCellValue("AV{$currentRow}", $motherEdu);
            $sheet->setCellValue("AW{$currentRow}", $motherJob);
            $sheet->setCellValue("AX{$currentRow}", $motherIncome);
            $sheet->setCellValueExplicit("AY{$currentRow}", (string)$motherPhone, DataType::TYPE_STRING);
            $sheet->setCellValue("AZ{$currentRow}", $guardianName);
            $sheet->setCellValue("BA{$currentRow}", $guardianRelation);
            $sheet->setCellValue("BB{$currentRow}", $guardianJob);
            $sheet->setCellValueExplicit("BC{$currentRow}", (string)$guardianPhone, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("BD{$currentRow}", (string)($s->clean_parent_phone ?? $s->parent_phone ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("BE{$currentRow}", $s->parent_email ?? ($fd['parent_email'] ?? '-'));

            // 58-67: Fisik & Kesehatan
            $sheet->setCellValue("BF{$currentRow}", $s->child_order ?? '-');
            $sheet->setCellValue("BG{$currentRow}", $s->siblings_count ?? '-');
            $sheet->setCellValue("BH{$currentRow}", $s->blood_type ?? '-');
            $sheet->setCellValue("BI{$currentRow}", $s->special_needs ?? 'Tidak ada');
            $sheet->setCellValue("BJ{$currentRow}", $s->height ?? '-');
            $sheet->setCellValue("BK{$currentRow}", $s->weight ?? '-');
            $sheet->setCellValue("BL{$currentRow}", $s->head_circumference ?? '-');
            $sheet->setCellValue("BM{$currentRow}", $s->arm_circumference ?? '-');
            $sheet->setCellValue("BN{$currentRow}", $healthNotes);
            $sheet->setCellValue("BO{$currentRow}", $habitsStr);

            // 68-69: Akun & Catatan Khusus
            $sheet->setCellValueExplicit("BP{$currentRow}", (string)($s->pin_access ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("BQ{$currentRow}", $s->notes ?? '-');

            // Alignment
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$currentRow}:N{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("P{$currentRow}:T{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("X{$currentRow}:AA{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AC{$currentRow}:AD{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AI{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AL{$currentRow}:AM{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AO{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AS{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AU{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AY{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BC{$currentRow}:BD{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BF{$currentRow}:BM{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BP{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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
        $sheet->getStyle("A4:{$lastCol}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE2E8F0'));

        // Freeze pane on column C: Freezes Column A (No) and Column B (Nama Lengkap) & Rows 1-5
        // Allows smooth horizontal scrolling across the remaining 67 columns while keeping student name visible!
        $sheet->freezePane('C6');

        // Column A: FIXED WIDTH (No) -> compact and prevented from auto-sizing against the document banner
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(7);

        // Columns B through BQ: Auto-fit column widths
        foreach (range(2, count($headers)) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $meta['filePrefix'] . '.xlsx';

        if ($request->filled('download_token')) {
            setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
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
     * Export students data to PDF.
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $students = $this->getFilteredStudentsQuery($request)->get();
        $meta = $this->resolveExportMeta($request, $students);
        $fileName = $meta['filePrefix'] . '.pdf';

        if ($request->filled('download_token')) {
            setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.students.pdf', [
            'students' => $students,
            'selectedYear' => $meta['selectedYear'],
            'selectedJenjang' => $meta['selectedJenjang'],
            'filterDetails' => $meta['filterDetails'],
            'mainTitle' => $meta['mainTitle'],
            'meta' => $meta,
        ])->setPaper('a4', 'landscape');

        $response = $pdf->download($fileName);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Download Excel template for importing students with grouped categories and validations.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Murid');

        // CATEGORY HEADER (ROW 1)
        $categories = [
            ['range' => 'A1:E1', 'title' => '1. IDENTITAS & DOKUMEN RESMI', 'color' => 'FF4F46E5'], // Indigo 600
            ['range' => 'F1:L1', 'title' => '2. BIODATA UTAMA ANANDA', 'color' => 'FF0284C7'],      // Sky 600
            ['range' => 'M1:U1', 'title' => '3. DOMISILI & TEMPAT TINGGAL', 'color' => 'FF0D9488'],  // Teal 600
            ['range' => 'V1:AE1', 'title' => '4. FISIK & ANTROPOMETRI', 'color' => 'FFD97706'],     // Amber 600
            ['range' => 'AF1:AK1', 'title' => '5. PENEMPATAN KELAS & PROGRAM', 'color' => 'FF7C3AED'], // Violet 600
            ['range' => 'AL1:AV1', 'title' => '6. DATA ORANG TUA & WALI', 'color' => 'FF059669'],    // Emerald 600
            ['range' => 'AW1:AZ1', 'title' => '7. STATUS & CATATAN KHUSUS', 'color' => 'FF475569'],  // Slate 600
        ];

        foreach ($categories as $cat) {
            $sheet->mergeCells($cat['range']);
            $firstCell = explode(':', $cat['range'])[0];
            $sheet->setCellValue($firstCell, $cat['title']);
            $sheet->getStyle($cat['range'])->getFont()->setBold(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($cat['range'])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($cat['color']);
            $sheet->getStyle($cat['range'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);

        // COLUMN HEADERS (ROW 2)
        $headers = [
            'NIS (opsional)',
            'NISN (opsional)',
            'NIK Ananda (opsional)',
            'No. KK (opsional)',
            'No. Akta Kelahiran (opsional)',
            'Nama Lengkap Ananda *',
            'Nama Panggilan',
            'Jenis Kelamin (L/P) *',
            'Tempat Lahir',
            'Tanggal Lahir (YYYY-MM-DD)',
            'Agama (Islam/Kristen/dll)',
            'Kewarganegaraan (WNI/WNA)',
            'Alamat Jalan',
            'RT',
            'RW',
            'Kelurahan / Desa',
            'Kecamatan',
            'Kota / Kabupaten',
            'Kode Pos',
            'Tempat Tinggal',
            'Moda Transportasi',
            'Anak Ke-',
            'Jumlah Saudara',
            'Golongan Darah (A/B/AB/O/Tidak Tahu)',
            'Kebutuhan Khusus (opsional)',
            'Tinggi Badan (cm)',
            'Berat Badan (kg)',
            'Lingkar Kepala (cm)',
            'Lingkar Lengan (cm)',
            'Jarak ke Sekolah',
            'Waktu Tempuh (menit)',
            'Sub Unit (PG/TK/DAYCARE/TPQ)',
            'Jenjang (KB-A/KB-B/TK-A/TK-B/Daycare/TPQ)',
            'Nama Kelompok / Rombel',
            'Kelompok Daycare (opsional)',
            'Ikut TPQ (Ya/Tidak)',
            'Kelompok TPQ (opsional)',
            'Nama Ayah',
            'NIK Ayah',
            'Pendidikan Ayah',
            'Pekerjaan Ayah',
            'No HP Ayah',
            'Nama Ibu',
            'NIK Ibu',
            'Pendidikan Ibu',
            'Pekerjaan Ibu',
            'No HP Ibu',
            'No WhatsApp Kontak Utama *',
            'PIN Akses Wali (default: 1234)',
            'Status (aktif/lulus/mutasi/keluar/nonaktif)',
            'Riwayat Alergi (opsional)',
            'Catatan Tambahan (opsional)'
        ];

        foreach ($headers as $colIndex => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '2', $header);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A2:{$lastCol}2";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9'); // Slate 100
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(32);

        // EXAMPLE DATA ROWS (ROW 3 & 4)
        $example1 = [
            '0101', '0192837465', '3573010101210001', '3573010101210001', '12345/DIS/2021',
            'Muhammad Fatih Al-Faruq', 'Fatih', 'L', 'Malang', '2021-03-15', 'Islam', 'WNI',
            'Jl. Candi Mendut No. 12', '03', '05', 'Mojolangu', 'Lowokwaru', 'Malang', '65142',
            'Bersama orang tua', 'Mobil pribadi', '1', '2', 'O', 'Tidak ada',
            '102.5', '16.5', '50.0', '16.0', '1-3 km', '15',
            'TK', 'TK-A', 'TK A-1 (Bintang)', 'Daycare Toddler', 'Ya', 'TPQ Al-Fatih',
            'Ahmad Fauzi, S.T.', '3573010101850001', 'S1', 'Karyawan Swasta', '081234567890',
            'Siti Aminah, S.Pd.', '3573010101880002', 'S1', 'Guru', '081234567891',
            '081234567890', '1234', 'aktif', 'Tidak ada alergi', 'Ananda aktif dan ramah'
        ];

        $example2 = [
            '0102', '0192837466', '3573010101220002', '3573010101220002', '12346/DIS/2022',
            'Aisyah Humaira Putri', 'Aisyah', 'P', 'Malang', '2022-06-20', 'Islam', 'WNI',
            'Jl. Soekarno Hatta No. 45', '02', '01', 'Jatimulyo', 'Lowokwaru', 'Malang', '65141',
            'Bersama orang tua', 'Motor', '2', '1', 'A', 'Tidak ada',
            '92.0', '13.2', '47.5', '14.5', '< 1 km', '10',
            'PG', 'KB-A', 'KB Bulan', '', 'Tidak', '',
            'Bambang Susanto, M.M.', '3573010101820003', 'S2', 'Wiraswasta', '082198765432',
            'Nurul Hidayah, S.E.', '3573010101860004', 'S1', 'Ibu Rumah Tangga', '082198765433',
            '082198765432', '5678', 'aktif', 'Alergi dingin/seafood ringan', 'Ananda ceria dan suka menggambar'
        ];

        foreach ($example1 as $colIndex => $val) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValueExplicit($colLetter . '3', (string)$val, DataType::TYPE_STRING);
        }

        foreach ($example2 as $colIndex => $val) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValueExplicit($colLetter . '4', (string)$val, DataType::TYPE_STRING);
        }

        $sheet->getStyle("A3:{$lastCol}4")->getFont()->setSize(9);
        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(22);

        // Apply Borders
        $sheet->getStyle("A1:{$lastCol}4")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        // Setup Dropdown Data Validations for standard columns (applied on rows 3-200)
        $dropdownConfigs = [
            'H' => '"L,P"', // Gender
            'L' => '"WNI,WNA"', // Citizenship
            'X' => '"A,B,AB,O,Tidak Tahu"', // Blood type
            'AF' => '"PG,TK,DAYCARE,TPQ"', // Sub Unit
            'AJ' => '"Ya,Tidak"', // Ikut TPQ
            'AX' => '"aktif,lulus,mutasi,keluar,nonaktif"', // Status
        ];

        foreach ($dropdownConfigs as $col => $formula) {
            for ($r = 3; $r <= 100; $r++) {
                $validation = $sheet->getCell("{$col}{$r}")->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(false);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input Tidak Valid');
                $validation->setError('Silakan pilih salah satu opsi dari menu dropdown.');
                $validation->setFormula1($formula);
            }
        }

        // Freeze Panes
        $sheet->freezePane('G3');

        // Auto-size columns
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        if (request()->filled('download_token')) {
            setcookie('download_token', request()->query('download_token'), time() + 60, '/', '', false, false);
        }

        $writer = new Xlsx($spreadsheet);

        $response = response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Template_Impor_Murid_PAUD_Lengkap.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);

        if (request()->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', request()->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Import students from Excel spreadsheet with intelligent column detection & upsert.
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
        $rawRows = $sheet->toArray(null, true, false, false);

        if (count($rawRows) < 2) {
            return redirect()->back()->with('error', 'File Excel kosong atau tidak memiliki data murid.');
        }

        // Detect Header Row & Data Rows
        $headerRowIdx = 0;
        foreach (array_slice($rawRows, 0, 3) as $idx => $r) {
            $rowString = strtolower(implode(' ', array_filter(array_map('strval', $r))));
            if (str_contains($rowString, 'nama lengkap') || (str_contains($rowString, 'nama') && str_contains($rowString, 'nis'))) {
                $headerRowIdx = $idx;
                break;
            }
        }

        $headers = $rawRows[$headerRowIdx];
        $dataRows = array_slice($rawRows, $headerRowIdx + 1);

        // Build dynamic header mapper
        $colMap = [];
        foreach ($headers as $cIdx => $hText) {
            $h = strtolower(trim((string)$hText));
            if ($h === '') continue;

            if (str_contains($h, 'nisn')) $colMap['nisn'] = $cIdx;
            elseif (str_contains($h, 'kelamin') || str_contains($h, 'l/p') || str_contains($h, 'gender') || $h === 'jk') $colMap['gender'] = $cIdx;
            elseif ((str_contains($h, 'nis') || str_contains($h, 'nomor induk')) && !str_contains($h, 'nisn') && !str_contains($h, 'jenis') && !str_contains($h, 'teknis')) $colMap['nis'] = $cIdx;
            elseif (str_contains($h, 'nik ananda') || str_contains($h, 'nik siswa') || ($h === 'nik') || (str_contains($h, 'nik') && !str_contains($h, 'ayah') && !str_contains($h, 'ibu') && !str_contains($h, 'wali'))) $colMap['nik'] = $cIdx;
            elseif (str_contains($h, 'kartu keluarga') || str_contains($h, 'no. kk') || str_contains($h, 'nomor kk') || $h === 'kk') $colMap['no_kk'] = $cIdx;
            elseif (str_contains($h, 'akta')) $colMap['no_akta'] = $cIdx;
            elseif (str_contains($h, 'nama lengkap') || (str_contains($h, 'nama') && (str_contains($h, 'ananda') || str_contains($h, 'siswa')))) $colMap['full_name'] = $cIdx;
            elseif (str_contains($h, 'panggilan')) $colMap['nickname'] = $cIdx;
            elseif (str_contains($h, 'tempat lahir')) $colMap['birth_place'] = $cIdx;
            elseif (str_contains($h, 'tanggal lahir') || str_contains($h, 'tgl lahir')) $colMap['birth_date'] = $cIdx;
            elseif (str_contains($h, 'agama')) $colMap['religion'] = $cIdx;
            elseif (str_contains($h, 'kewarganegaraan') || str_contains($h, 'wni')) $colMap['citizenship'] = $cIdx;
            elseif (str_contains($h, 'alamat')) $colMap['address'] = $cIdx;
            elseif ($h === 'rt') $colMap['rt'] = $cIdx;
            elseif ($h === 'rw') $colMap['rw'] = $cIdx;
            elseif (str_contains($h, 'kelurahan') || str_contains($h, 'desa')) $colMap['kelurahan'] = $cIdx;
            elseif (str_contains($h, 'kecamatan')) $colMap['kecamatan'] = $cIdx;
            elseif (str_contains($h, 'kota') || str_contains($h, 'kabupaten')) $colMap['city'] = $cIdx;
            elseif (str_contains($h, 'kode pos') || str_contains($h, 'kodepos')) $colMap['postal_code'] = $cIdx;
            elseif (str_contains($h, 'tempat tinggal')) $colMap['living_with'] = $cIdx;
            elseif (str_contains($h, 'transportasi')) $colMap['transportation'] = $cIdx;
            elseif (str_contains($h, 'anak ke')) $colMap['child_order'] = $cIdx;
            elseif (str_contains($h, 'saudara')) $colMap['siblings_count'] = $cIdx;
            elseif (str_contains($h, 'darah')) $colMap['blood_type'] = $cIdx;
            elseif (str_contains($h, 'kebutuhan khusus') || str_contains($h, 'disabilitas')) $colMap['special_needs'] = $cIdx;
            elseif (str_contains($h, 'tinggi') || $h === 'tb') $colMap['height'] = $cIdx;
            elseif (str_contains($h, 'berat') || $h === 'bb') $colMap['weight'] = $cIdx;
            elseif (str_contains($h, 'lingkar kepala') || $h === 'lk') $colMap['head_circumference'] = $cIdx;
            elseif (str_contains($h, 'lingkar lengan') || str_contains($h, 'lila')) $colMap['arm_circumference'] = $cIdx;
            elseif (str_contains($h, 'jarak')) $colMap['distance_to_school'] = $cIdx;
            elseif (str_contains($h, 'waktu tempuh')) $colMap['travel_time_minutes'] = $cIdx;
            elseif (str_contains($h, 'sub unit')) $colMap['sub_unit'] = $cIdx;
            elseif (str_contains($h, 'jenjang')) $colMap['jenjang'] = $cIdx;
            elseif (str_contains($h, 'daycare')) $colMap['daycare'] = $cIdx;
            elseif (str_contains($h, 'ikut tpq')) $colMap['is_tpq'] = $cIdx;
            elseif (str_contains($h, 'kelompok tpq')) $colMap['tpq_rombel'] = $cIdx;
            elseif (str_contains($h, 'kelompok') || str_contains($h, 'rombel')) $colMap['classroom'] = $cIdx;
            elseif (str_contains($h, 'nama ayah') || ($h === 'ayah')) $colMap['father_name'] = $cIdx;
            elseif (str_contains($h, 'nik ayah')) $colMap['father_nik'] = $cIdx;
            elseif (str_contains($h, 'pendidikan ayah')) $colMap['father_education'] = $cIdx;
            elseif (str_contains($h, 'pekerjaan ayah')) $colMap['father_job'] = $cIdx;
            elseif (str_contains($h, 'hp ayah') || str_contains($h, 'telepon ayah')) $colMap['father_phone'] = $cIdx;
            elseif (str_contains($h, 'nama ibu') || ($h === 'ibu')) $colMap['mother_name'] = $cIdx;
            elseif (str_contains($h, 'nik ibu')) $colMap['mother_nik'] = $cIdx;
            elseif (str_contains($h, 'pendidikan ibu')) $colMap['mother_education'] = $cIdx;
            elseif (str_contains($h, 'pekerjaan ibu')) $colMap['mother_job'] = $cIdx;
            elseif (str_contains($h, 'hp ibu') || str_contains($h, 'telepon ibu')) $colMap['mother_phone'] = $cIdx;
            elseif (str_contains($h, 'whatsapp') || str_contains($h, 'kontak') || str_contains($h, 'wa')) $colMap['parent_phone'] = $cIdx;
            elseif (str_contains($h, 'pin')) $colMap['pin_access'] = $cIdx;
            elseif (str_contains($h, 'status')) $colMap['status'] = $cIdx;
            elseif (str_contains($h, 'alergi')) $colMap['allergy'] = $cIdx;
            elseif (str_contains($h, 'catatan')) $colMap['notes'] = $cIdx;
        }

        // Positional fallback for legacy 24-column template
        if (!isset($colMap['full_name'])) {
            $colMap['full_name'] = 3;
            $colMap['nis'] = 0;
            $colMap['nisn'] = 1;
            $colMap['nik'] = 2;
            $colMap['nickname'] = 4;
            $colMap['gender'] = 5;
            $colMap['birth_place'] = 6;
            $colMap['birth_date'] = 7;
            $colMap['religion'] = 8;
            $colMap['sub_unit'] = 9;
            $colMap['jenjang'] = 10;
            $colMap['classroom'] = 11;
            $colMap['daycare'] = 12;
            $colMap['is_tpq'] = 13;
            $colMap['tpq_rombel'] = 14;
            $colMap['father_name'] = 15;
            $colMap['father_phone'] = 16;
            $colMap['mother_name'] = 17;
            $colMap['mother_phone'] = 18;
            $colMap['parent_phone'] = 19;
            $colMap['address'] = 20;
            $colMap['city'] = 21;
            $colMap['pin_access'] = 22;
            $colMap['status'] = 23;
        }

        $activeAY = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        $defaultAYId = $request->input('default_academic_year_id', $activeAY?->id);
        $defaultClassroomId = $request->input('default_classroom_id');

        $errors = [];
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($dataRows as $index => $row) {
            $rowNum = $index + $headerRowIdx + 2;

            $getVal = function ($key) use ($row, $colMap) {
                if (isset($colMap[$key]) && isset($row[$colMap[$key]])) {
                    $v = trim((string)$row[$colMap[$key]]);
                    return $v !== '' ? $v : null;
                }
                return null;
            };

            $fullName = $getVal('full_name');
            if (empty($fullName)) {
                continue;
            }

            $nis = $getVal('nis');
            $nisn = $getVal('nisn');
            $nik = $getVal('nik');
            $noKk = $getVal('no_kk');
            $noAkta = $getVal('no_akta');
            $nickname = $getVal('nickname');
            $genderRaw = $getVal('gender') ?? 'L';
            $birthPlace = $getVal('birth_place');
            $birthDateRaw = isset($colMap['birth_date']) ? ($row[$colMap['birth_date']] ?? null) : null;
            $religion = $getVal('religion') ?? 'Islam';
            $citizenship = $getVal('citizenship') ?? 'WNI';
            $address = $getVal('address');
            $rt = $getVal('rt');
            $rw = $getVal('rw');
            $kelurahan = $getVal('kelurahan');
            $kecamatan = $getVal('kecamatan');
            $city = $getVal('city') ?? 'Malang';
            $postalCode = $getVal('postal_code');
            $livingWith = $getVal('living_with') ?? 'Bersama orang tua';
            $transportation = $getVal('transportation');
            $childOrder = is_numeric($getVal('child_order')) ? (int)$getVal('child_order') : null;
            $siblingsCount = is_numeric($getVal('siblings_count')) ? (int)$getVal('siblings_count') : null;
            $bloodType = $getVal('blood_type');
            $specialNeeds = $getVal('special_needs') ?? 'Tidak ada';
            $height = is_numeric($getVal('height')) ? (float)$getVal('height') : null;
            $weight = is_numeric($getVal('weight')) ? (float)$getVal('weight') : null;
            $headCirc = is_numeric($getVal('head_circumference')) ? (float)$getVal('head_circumference') : null;
            $armCirc = is_numeric($getVal('arm_circumference')) ? (float)$getVal('arm_circumference') : null;
            $distanceToSchool = $getVal('distance_to_school');
            $travelTime = is_numeric($getVal('travel_time_minutes')) ? (int)$getVal('travel_time_minutes') : null;
            $subUnitRaw = strtoupper($getVal('sub_unit') ?? 'TK');
            $jenjangRaw = $getVal('jenjang');
            $kelompokRaw = $getVal('classroom');
            $daycareRaw = $getVal('daycare');
            $tpqRaw = $getVal('is_tpq');
            $tpqKelompokRaw = $getVal('tpq_rombel');
            $fatherName = $getVal('father_name');
            $fatherNik = $getVal('father_nik');
            $fatherEdu = $getVal('father_education');
            $fatherJob = $getVal('father_job');
            $fatherPhone = $getVal('father_phone');
            $motherName = $getVal('mother_name');
            $motherNik = $getVal('mother_nik');
            $motherEdu = $getVal('mother_education');
            $motherJob = $getVal('mother_job');
            $motherPhone = $getVal('mother_phone');
            $parentPhone = $getVal('parent_phone') ?? ($fatherPhone ?: $motherPhone);
            $pinAccess = $getVal('pin_access') ?? '1234';
            $statusRaw = strtolower($getVal('status') ?? 'aktif');
            $allergy = $getVal('allergy');
            $notes = $getVal('notes');

            // Normalize gender
            $gender = in_array(strtoupper($genderRaw), ['P', 'PEREMPUAN', 'FEMALE', 'W']) ? 'P' : 'L';

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
                    $ts = strtotime(str_replace('/', '-', (string)$birthDateRaw));
                    if ($ts !== false) {
                        $birthDate = date('Y-m-d', $ts);
                    }
                }
            }

            // Resolve Classroom dynamically
            $classroomId = $defaultClassroomId;
            $classLevelId = null;
            $jenjangId = null;
            $subUnit = in_array($subUnitRaw, ['PG', 'TK', 'DAYCARE', 'TPQ']) ? $subUnitRaw : 'TK';

            if (!empty($kelompokRaw)) {
                $matchedClassroom = Classroom::with(['jenjang', 'classLevel.jenjang'])->where('name', 'like', "%{$kelompokRaw}%")->first();
                if ($matchedClassroom) {
                    $classroomId = $matchedClassroom->id;
                    $classLevelId = $matchedClassroom->class_level_id;
                    $jenjangId = $matchedClassroom->jenjang_id ?? $matchedClassroom->classLevel?->jenjang_id;
                    $subUnit = $matchedClassroom->jenjang?->code ?? $matchedClassroom->sub_unit ?? $subUnit;
                }
            }

            if (!$classLevelId && !empty($jenjangRaw)) {
                $matchedLevel = ClassLevel::with('jenjang')->where('name', 'like', "%{$jenjangRaw}%")->first();
                if ($matchedLevel) {
                    $classLevelId = $matchedLevel->id;
                    if (!$jenjangId) $jenjangId = $matchedLevel->jenjang_id;
                    if (!$subUnit) $subUnit = $matchedLevel->jenjang?->code;
                }
            }

            if (!$jenjangId && !empty($jenjangRaw)) {
                $matchedJenjang = Jenjang::where('name', 'like', "%{$jenjangRaw}%")->orWhere('code', 'like', "%{$jenjangRaw}%")->first();
                if ($matchedJenjang) {
                    $jenjangId = $matchedJenjang->id;
                    $subUnit = $matchedJenjang->code;
                }
            }

            // Resolve Daycare
            $daycareClassroomId = null;
            if (!empty($daycareRaw)) {
                $dc = Classroom::whereHas('jenjang', function($jq) {
                    $jq->whereIn('code', ['DAYCARE', 'TPA'])->orWhere('name', 'like', '%Daycare%')->orWhere('name', 'like', '%TPA%');
                })->where('name', 'like', "%{$daycareRaw}%")->first() ?? Classroom::where('name', 'like', "%{$daycareRaw}%")->first();
                $daycareClassroomId = $dc?->id;
            }

            // Resolve TPQ
            $isTpq = in_array(strtolower($tpqRaw ?? ''), ['ya', 'true', '1', 'tpq', 'ikut']);
            $tpqClassroomId = null;
            if (!empty($tpqKelompokRaw)) {
                $tpqCls = Classroom::whereHas('jenjang', function($jq) {
                    $jq->where('code', 'TPQ')->orWhere('name', 'like', '%TPQ%');
                })->where('name', 'like', "%{$tpqKelompokRaw}%")->first() ?? Classroom::where('name', 'like', "%{$tpqKelompokRaw}%")->first();
                $tpqClassroomId = $tpqCls?->id;
                if ($tpqClassroomId) $isTpq = true;
            }

            // Match student by NIS, NIK, or Full Name + Birth Date
            $student = null;
            if (!empty($nis)) {
                $student = Student::where('nis', $nis)->first();
            }
            if (!$student && !empty($nik)) {
                $student = Student::where('nik', $nik)->first();
            }
            if (!$student && $birthDate && $fullName) {
                $student = Student::where('full_name', $fullName)->where('birth_date', $birthDate)->first();
            }

            // Prepare Family Data JSON
            $familyData = [
                'father' => [
                    'name' => $fatherName,
                    'nik' => $fatherNik,
                    'education' => $fatherEdu,
                    'job' => $fatherJob,
                    'phone' => $fatherPhone,
                ],
                'mother' => [
                    'name' => $motherName,
                    'nik' => $motherNik,
                    'education' => $motherEdu,
                    'job' => $motherJob,
                    'phone' => $motherPhone,
                ],
                'guardian' => [
                    'is_active' => false,
                ]
            ];

            // Prepare Health Habits JSON
            $healthHabits = [];
            if (!empty($allergy)) {
                $healthHabits['allergy'] = [
                    'has_allergies' => true,
                    'types' => $allergy,
                    'symptoms' => null,
                ];
            }

            $data = [
                'nis' => $nis,
                'nisn' => $nisn,
                'nik' => $nik,
                'no_kk' => $noKk,
                'no_akta' => $noAkta,
                'full_name' => $fullName,
                'nickname' => $nickname,
                'gender' => $gender,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'religion' => $religion,
                'citizenship' => $citizenship,
                'address' => $address,
                'rt' => $rt,
                'rw' => $rw,
                'kelurahan' => $kelurahan,
                'kecamatan' => $kecamatan,
                'city' => $city,
                'postal_code' => $postalCode,
                'living_with' => $livingWith,
                'transportation' => $transportation,
                'child_order' => $childOrder,
                'siblings_count' => $siblingsCount,
                'blood_type' => $bloodType,
                'special_needs' => $specialNeeds,
                'height' => $height,
                'weight' => $weight,
                'head_circumference' => $headCirc,
                'arm_circumference' => $armCirc,
                'distance_to_school' => $distanceToSchool,
                'travel_time_minutes' => $travelTime,
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
                'father_job' => $fatherJob,
                'mother_name' => $motherName,
                'mother_phone' => $motherPhone,
                'mother_job' => $motherJob,
                'parent_phone' => $parentPhone,
                'family_data' => $familyData,
                'health_habits' => $healthHabits,
                'pin_access' => $pinAccess,
                'status' => in_array($statusRaw, ['aktif', 'lulus', 'mutasi', 'keluar', 'nonaktif']) ? $statusRaw : 'aktif',
                'notes' => $notes,
                'enrolled_date' => now()->toDateString(),
            ];

            if ($student) {
                $student->update($data);
                $updatedCount++;
            } else {
                $student = Student::create($data);
                $importedCount++;
            }

            // Sync classroom history
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
                        'status' => $student->status,
                        'start_date' => $student->enrolled_date ?? now()->toDateString(),
                        'notes' => 'Impor Data Excel Lengkap',
                    ]
                );
            }
        }

        $msg = "Proses impor selesai! {$importedCount} data murid baru berhasil ditambahkan, {$updatedCount} data murid diperbarui.";

        if (!empty($errors)) {
            return redirect()->route('students.index')
                ->with('success', $msg)
                ->with('import_errors', $errors);
        }

        return redirect()->route('students.index')->with('success', $msg);
    }
}

