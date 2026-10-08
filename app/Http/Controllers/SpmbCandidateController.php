<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\SpmbCandidate;
use App\Models\Student;
use App\Services\SpmbIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpmbCandidateController extends Controller
{
    protected SpmbIntegrationService $service;

    public function __construct(SpmbIntegrationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of SPMB Candidates.
     */
    public function index(Request $request)
    {
        // 1. Get dynamic master filter options from SPMB API / master data
        $masterOptions = $this->service->getFilterOptions();
        $defaultPeriodFromSpmb = $masterOptions['default_period'] ?? null;

        $rawCandidateYears = SpmbCandidate::whereNotNull('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->toArray();
        $rawMasterYears = AcademicYear::pluck('name')->toArray();
        $apiPeriods = $masterOptions['periods'] ?? [];

        $academicYears = collect(array_merge($rawCandidateYears, $rawMasterYears, $apiPeriods))
            ->map(fn($y) => str_replace('-', '/', trim($y)))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        // Default year selection priority:
        // 1. User query parameter 'period'
        // 2. Default active registration period in SPMB (e.g. 2027/2028)
        // 3. Most populated candidate year in database
        // 4. Active academic year in PAUD
        $defaultYear = $defaultPeriodFromSpmb;
        if (!$defaultYear || !in_array($defaultYear, $academicYears)) {
            $mostPopulatedYear = SpmbCandidate::whereNotNull('academic_year')
                ->select('academic_year', \DB::raw('count(*) as total'))
                ->groupBy('academic_year')
                ->orderByDesc('total')
                ->value('academic_year');
            
            $activeMasterYear = AcademicYear::where('is_active', true)->value('name');

            $defaultYear = $mostPopulatedYear ?: ($activeMasterYear ?: ($academicYears[0] ?? 'all'));
        }

        $selectedYear = $request->get('period', $defaultYear);
        if ($selectedYear && $selectedYear !== 'all') {
            $selectedYear = str_replace('-', '/', trim($selectedYear));
        }

        // 2. Base Query
        $query = SpmbCandidate::with('student.classroom');

        if ($selectedYear && $selectedYear !== 'all') {
            $slashYear = str_replace('-', '/', $selectedYear);
            $hyphenYear = str_replace('/', '-', $selectedYear);
            $query->whereIn('academic_year', [$slashYear, $hyphenYear]);
        }

        // Search Filter (Keyword search for name, reg no, NIK, parent)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%");
            });
        }

        // 1. Filter Jalur Masuk (registration_type) - Exact match
        if ($regType = $request->get('registration_type')) {
            if ($regType !== 'all') {
                $query->where('registration_type', $regType);
            }
        }

        // 2. Filter Gelombang (wave) - Exact match
        if ($wave = $request->get('wave')) {
            if ($wave !== 'all') {
                $query->where('wave', $wave);
            }
        }

        // 3. Filter Jenjang (jenjang) - KODE JENJANG (KB, TK, TPA, TPQ)
        // Multi-jenjang support: mencocokkan kelas utama maupun layanan tambahan
        if ($jenjang = $request->get('jenjang')) {
            if ($jenjang !== 'all') {
                $jenjangCode = strtoupper(trim($jenjang));
                $query->where(function ($q) use ($jenjangCode) {
                    $q->where('admission_level', 'like', "{$jenjangCode}%")
                      ->orWhere('admission_level', 'like', "%{$jenjangCode}%")
                      ->orWhere('extra_services', 'like', "%{$jenjangCode}%")
                      ->orWhere('raw_payload', 'like', "%\"{$jenjangCode}\"%")
                      ->orWhere('raw_payload', 'like', "%\"jenjang_code\":\"{$jenjangCode}\"%");
                });
            }
        }

        // 4. Filter Kelas (admission_level) - Mendukung single class maupun multi-kelas (misal KB A & TPA 2)
        if ($admissionLevel = $request->get('admission_level')) {
            if ($admissionLevel !== 'all') {
                $query->where(function ($q) use ($admissionLevel) {
                    $q->where('admission_level', $admissionLevel)
                      ->orWhere('admission_level', 'like', "%{$admissionLevel}%")
                      ->orWhere('extra_services', 'like', "%{$admissionLevel}%")
                      ->orWhere('raw_payload', 'like', "%\"{$admissionLevel}\"%");
                });
            }
        }

        // 5. Filter Kategori Murid (class_program) - Exact match
        if ($category = $request->get('category')) {
            if ($category !== 'all') {
                $query->where('class_program', $category);
            }
        }

        // Status Pendaftaran Filter - Exact match
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('registration_status', $status);
            }
        }

        // Status Pembayaran Filter - Exact match
        if ($payment = $request->get('payment_status')) {
            if ($payment !== 'all') {
                $query->where('payment_status', $payment);
            }
        }

        // 3. Stats Calculation
        $statsQuery = SpmbCandidate::query();
        if ($selectedYear && $selectedYear !== 'all') {
            $slashYear = str_replace('-', '/', $selectedYear);
            $hyphenYear = str_replace('/', '-', $selectedYear);
            $statsQuery->whereIn('academic_year', [$slashYear, $hyphenYear]);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'verified' => (clone $statsQuery)->whereIn('registration_status', ['verified', 'accepted', 'diterima', 'terverifikasi', 'completed', 'agreement_signed'])->count(),
            'paid' => (clone $statsQuery)->whereIn('payment_status', ['paid', 'lunas', 'settlement', 'success'])->count(),
            'enrolled' => (clone $statsQuery)->where('is_enrolled', true)->count(),
            'has_payment_data' => (clone $statsQuery)->whereNotNull('payment_status')->exists(),
        ];

        // 4. Dynamic Filter Options Lists
        // Jalur Masuk (dari Master SPMB & Database)
        $dbTypes = SpmbCandidate::whereNotNull('registration_type')->where('registration_type', '!=', '')->distinct()->pluck('registration_type')->toArray();
        $apiTypes = $masterOptions['registration_types'] ?? ['Murid Baru', 'Mutasi Masuk / Pindahan'];
        $registrationTypes = array_values(array_unique(array_filter(array_merge($apiTypes, $dbTypes))));

        // Gelombang (dari Master SPMB & Database)
        $dbWaves = SpmbCandidate::whereNotNull('wave')->where('wave', '!=', '')->distinct()->pluck('wave')->toArray();
        $apiWaves = $masterOptions['waves'] ?? ['Indent', 'Gelombang 1', 'Gelombang 2'];
        $availableWaves = array_values(array_unique(array_filter(array_merge($apiWaves, $dbWaves))));

        // Jenjang (KB, TK, TPA, TPQ)
        $availableJenjangs = collect($masterOptions['jenjangs'] ?? [
            ['code' => 'KB', 'name' => 'Playgroup (KB)'],
            ['code' => 'TK', 'name' => 'Taman Kanak-kanak (TK)'],
            ['code' => 'TPA', 'name' => 'Daycare (TPA)'],
            ['code' => 'TPQ', 'name' => 'TPQ'],
        ])->map(function ($j) {
            return is_array($j) ? (object)$j : $j;
        });

        // Seluruh Master Kelas PAUD
        $allMasterGrades = $masterOptions['grades'] ?? [
            ['name' => 'KB A', 'jenjang_code' => 'KB'],
            ['name' => 'KB B', 'jenjang_code' => 'KB'],
            ['name' => 'TK A', 'jenjang_code' => 'TK'],
            ['name' => 'TK B', 'jenjang_code' => 'TK'],
            ['name' => 'TPA 1 (Umum)', 'jenjang_code' => 'TPA'],
            ['name' => 'TPA 1 (Anak Gukar YPAS)', 'jenjang_code' => 'TPA'],
            ['name' => 'TPA 2 (Mulai Usia 2 Tahun)', 'jenjang_code' => 'TPA'],
            ['name' => 'TPA 2 (Mulai Usia 3 Tahun)', 'jenjang_code' => 'TPA'],
            ['name' => 'TPA 3 (Mulai Usia 4 Tahun)', 'jenjang_code' => 'TPA'],
            ['name' => 'TPA 3 (Mulai Usia 5 Tahun)', 'jenjang_code' => 'TPA'],
            ['name' => 'TPQ', 'jenjang_code' => 'TPQ'],
        ];

        // Filter Opsi Kelas jika Jenjang dipilih (Cascade Jenjang -> Kelas)
        $selectedJenjang = $request->get('jenjang');
        if ($selectedJenjang && $selectedJenjang !== 'all') {
            $filteredGrades = array_filter($allMasterGrades, function($g) use ($selectedJenjang) {
                return strtoupper($g['jenjang_code'] ?? '') === strtoupper($selectedJenjang);
            });
            $availableAdmissionLevels = array_values(array_unique(array_column($filteredGrades, 'name')));
        } else {
            $availableAdmissionLevels = array_values(array_unique(array_column($allMasterGrades, 'name')));
        }

        // Kategori Murid (Reguler, MBK)
        $dbCats = SpmbCandidate::whereNotNull('class_program')->where('class_program', '!=', '')->distinct()->pluck('class_program')->toArray();
        $apiCats = $masterOptions['categories'] ?? ['Reguler', 'Murid Berkebutuhan Khusus (MBK)'];
        $categories = array_values(array_unique(array_filter(array_merge($apiCats, $dbCats))));

        $candidates = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $masterAcademicYears = AcademicYear::orderBy('name', 'desc')->get();
        $masterJenjangs = $availableJenjangs;
        $masterClassrooms = Classroom::with(['jenjang', 'classLevel', 'homeroomTeacher'])
            ->where('is_active', true)
            ->orderBy('jenjang_id')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();

        return view('admin.spmb-candidates.index', compact(
            'candidates', 
            'academicYears', 
            'selectedYear', 
            'stats', 
            'registrationTypes',
            'availableWaves',
            'availableJenjangs',
            'allMasterGrades',
            'availableAdmissionLevels',
            'categories',
            'masterAcademicYears',
            'masterJenjangs',
            'masterClassrooms'
        ));
    }

    /**
     * Show detail of candidate.
     */
    public function show($id): JsonResponse
    {
        $candidate = SpmbCandidate::with([
            'student.classroom.classLevel',
            'student.classroom.jenjang',
            'student.classroom.homeroomTeacher',
            'student.daycareClassroom',
            'student.tpqClassroom',
            'student.jenjang',
            'student.classLevel',
            'student.academicYear'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'candidate' => $candidate,
            'clean_phone' => $candidate->getCleanPhone(),
            'wa_url' => $candidate->whatsapp_url,
        ]);
    }

    /**
     * Trigger manual pull sync from SPMB.
     */
    public function sync(Request $request): JsonResponse
    {
        $period = $request->input('period');
        $status = $request->input('status');

        $result = $this->service->syncCandidates($period, $status);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Test SPMB connection endpoint.
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->service->testConnection();
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get data prefilled for enrollment modal.
     */
    public function getEnrollData($id): JsonResponse
    {
        $candidate = SpmbCandidate::with([
            'student.classroom.classLevel',
            'student.classroom.jenjang',
            'student.classroom.homeroomTeacher',
            'student.daycareClassroom',
            'student.tpqClassroom',
            'student.jenjang',
            'student.classLevel',
            'student.academicYear'
        ])->findOrFail($id);

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        // Match academic year from candidate's period
        $matchedYear = null;
        if ($candidate->academic_year) {
            $cleanYear = str_replace('-', '/', $candidate->academic_year);
            $matchedYear = AcademicYear::where('name', $cleanYear)->first();
        }
        if (!$matchedYear) {
            $matchedYear = AcademicYear::where('is_active', true)->first() ?? $academicYears->first();
        }

        // Get all active classrooms (with count of active students)
        $classrooms = Classroom::with(['classLevel', 'jenjang', 'homeroomTeacher'])
            ->withCount(['students as active_students_count' => function ($q) use ($matchedYear) {
                $q->where('status', 'aktif');
                if ($matchedYear) {
                    $q->where('academic_year_id', $matchedYear->id);
                }
            }])
            ->where('is_active', true)
            ->orderBy('jenjang_id')
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();

        $jenjangs = \App\Models\Jenjang::where('is_active', true)->orderBy('order')->get();
        $classLevels = \App\Models\ClassLevel::orderBy('jenjang_id')->orderBy('order')->get();

        $daycareClassrooms = Classroom::where('is_active', true)
            ->where(function($q) {
                $q->where('sub_unit', 'DAYCARE')
                  ->orWhereHas('jenjang', fn($jq) => $jq->where('code', 'DAYCARE')->orWhere('code', 'TPA'));
            })->orderBy('name')->get();

        $tpqClassrooms = Classroom::where('is_active', true)
            ->where(function($q) {
                $q->where('sub_unit', 'TPQ')
                  ->orWhereHas('jenjang', fn($jq) => $jq->where('code', 'TPQ'));
            })->orderBy('name')->get();

        // Generate suggested NIS
        $yearDigits = $matchedYear ? substr(explode('/', $matchedYear->name)[0] ?? '2027', -2) : date('y');
        $prefix = "{$yearDigits}.PAUD.";

        $latestStudent = Student::where('nis', 'like', "{$prefix}%")
            ->orderBy('nis', 'desc')
            ->first();

        $nextSeq = 1;
        if ($latestStudent && preg_match('/(\d+)$/', $latestStudent->nis, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        }
        $suggestedNis = $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'candidate' => $candidate,
            'student' => $candidate->student,
            'suggested_nis' => $suggestedNis,
            'academic_years' => $academicYears,
            'selected_year_id' => $matchedYear?->id,
            'jenjangs' => $jenjangs,
            'class_levels' => $classLevels,
            'classrooms' => $classrooms,
            'daycare_classrooms' => $daycareClassrooms,
            'tpq_classrooms' => $tpqClassrooms,
        ]);
    }

    /**
     * Enroll candidate into active students.
     */
    public function enroll(Request $request, $id): JsonResponse
    {
        $candidate = SpmbCandidate::findOrFail($id);

        $validated = $request->validate([
            'nis' => 'nullable|string|max:50|unique:students,nis,' . ($candidate->student_id ?? 'NULL'),
            'academic_year_id' => 'required|exists:academic_years,id',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'daycare_classroom_id' => 'nullable|exists:classrooms,id',
            'is_tpq' => 'nullable|boolean',
            'tpq_classroom_id' => 'nullable|exists:classrooms,id',
            'enrolled_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $classroom = Classroom::with(['classLevel', 'jenjang'])->findOrFail($validated['classroom_id']);
        $jenjangId = $validated['jenjang_id'] ?: $classroom->jenjang_id;
        $classLevelId = $validated['class_level_id'] ?: $classroom->class_level_id;
        $subUnit = $classroom->sub_unit ?: ($classroom->jenjang?->code ?? 'TK');

        $studentData = [
            'nis' => !empty($validated['nis']) ? trim($validated['nis']) : null,
            'nisn' => $candidate->nisn,
            'nik' => $candidate->nik,
            'spmb_candidate_id' => $candidate->id,
            'jenjang_id' => $jenjangId,
            'class_level_id' => $classLevelId,
            'classroom_id' => $classroom->id,
            'daycare_classroom_id' => $validated['daycare_classroom_id'] ?? null,
            'is_tpq' => !empty($validated['is_tpq']),
            'tpq_classroom_id' => $validated['tpq_classroom_id'] ?? null,
            'sub_unit' => $subUnit,
            'academic_year_id' => $validated['academic_year_id'],
            'full_name' => $candidate->full_name,
            'nickname' => $candidate->nickname,
            'gender' => $candidate->gender,
            'birth_place' => $candidate->birth_place,
            'birth_date' => $candidate->birth_date,
            'religion' => $candidate->religion ?: 'Islam',
            'address' => $candidate->address,
            'city' => $candidate->city,
            'province' => $candidate->province,
            'previous_school' => $candidate->previous_school,
            'student_photo_url' => $candidate->student_photo_url,
            'father_name' => $candidate->father_name,
            'father_phone' => $candidate->father_phone,
            'father_job' => $candidate->father_job,
            'mother_name' => $candidate->mother_name,
            'mother_phone' => $candidate->mother_phone,
            'mother_job' => $candidate->mother_job,
            'guardian_name' => $candidate->guardian_name,
            'guardian_phone' => $candidate->guardian_phone,
            'parent_phone' => $candidate->parent_phone,
            'parent_email' => $candidate->parent_email,
            'documents' => $candidate->documents,
            'status' => 'aktif',
            'enrolled_date' => $validated['enrolled_date'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? "Terdaftar via integrasi SPMB ({$candidate->registration_number})",
        ];

        if ($candidate->student_id && $existingStudent = Student::find($candidate->student_id)) {
            $existingStudent->update($studentData);
            $student = $existingStudent;
        } else {
            $student = Student::create($studentData);
        }

        $candidate->is_enrolled = true;
        $candidate->enrolled_at = now();
        $candidate->student_id = $student->id;
        $candidate->save();

        // Record initial classroom history with snapshots
        if ($student->classroom_id) {
            $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
            \App\Models\StudentClassroomHistory::updateOrCreate(
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
                    'status' => 'aktif',
                    'start_date' => $student->enrolled_date ?? now()->toDateString(),
                    'notes' => "Pendaftaran Murid Baru via SPMB ({$candidate->registration_number})",
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Ananda {$candidate->full_name} berhasil resmi terdaftar sebagai Murid Aktif SANS PAUD (NIS: {$student->nis}).",
            'student' => $student,
        ]);
    }

    /**
     * Cancel enrollment status and remove student record.
     */
    public function unenroll($id): JsonResponse
    {
        $candidate = SpmbCandidate::findOrFail($id);

        if ($candidate->student_id) {
            $student = Student::find($candidate->student_id);
            if ($student) {
                $student->delete();
            }
        }

        $candidate->is_enrolled = false;
        $candidate->enrolled_at = null;
        $candidate->student_id = null;
        $candidate->save();

        return response()->json([
            'success' => true,
            'message' => "Status Murid Aktif untuk {$candidate->full_name} berhasil dibatalkan.",
        ]);
    }
}
