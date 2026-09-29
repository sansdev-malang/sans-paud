<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    /**
     * Display a listing of classrooms with student statistics.
     */
    public function index(Request $request)
    {
        $selectedYearId = $request->get('academic_year_id');
        $selectedSubUnit = $request->get('sub_unit', 'all');

        $query = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher']);

        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
        }

        if ($selectedSubUnit && $selectedSubUnit !== 'all') {
            $query->where('sub_unit', $selectedSubUnit);
        }

        if ($classLevelId = $request->get('class_level_id')) {
            if ($classLevelId !== 'all') {
                $query->where('class_level_id', $classLevelId);
            }
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $classrooms = $query->orderBy('class_level_id')->orderBy('name')->get();

        // Calculate active student counts per classroom considering multi-program (daycare & tpq)
        foreach ($classrooms as $cls) {
            $clsId = $cls->id;
            $cls->active_students_count = Student::where(function ($q) use ($clsId) {
                $q->where('classroom_id', $clsId)
                  ->orWhere('daycare_classroom_id', $clsId)
                  ->orWhere('tpq_classroom_id', $clsId);
            })->where('status', 'aktif')->count();
        }

        // Calculate statistics
        $totalClassrooms = $classrooms->count();
        $totalCapacity = $classrooms->sum('capacity');
        $totalEnrolled = $classrooms->sum('active_students_count');
        $occupancyRate = $totalCapacity > 0 ? round(($totalEnrolled / $totalCapacity) * 100, 1) : 0;

        $stats = [
            'total_classrooms' => $totalClassrooms,
            'total_capacity' => $totalCapacity,
            'total_enrolled' => $totalEnrolled,
            'occupancy_rate' => $occupancyRate,
        ];

        $classLevels = ClassLevel::orderBy('order')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $teachers = Employee::whereIn('status', ['Active', 'aktif', 'active', 'Aktif'])->orderBy('name')->get();

        return view('admin.classrooms.index', compact(
            'classrooms',
            'stats',
            'classLevels',
            'academicYears',
            'teachers',
            'selectedYearId',
            'selectedSubUnit'
        ));
    }

    /**
     * Get list of students in specific classroom (JSON).
     */
    public function students($id): JsonResponse
    {
        $classroom = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])->findOrFail($id);
        
        $students = Student::where(function ($q) use ($id) {
            $q->where('classroom_id', $id)
              ->orWhere('daycare_classroom_id', $id)
              ->orWhere('tpq_classroom_id', $id);
        })
        ->where('status', 'aktif')
        ->with('spmbCandidate')
        ->orderBy('full_name')
        ->get();

        return response()->json([
            'success' => true,
            'classroom' => $classroom,
            'students' => $students,
            'count' => $students->count(),
        ]);
    }

    /**
     * Store new classroom.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'sub_unit' => 'nullable|string|in:PG,TK,DAYCARE,TPQ',
            'class_level_id' => 'required|exists:class_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:employees,id',
            'capacity' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['sub_unit'])) {
            $level = ClassLevel::find($validated['class_level_id']);
            $validated['sub_unit'] = $level?->sub_unit ?? 'TK';
        }

        $classroom = Classroom::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Kelompok {$classroom->name} berhasil dibuat.",
            'classroom' => $classroom,
        ]);
    }

    /**
     * Update classroom.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $classroom = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'sub_unit' => 'nullable|string|in:PG,TK,DAYCARE,TPQ',
            'class_level_id' => 'required|exists:class_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'homeroom_teacher_id' => 'nullable|exists:employees,id',
            'capacity' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['sub_unit'])) {
            $level = ClassLevel::find($validated['class_level_id']);
            $validated['sub_unit'] = $level?->sub_unit ?? 'TK';
        }

        $classroom->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Kelompok {$classroom->name} berhasil diperbarui.",
            'classroom' => $classroom,
        ]);
    }

    /**
     * Delete classroom.
     */
    public function destroy($id): JsonResponse
    {
        $classroom = Classroom::findOrFail($id);
        
        $activeCount = Student::where(function ($q) use ($id) {
            $q->where('classroom_id', $id)
              ->orWhere('daycare_classroom_id', $id)
              ->orWhere('tpq_classroom_id', $id);
        })->where('status', 'aktif')->count();

        if ($activeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Kelompok tidak dapat dihapus karena masih memiliki {$activeCount} murid aktif. Pindahkan murid terlebih dahulu.",
            ], 422);
        }

        $name = $classroom->name;
        $classroom->delete();

        return response()->json([
            'success' => true,
            'message' => "Kelompok {$name} berhasil dihapus.",
        ]);
    }
}
