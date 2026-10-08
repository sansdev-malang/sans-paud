<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Jenjang;
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
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        if (!$selectedYearId || $selectedYearId === 'all') {
            $selectedYearId = $activeAcademicYear?->id;
        }
        $selectedJenjangId = $request->get('jenjang_id');

        $query = Classroom::with(['jenjang', 'classLevel.jenjang', 'academicYear', 'homeroomTeacher']);

        if ($selectedYearId) {
            $query->where('academic_year_id', $selectedYearId);
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

        // Fast Grouped Query to calculate active student counts per classroom without N+1 bottleneck
        $classroomIds = $classrooms->pluck('id')->toArray();
        if (!empty($classroomIds)) {
            $primaryCounts = Student::where('status', 'aktif')
                ->whereIn('classroom_id', $classroomIds)
                ->selectRaw('classroom_id, count(*) as total')
                ->groupBy('classroom_id')
                ->pluck('total', 'classroom_id')
                ->toArray();

            $daycareCounts = Student::where('status', 'aktif')
                ->whereIn('daycare_classroom_id', $classroomIds)
                ->selectRaw('daycare_classroom_id, count(*) as total')
                ->groupBy('daycare_classroom_id')
                ->pluck('total', 'daycare_classroom_id')
                ->toArray();

            $tpqCounts = Student::where('status', 'aktif')
                ->whereIn('tpq_classroom_id', $classroomIds)
                ->selectRaw('tpq_classroom_id, count(*) as total')
                ->groupBy('tpq_classroom_id')
                ->pluck('total', 'tpq_classroom_id')
                ->toArray();

            foreach ($classrooms as $cls) {
                $id = $cls->id;
                $code = $cls->jenjang?->code ?? $cls->sub_unit;
                if ($code === 'DAYCARE' || $code === 'TPA') {
                    $cls->active_students_count = ($primaryCounts[$id] ?? 0) + ($daycareCounts[$id] ?? 0);
                } elseif ($code === 'TPQ') {
                    $cls->active_students_count = ($primaryCounts[$id] ?? 0) + ($tpqCounts[$id] ?? 0);
                } else {
                    $cls->active_students_count = $primaryCounts[$id] ?? 0;
                }
            }
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

        $jenjangs = Jenjang::orderBy('order', 'asc')->get();
        $classLevels = ClassLevel::with('jenjang')->orderBy('order')->get();
        $teachers = Employee::whereIn('status', ['Active', 'aktif', 'active', 'Aktif'])->orderBy('name')->get();

        return view('admin.classrooms.index', compact(
            'classrooms',
            'stats',
            'jenjangs',
            'classLevels',
            'academicYears',
            'teachers',
            'selectedYearId',
            'selectedJenjangId'
        ));
    }

    /**
     * Show single classroom detail (JSON).
     */
    public function show($id): JsonResponse
    {
        $classroom = Classroom::with(['jenjang', 'classLevel.jenjang', 'academicYear', 'homeroomTeacher'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'classroom' => $classroom,
        ]);
    }

    /**
     * Get list of students in specific classroom (JSON).
     */
    public function students($id): JsonResponse
    {
        $classroom = Classroom::with(['jenjang', 'classLevel', 'academicYear', 'homeroomTeacher'])->findOrFail($id);
        
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
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'class_level_id' => 'required|exists:class_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'capacity' => 'required|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $level = ClassLevel::with('jenjang')->find($validated['class_level_id']);
        if ($level) {
            if (empty($validated['jenjang_id'])) {
                $validated['jenjang_id'] = $level->jenjang_id;
            }
            $validated['sub_unit'] = $level->jenjang?->code ?? $level->sub_unit;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $classroom = Classroom::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Rombel/Kelompok {$classroom->name} berhasil dibuat.",
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
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'class_level_id' => 'required|exists:class_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'capacity' => 'required|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $level = ClassLevel::with('jenjang')->find($validated['class_level_id']);
        if ($level) {
            if (empty($validated['jenjang_id'])) {
                $validated['jenjang_id'] = $level->jenjang_id;
            }
            $validated['sub_unit'] = $level->jenjang?->code ?? $level->sub_unit;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $classroom->is_active;

        $classroom->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Rombel/Kelompok {$classroom->name} berhasil diperbarui.",
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
                'message' => "Rombel/Kelompok tidak dapat dihapus karena masih memiliki {$activeCount} murid aktif. Pindahkan murid terlebih dahulu.",
            ], 422);
        }

        $name = $classroom->name;
        $classroom->delete();

        return response()->json([
            'success' => true,
            'message' => "Rombel/Kelompok {$name} berhasil dihapus.",
        ]);
    }
}
