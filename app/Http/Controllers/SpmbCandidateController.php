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
        // 1. Get available academic years and normalize to standard slash format (e.g. 2026/2027)
        $rawYears = SpmbCandidate::select('academic_year')
            ->whereNotNull('academic_year')
            ->distinct()
            ->pluck('academic_year')
            ->toArray();

        $academicYears = collect($rawYears)
            ->map(fn($y) => str_replace('-', '/', trim($y)))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        // Default to latest year or 'all' if empty
        $selectedYear = $request->get('period', $academicYears[0] ?? 'all');
        if ($selectedYear && $selectedYear !== 'all') {
            $selectedYear = str_replace('-', '/', trim($selectedYear));
        }

        // 2. Base Query
        $query = SpmbCandidate::with('student.classroom');

        if ($selectedYear && $selectedYear !== 'all') {
            $slashYear = str_replace('-', '/', $selectedYear);
            $hyphenYear = str_replace('/', '-', $selectedYear);
            $query->where(function ($q) use ($slashYear, $hyphenYear) {
                $q->where('academic_year', $slashYear)
                  ->orWhere('academic_year', $hyphenYear);
            });
        }

        // Search Filter
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

        // Category Filter (Reguler / MBK)
        if ($category = $request->get('category')) {
            if ($category !== 'all') {
                if (strtolower($category) === 'mbk') {
                    $query->where(function ($q) {
                        $q->where('class_program', 'like', '%mbk%')
                          ->orWhere('class_program', 'like', '%kebutuhan khusus%');
                    });
                } elseif (strtolower($category) === 'reguler') {
                    $query->where(function ($q) {
                        $q->where('class_program', 'not like', '%mbk%')
                          ->where('class_program', 'not like', '%kebutuhan khusus%');
                    });
                } else {
                    $query->where('class_program', $category);
                }
            }
        }

        // Jalur Masuk Filter (Murid Baru / Mutasi Masuk)
        if ($regType = $request->get('registration_type')) {
            if ($regType !== 'all') {
                $query->where(function ($q) use ($regType) {
                    $q->where('registration_type', 'like', "%{$regType}%")
                      ->orWhere('raw_payload->registration_type', 'like', "%{$regType}%")
                      ->orWhere('raw_payload->entry_type', 'like', "%{$regType}%");
                });
            }
        }

        // Kelas / Kelompok Pilihan Filter (TK A / TK B / KB / Daycare)
        if ($admissionLevel = $request->get('admission_level')) {
            if ($admissionLevel !== 'all') {
                $query->where(function ($q) use ($admissionLevel) {
                    $q->where('admission_level', 'like', "%{$admissionLevel}%")
                      ->orWhere('class_program', 'like', "%{$admissionLevel}%");
                });
            }
        }

        // Layanan Tambahan Filter (Daycare / TPQ / Fullday)
        if ($service = $request->get('service')) {
            if ($service !== 'all') {
                $query->where(function ($q) use ($service) {
                    $q->where('extra_services', 'like', "%{$service}%")
                      ->orWhere('raw_payload->extra_services', 'like', "%{$service}%")
                      ->orWhere('raw_payload->services', 'like', "%{$service}%");
                });
            }
        }

        // Wave Filter
        if ($wave = $request->get('wave')) {
            if ($wave !== 'all') {
                $query->where('wave', $wave);
            }
        }

        // Status Filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('registration_status', $status);
            }
        }

        // Payment Filter
        if ($payment = $request->get('payment_status')) {
            if ($payment !== 'all') {
                $query->where('payment_status', $payment);
            }
        }

        // 3. Stats Calculation (based on selected year)
        $statsQuery = SpmbCandidate::query();
        if ($selectedYear && $selectedYear !== 'all') {
            $slashYear = str_replace('-', '/', $selectedYear);
            $hyphenYear = str_replace('/', '-', $selectedYear);
            $statsQuery->where(function ($q) use ($slashYear, $hyphenYear) {
                $q->where('academic_year', $slashYear)
                  ->orWhere('academic_year', $hyphenYear);
            });
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'verified' => (clone $statsQuery)->whereIn('registration_status', ['verified', 'accepted', 'diterima', 'terverifikasi'])->count(),
            'paid' => (clone $statsQuery)->whereIn('payment_status', ['paid', 'lunas', 'settlement', 'success'])->count(),
            'enrolled' => (clone $statsQuery)->where('is_enrolled', true)->count(),
        ];

        // 4. Get filter option lists
        $availableWaves = (clone $statsQuery)->whereNotNull('wave')->distinct()->pluck('wave')->filter()->values()->toArray();
        if (empty($availableWaves)) {
            $availableWaves = ['Gelombang 1', 'Gelombang 2', 'Gelombang 3', 'Indent'];
        }

        $categories = ['Reguler', 'MBK'];
        $registrationTypes = ['Murid Baru', 'Mutasi Masuk / Pindahan'];
        $availableAdmissionLevels = ['Kelompok Bermain (KB)', 'TK A', 'TK B', 'Daycare / TPA'];
        $availableServices = ['Daycare', 'Fullday', 'TPQ'];

        $candidates = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $masterAcademicYears = AcademicYear::orderBy('name', 'desc')->get();
        $masterJenjangs = \App\Models\Jenjang::where('is_active', true)->orderBy('order')->get();
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
            'categories',
            'registrationTypes',
            'availableWaves',
            'availableAdmissionLevels',
            'availableServices',
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
            'nis' => 'required|string|max:50|unique:students,nis,' . ($candidate->student_id ?? 'NULL'),
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
            'nis' => $validated['nis'],
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
