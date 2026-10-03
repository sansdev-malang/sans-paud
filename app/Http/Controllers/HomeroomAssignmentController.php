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

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id', $jenjangs->first()?->id);
        $selectedClassLevelId = $request->get('class_level_id');
        $selectedStatus = $request->get('status');
        $search = $request->get('search');

        $query = HomeroomAssignment::with([
            'academicYear',
            'jenjang',
            'classLevel',
            'classroom',
            'teacher'
        ]);

        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
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

        if ($selectedStatus !== null && $selectedStatus !== '' && $selectedStatus !== 'all') {
            $query->where('is_active', (bool)$selectedStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('teacher', function ($tq) use ($search) {
                    $tq->where('name', 'like', "%{$search}%")
                       ->orWhere('nuptk', 'like', "%{$search}%");
                })->orWhereHas('classroom', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $assignments = $query->orderBy('academic_year_id', 'desc')
            ->orderBy('jenjang_id', 'asc')
            ->orderBy('class_level_id', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Master data for dropdowns
        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $classLevels = ClassLevel::with('jenjang')->where('is_active', true)->orderBy('order')->get();
        $classrooms = Classroom::with(['jenjang', 'classLevel'])->where('is_active', true)->orderBy('name')->get();

        // Teachers (Employees with teacher type or active status)
        $teacherTypeId = EmployeeType::where('code', 'teacher')->value('id');
        $teachers = Employee::where('status', 'Active')
            ->when($teacherTypeId, function ($q) use ($teacherTypeId) {
                $q->where(function ($sq) use ($teacherTypeId) {
                    $sq->where('employee_type_id', $teacherTypeId)
                       ->orWhere('position', 'like', '%guru%')
                       ->orWhere('position', 'like', '%wali%');
                });
            })
            ->orderBy('name', 'asc')
            ->get();

        if ($teachers->isEmpty()) {
            $teachers = Employee::where('status', 'Active')->orderBy('name', 'asc')->get();
        }

        return view('admin.homeroom-assignments.index', compact(
            'assignments',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedJenjangId',
            'selectedClassLevelId',
            'selectedStatus',
            'jenjangs',
            'classLevels',
            'classrooms',
            'teachers'
        ));
    }

    /**
     * Store a newly created homeroom assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'employee_id' => 'required|exists:employees,id',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Auto-fetch jenjang_id and class_level_id from classroom to ensure strict relational integrity
        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $validated['jenjang_id'] = $classroom->jenjang_id;
        $validated['class_level_id'] = $classroom->class_level_id;

        // Check if unique assignment exists
        $existing = HomeroomAssignment::where('academic_year_id', $validated['academic_year_id'])
            ->where('classroom_id', $validated['classroom_id'])
            ->where('employee_id', $validated['employee_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'jenjang_id' => $validated['jenjang_id'],
                'class_level_id' => $validated['class_level_id'],
                'is_active' => $validated['is_active'],
                'notes' => $validated['notes'],
            ]);
            $assignment = $existing;
        } else {
            $assignment = HomeroomAssignment::create($validated);
        }

        // Synchronize classroom's current homeroom teacher if active tapel
        $activeTapel = AcademicYear::where('is_active', true)->first();
        if ($activeTapel && $activeTapel->id == $assignment->academic_year_id && $assignment->is_active) {
            $classroom->update(['homeroom_teacher_id' => $assignment->employee_id]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan wali kelas berhasil disimpan.',
                'data' => $assignment,
                'jenjang_id' => $assignment->jenjang_id,
                'academic_year_id' => $assignment->academic_year_id,
            ]);
        }

        return redirect()->route('homeroom-assignments.index', [
            'academic_year_id' => $assignment->academic_year_id,
            'jenjang_id' => $assignment->jenjang_id,
        ])->with('success', 'Penugasan wali kelas berhasil disimpan.');
    }

    /**
     * Update the specified homeroom assignment.
     */
    public function update(Request $request, $id)
    {
        $assignment = HomeroomAssignment::findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'employee_id' => 'required|exists:employees,id',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $classroom = Classroom::findOrFail($validated['classroom_id']);
        $validated['jenjang_id'] = $classroom->jenjang_id;
        $validated['class_level_id'] = $classroom->class_level_id;

        $assignment->update($validated);

        // Synchronize classroom's current homeroom teacher if active tapel
        $activeTapel = AcademicYear::where('is_active', true)->first();
        if ($activeTapel && $activeTapel->id == $assignment->academic_year_id) {
            if ($assignment->is_active) {
                $classroom->update(['homeroom_teacher_id' => $assignment->employee_id]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Penugasan wali kelas berhasil diperbarui.',
                'data' => $assignment,
                'jenjang_id' => $assignment->jenjang_id,
                'academic_year_id' => $assignment->academic_year_id,
            ]);
        }

        return redirect()->route('homeroom-assignments.index', [
            'academic_year_id' => $assignment->academic_year_id,
            'jenjang_id' => $assignment->jenjang_id,
        ])->with('success', 'Penugasan wali kelas berhasil diperbarui.');
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
