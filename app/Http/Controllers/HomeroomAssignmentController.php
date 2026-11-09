<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\HomeroomAssignment;
use App\Models\Jenjang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeroomAssignmentController extends Controller
{
    /**
     * Display a listing of homeroom assignments with filters.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        if (!$selectedYearId || $selectedYearId === 'all') {
            $selectedYearId = $activeAcademicYear?->id;
        }

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id', 'all');
        $selectedClassLevelId = $request->get('class_level_id', 'all');
        $selectedRole = $request->get('role', 'all');
        $selectedStatus = $request->get('status', 'all');
        $search = $request->get('search');

        $query = HomeroomAssignment::with([
            'academicYear',
            'jenjang',
            'classLevel',
            'classroom',
            'teacher.user'
        ]);

        if ($selectedYearId) {
            $query->where('academic_year_id', $selectedYearId);
        }

        if ($selectedRole && $selectedRole !== 'all') {
            $query->where('role', $selectedRole);
        }

        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $query->where(function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId)
                  ->orWhereHas('classroom', function ($cq) use ($selectedJenjangId) {
                      $cq->where('jenjang_id', $selectedJenjangId);
                  });
            });
        }

        if ($selectedClassLevelId && $selectedClassLevelId !== 'all') {
            $query->where(function ($q) use ($selectedClassLevelId) {
                $q->where('class_level_id', $selectedClassLevelId)
                  ->orWhereHas('classroom', function ($cq) use ($selectedClassLevelId) {
                      $cq->where('class_level_id', $selectedClassLevelId);
                  });
            });
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $query->where('is_active', $selectedStatus === 'active' || $selectedStatus === '1');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('teacher', function ($tq) use ($search) {
                    $tq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('nuptk', 'like', "%{$search}%")
                       ->orWhere('niy', 'like', "%{$search}%")
                       ->orWhere('nik', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('classroom', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                })->orWhereHas('classLevel', function ($lq) use ($search) {
                    $lq->where('name', 'like', "%{$search}%");
                })->orWhereHas('jenjang', function ($jq) use ($search) {
                    $jq->where('name', 'like', "%{$search}%");
                })->orWhere('role', 'like', "%{$search}%")
                  ->orWhere('subject_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $perPageCount = ($perPage === 'all' || $perPage == 99999 || $perPage === '99999') ? 99999 : (int) $perPage;
        if ($perPageCount <= 0) {
            $perPageCount = 15;
        }

        $assignments = $query->orderByRaw("
            CASE 
                WHEN role = 'koordinator_tingkat' THEN 1 
                WHEN role = 'pendamping_tingkat' THEN 2 
                WHEN role = 'wali_kelas' THEN 3 
                WHEN role = 'guru_kelas' THEN 4 
                WHEN role = 'gpk' THEN 5 
                WHEN role = 'gpq' THEN 6 
                WHEN role = 'guru_mapel' THEN 7 
                ELSE 8 
            END ASC
        ")
        ->orderBy('jenjang_id', 'asc')
        ->orderBy('class_level_id', 'asc')
        ->paginate($perPageCount)
        ->withQueryString();

        // Master data for dropdowns
        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $classLevels = ClassLevel::with('jenjang')->whereNotNull('jenjang_id')->where('is_active', true)->orderBy('order')->get();
        $classrooms = Classroom::with(['jenjang', 'classLevel'])->where('is_active', true)->orderBy('name')->get();

        // Filter available class levels for toolbar filter
        $availableClassLevels = ($selectedJenjangId && $selectedJenjangId !== 'all')
            ? $classLevels->where('jenjang_id', $selectedJenjangId)
            : $classLevels;

        // Teachers strictly with teacher type (Guru)
        $teacherTypeId = EmployeeType::where('code', 'teacher')->value('id');
        $teachers = Employee::whereIn('status', ['Active', 'aktif', 'active', 'Aktif'])
            ->when($teacherTypeId, function ($q) use ($teacherTypeId) {
                $q->where('employee_type_id', $teacherTypeId);
            })
            ->orderBy('name', 'asc')
            ->get();

        $statsQuery = HomeroomAssignment::query();
        if ($selectedYearId) {
            $statsQuery->where('academic_year_id', $selectedYearId);
        }

        $stats = [
            'total_assignments' => (clone $statsQuery)->count(),
            'total_homeroom' => (clone $statsQuery)->where('role', 'wali_kelas')->where('is_active', true)->count(),
            'total_class_teacher' => (clone $statsQuery)->where('role', 'guru_kelas')->where('is_active', true)->count(),
            'total_gpk' => (clone $statsQuery)->where('role', 'gpk')->where('is_active', true)->count(),
            'total_gpq' => (clone $statsQuery)->where('role', 'gpq')->where('is_active', true)->count(),
            'total_mapel' => (clone $statsQuery)->where('role', 'guru_mapel')->where('is_active', true)->count(),
            'total_teachers' => $teachers->count(),
            'total_classrooms' => Classroom::where('is_active', true)
                ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
                ->count(),
            'assigned_classrooms' => HomeroomAssignment::where('academic_year_id', $selectedYearId)
                ->where('is_active', true)
                ->distinct('classroom_id')
                ->count('classroom_id'),
        ];

        return view('admin.homeroom-assignments.index', compact(
            'assignments',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedJenjangId',
            'selectedClassLevelId',
            'selectedRole',
            'selectedStatus',
            'jenjangs',
            'classLevels',
            'availableClassLevels',
            'classrooms',
            'teachers',
            'stats'
        ));
    }

    /**
     * Store a newly created homeroom assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|string|in:wali_kelas,guru_kelas,gpk,gpq,guru_mapel,koordinator_tingkat,pendamping_tingkat',
            'subject_name' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Auto-fetch jenjang_id and class_level_id from classroom or classLevel
        if (!empty($validated['classroom_id'])) {
            $classroom = Classroom::findOrFail($validated['classroom_id']);
            $validated['jenjang_id'] = $classroom->jenjang_id;
            $validated['class_level_id'] = $classroom->class_level_id;
        } elseif (!empty($validated['class_level_id'])) {
            $level = ClassLevel::findOrFail($validated['class_level_id']);
            $validated['jenjang_id'] = $level->jenjang_id;
        }

        // Check if unique assignment exists for this rombel/level and teacher
        $existing = HomeroomAssignment::where('academic_year_id', $validated['academic_year_id'])
            ->when(!empty($validated['classroom_id']), fn($q) => $q->where('classroom_id', $validated['classroom_id']))
            ->where('employee_id', $validated['employee_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'jenjang_id' => $validated['jenjang_id'] ?? $existing->jenjang_id,
                'class_level_id' => $validated['class_level_id'] ?? $existing->class_level_id,
                'role' => $validated['role'],
                'subject_name' => $validated['subject_name'] ?? null,
                'is_active' => $validated['is_active'],
                'notes' => $validated['notes'],
            ]);
            $assignment = $existing;
        } else {
            $assignment = HomeroomAssignment::create($validated);
        }

        // Synchronize classroom's current homeroom teacher if active tapel and role is wali_kelas
        $activeTapel = AcademicYear::where('is_active', true)->first();
        if ($assignment->role === 'wali_kelas' && $assignment->classroom_id && $activeTapel && $activeTapel->id == $assignment->academic_year_id && $assignment->is_active) {
            Classroom::where('id', $assignment->classroom_id)->update(['homeroom_teacher_id' => $assignment->employee_id]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan guru berhasil disimpan.',
                'data' => $assignment,
                'jenjang_id' => $assignment->jenjang_id,
                'academic_year_id' => $assignment->academic_year_id,
            ]);
        }

        return redirect()->route('homeroom-assignments.index', [
            'academic_year_id' => $assignment->academic_year_id,
            'jenjang_id' => $assignment->jenjang_id,
            'role' => $assignment->role,
        ])->with('success', 'Penugasan guru berhasil disimpan.');
    }

    /**
     * Update the specified homeroom assignment.
     */
    public function update(Request $request, $id)
    {
        $assignment = HomeroomAssignment::findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|string|in:wali_kelas,guru_kelas,gpk,gpq,guru_mapel,koordinator_tingkat,pendamping_tingkat',
            'subject_name' => 'nullable|string|max:100',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['classroom_id'])) {
            $classroom = Classroom::findOrFail($validated['classroom_id']);
            $validated['jenjang_id'] = $classroom->jenjang_id;
            $validated['class_level_id'] = $classroom->class_level_id;
        } elseif (!empty($validated['class_level_id'])) {
            $level = ClassLevel::findOrFail($validated['class_level_id']);
            $validated['jenjang_id'] = $level->jenjang_id;
        }

        $assignment->update($validated);

        // Synchronize classroom's current homeroom teacher if active tapel
        $activeTapel = AcademicYear::where('is_active', true)->first();
        if ($assignment->classroom_id && $activeTapel && $activeTapel->id == $assignment->academic_year_id) {
            if ($assignment->role === 'wali_kelas' && $assignment->is_active) {
                Classroom::where('id', $assignment->classroom_id)->update(['homeroom_teacher_id' => $assignment->employee_id]);
            } else {
                Classroom::where('id', $assignment->classroom_id)
                    ->where('homeroom_teacher_id', $assignment->employee_id)
                    ->update(['homeroom_teacher_id' => null]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan guru berhasil diperbarui.',
                'data' => $assignment,
                'jenjang_id' => $assignment->jenjang_id,
                'academic_year_id' => $assignment->academic_year_id,
            ]);
        }

        return redirect()->route('homeroom-assignments.index', [
            'academic_year_id' => $assignment->academic_year_id,
            'jenjang_id' => $assignment->jenjang_id,
            'role' => $assignment->role,
        ])->with('success', 'Penugasan guru berhasil diperbarui.');
    }

    /**
     * Remove the specified homeroom assignment.
     */
    public function destroy($id)
    {
        $assignment = HomeroomAssignment::findOrFail($id);
        $tapelId = $assignment->academic_year_id;
        $jenjangId = $assignment->jenjang_id ?? $assignment->classroom?->jenjang_id;
        $classroomId = $assignment->classroom_id;
        $employeeId = $assignment->employee_id;

        $assignment->delete();

        // If active tapel and classroom had this teacher, reset or assign next active teacher
        $activeTapel = AcademicYear::where('is_active', true)->first();
        if ($activeTapel && $activeTapel->id == $tapelId) {
            $classroom = Classroom::find($classroomId);
            if ($classroom && $classroom->homeroom_teacher_id == $employeeId) {
                $nextAssignment = HomeroomAssignment::where('academic_year_id', $tapelId)
                    ->where('classroom_id', $classroomId)
                    ->where('is_active', true)
                    ->first();
                $classroom->update(['homeroom_teacher_id' => $nextAssignment?->employee_id]);
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan wali kelas berhasil dihapus.',
                'jenjang_id' => $jenjangId,
                'academic_year_id' => $tapelId,
            ]);
        }

        return redirect()->route('homeroom-assignments.index', [
            'academic_year_id' => $tapelId,
            'jenjang_id' => $jenjangId,
        ])->with('success', 'Penugasan wali kelas berhasil dihapus.');
    }

    /**
     * Toggle status active/inactive.
     */
    public function toggleStatus($id)
    {
        $assignment = HomeroomAssignment::findOrFail($id);
        $assignment->is_active = !$assignment->is_active;
        $assignment->save();

        $activeTapel = AcademicYear::where('is_active', true)->first();
        if ($activeTapel && $activeTapel->id == $assignment->academic_year_id) {
            $classroom = Classroom::find($assignment->classroom_id);
            if ($classroom) {
                if ($assignment->is_active) {
                    $classroom->update(['homeroom_teacher_id' => $assignment->employee_id]);
                } else if ($classroom->homeroom_teacher_id == $assignment->employee_id) {
                    $classroom->update(['homeroom_teacher_id' => null]);
                }
            }
        }

        return redirect()->back()->with('success', 'Status penugasan wali kelas berhasil diubah.');
    }
}
