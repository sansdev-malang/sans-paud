<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $query = Student::with(['classroom.classLevel', 'academicYear', 'spmbCandidate']);

        // Academic Year Filter
        if ($selectedYearId) {
            $query->where('academic_year_id', $selectedYearId);
        }

        // Search query
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        // Filter: Kelas (ClassLevel)
        if ($classLevelId = $request->get('class_level_id')) {
            if ($classLevelId !== 'all') {
                $query->whereHas('classroom', function ($q) use ($classLevelId) {
                    $q->where('class_level_id', $classLevelId);
                });
            }
        }

        // Filter: Rombel (Classroom)
        if ($classroomId = $request->get('classroom_id')) {
            if ($classroomId !== 'all') {
                $query->where('classroom_id', $classroomId);
            }
        }

        // Filter: Status
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Filter: Gender
        if ($gender = $request->get('gender')) {
            if ($gender !== 'all') {
                $query->where('gender', $gender);
            }
        }

        // Stats calculation based on selected academic year
        $statsQuery = Student::query();
        if ($selectedYearId) {
            $statsQuery->where('academic_year_id', $selectedYearId);
        }

        $totalStudents = (clone $statsQuery)->count();
        $activeStudents = (clone $statsQuery)->where('status', 'aktif')->count();
        $maleStudents = (clone $statsQuery)->where('status', 'aktif')->whereIn('gender', ['L', 'Laki-laki', 'Male'])->count();
        $femaleStudents = (clone $statsQuery)->where('status', 'aktif')->whereIn('gender', ['P', 'Perempuan', 'Female'])->count();
        
        $rombelQuery = Classroom::where('is_active', true);
        if ($selectedYearId) {
            $rombelQuery->where('academic_year_id', $selectedYearId);
        }
        $totalClassrooms = $rombelQuery->count();

        $stats = [
            'total_active' => $activeStudents,
            'total_all' => $totalStudents,
            'male' => $maleStudents,
            'female' => $femaleStudents,
            'classrooms' => $totalClassrooms,
        ];

        // Master lists for filter dropdowns & modal selects
        $classLevels = ClassLevel::orderBy('order')->get();
        
        $classroomListQuery = Classroom::with(['classLevel', 'academicYear'])->where('is_active', true);
        if ($selectedYearId) {
            $classroomListQuery->where('academic_year_id', $selectedYearId);
        }
        $classrooms = $classroomListQuery->orderBy('class_level_id')->orderBy('name')->get();
        $allClassrooms = Classroom::with(['classLevel', 'academicYear'])->where('is_active', true)->orderBy('academic_year_id', 'desc')->orderBy('name')->get();

        $students = $query->orderBy('status', 'asc')->orderBy('full_name', 'asc')->paginate(15)->withQueryString();

        return view('admin.students.index', compact(
            'students',
            'stats',
            'classLevels',
            'classrooms',
            'allClassrooms',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedYear'
        ));
    }

    /**
     * Show single student detail (JSON).
     */
    public function show($id): JsonResponse
    {
        $student = Student::with(['classroom.classLevel', 'classroom.homeroomTeacher', 'academicYear', 'spmbCandidate'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'student' => $student,
            'formatted_gender' => $student->formatted_gender,
            'age' => $student->age,
            'whatsapp_url' => $student->whatsapp_url,
            'clean_phone' => $student->clean_parent_phone,
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
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'previous_school' => 'nullable|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'parent_phone' => 'nullable|string|max:50',
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

        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Data siswa {$student->full_name} berhasil ditambahkan.",
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
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'previous_school' => 'nullable|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'parent_phone' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'notes' => 'nullable|string',
        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Data siswa {$student->full_name} berhasil diperbarui.",
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

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => "Data siswa {$name} berhasil dihapus.",
        ]);
    }
}
