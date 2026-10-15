<?php

namespace App\Http\Controllers;

use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Jenjang;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassLevelController extends Controller
{
    /**
     * Display a listing of class levels (Kelas) with stats.
     */
    public function index()
    {
        $jenjangs = Jenjang::orderBy('order', 'asc')->get();

        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear?->id;

        $classLevels = ClassLevel::with(['jenjang', 'classrooms' => function ($q) use ($activeYearId) {
                if ($activeYearId) {
                    $q->where('academic_year_id', $activeYearId);
                }
                $q->with('academicYear');
            }])
            ->withCount([
                'classrooms' => function ($q) use ($activeYearId) {
                    if ($activeYearId) {
                        $q->where('academic_year_id', $activeYearId);
                    }
                },
            ])
            ->orderBy('order', 'asc')
            ->get();

        // Calculate student counts per class level via classrooms
        foreach ($classLevels as $lvl) {
            $classLevelId = $lvl->id;
            $lvl->active_students_count = Student::whereHas('classroom', function ($q) use ($classLevelId, $activeYearId) {
                $q->where('class_level_id', $classLevelId);
                if ($activeYearId) {
                    $q->where('academic_year_id', $activeYearId);
                }
            })->where('status', 'aktif')
            ->when($activeYearId, fn($q) => $q->where('academic_year_id', $activeYearId))
            ->count();
        }

        $totalLevels = $classLevels->count();
        $classroomsQuery = Classroom::where('is_active', true);
        if ($activeYearId) {
            $classroomsQuery->where('academic_year_id', $activeYearId);
        }
        $totalClassrooms = (clone $classroomsQuery)->count();
        $totalCapacity = (clone $classroomsQuery)->sum('capacity');
        $totalStudents = Student::where('status', 'aktif')
            ->when($activeYearId, fn($q) => $q->where('academic_year_id', $activeYearId))
            ->count();

        $stats = [
            'total_levels' => $totalLevels,
            'total_classrooms' => $totalClassrooms,
            'total_capacity' => $totalCapacity,
            'total_students' => $totalStudents,
        ];

        return view('admin.class-levels.index', compact('classLevels', 'jenjangs', 'stats'));
    }

    /**
     * Show single class level (JSON).
     */
    public function show($id): JsonResponse
    {
        $classLevel = ClassLevel::with(['jenjang', 'classrooms'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'class_level' => $classLevel,
        ]);
    }

    /**
     * Store new class level.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenjang_id' => 'required|exists:jenjangs,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:1|max:99',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['order'])) {
            $validated['order'] = (ClassLevel::max('order') ?? 0) + 1;
        }

        $jenjang = Jenjang::find($validated['jenjang_id']);
        if ($jenjang) {
            $validated['sub_unit'] = $jenjang->code;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $classLevel = ClassLevel::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Kelas {$classLevel->name} berhasil ditambahkan.",
            'class_level' => $classLevel,
        ]);
    }

    /**
     * Update class level.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $classLevel = ClassLevel::findOrFail($id);

        $validated = $request->validate([
            'jenjang_id' => 'required|exists:jenjangs,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:1|max:99',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $jenjang = Jenjang::find($validated['jenjang_id']);
        if ($jenjang) {
            $validated['sub_unit'] = $jenjang->code;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $classLevel->is_active;

        $classLevel->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Kelas {$classLevel->name} berhasil diperbarui.",
            'class_level' => $classLevel,
        ]);
    }

    /**
     * Delete class level.
     */
    public function destroy($id): JsonResponse
    {
        $classLevel = ClassLevel::findOrFail($id);

        $classroomsCount = $classLevel->classrooms()->count();
        if ($classroomsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Kelas tidak dapat dihapus karena masih digunakan oleh {$classroomsCount} rombel/kelompok.",
            ], 422);
        }

        $name = $classLevel->name;
        $classLevel->delete();

        return response()->json([
            'success' => true,
            'message' => "Kelas {$name} berhasil dihapus.",
        ]);
    }
}
