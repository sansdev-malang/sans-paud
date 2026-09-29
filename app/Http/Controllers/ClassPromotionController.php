<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassroomHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassPromotionController extends Controller
{
    /**
     * Display Class Promotion & Graduation management page for PG - TK - DAYCARE.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $sourceYearId = $request->get('source_year_id', $activeAcademicYear?->id);
        $sourceYear = $academicYears->firstWhere('id', $sourceYearId) ?? $activeAcademicYear;

        // Classrooms in source academic year
        $sourceClassrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true)
            ->where('academic_year_id', $sourceYear?->id)
            ->join('class_levels', 'classrooms.class_level_id', '=', 'class_levels.id')
            ->orderBy('class_levels.order', 'asc')
            ->orderBy('classrooms.name', 'asc')
            ->select('classrooms.*')
            ->get();

        // All active classrooms across all academic years for target selection
        $allClassrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true)
            ->join('class_levels', 'classrooms.class_level_id', '=', 'class_levels.id')
            ->orderBy('classrooms.academic_year_id', 'desc')
            ->orderBy('class_levels.order', 'asc')
            ->orderBy('classrooms.name', 'asc')
            ->select('classrooms.*')
            ->get();

        // Separate graduation classrooms (KB-B for Playgroup & TK-B for TK)
        $graduationClassrooms = $sourceClassrooms->filter(function ($c) {
            $code = strtoupper($c->classLevel?->code ?? '');
            $name = strtoupper($c->classLevel?->name ?? '');
            $clsName = strtoupper($c->name ?? '');

            return str_contains($code, 'KB-B') || str_contains($name, 'KB-B') || str_contains($clsName, 'KB B')
                || str_contains($code, 'TK-B') || str_contains($name, 'TK-B') || str_contains($clsName, 'TK B');
        });

        $classLevels = ClassLevel::orderBy('order')->get();

        return view('admin.promotions.index', [
            'academicYears' => $academicYears,
            'activeAcademicYear' => $activeAcademicYear,
            'sourceYear' => $sourceYear,
            'sourceClassrooms' => $sourceClassrooms,
            'allClassrooms' => $allClassrooms,
            'graduationClassrooms' => $graduationClassrooms,
            'classLevels' => $classLevels,
        ]);
    }

    /**
     * Fetch active students in a classroom for promotion/graduation table (JSON).
     */
    public function getStudents(Request $request): JsonResponse
    {
        $classroomId = $request->get('classroom_id');
        if (!$classroomId) {
            return response()->json(['success' => false, 'message' => 'Kelompok belum dipilih.', 'students' => []]);
        }

        $classroom = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])->find($classroomId);
        if (!$classroom) {
            return response()->json(['success' => false, 'message' => 'Kelompok tidak ditemukan.', 'students' => []]);
        }

        $students = Student::where('classroom_id', $classroomId)
            ->where('status', 'aktif')
            ->orderBy('full_name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'classroom' => $classroom,
            'students' => $students,
            'count' => $students->count(),
        ]);
    }

    /**
     * Process class promotion for students.
     */
    public function promote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_classroom_id' => 'required|exists:classrooms,id',
            'target_classroom_id' => 'required|exists:classrooms,id',
            'target_academic_year_id' => 'required|exists:academic_years,id',
            'effective_date' => 'nullable|date',
            'students' => 'required|array|min:1',
            'students.*.id' => 'required|exists:students,id',
            'students.*.action' => 'required|in:promote,stay,transfer_out',
            'students.*.custom_classroom_id' => 'nullable|exists:classrooms,id',
        ]);

        $sourceClassroom = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])->findOrFail($validated['source_classroom_id']);
        $targetClassroom = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])->findOrFail($validated['target_classroom_id']);
        $effectiveDate = $validated['effective_date'] ?: now()->toDateString();

        $promotedCount = 0;
        $stayedCount = 0;
        $transferredCount = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['students'] as $item) {
                $student = Student::findOrFail($item['id']);
                $action = $item['action'];

                // 1. Close active history in old classroom
                StudentClassroomHistory::where('student_id', $student->id)
                    ->where('classroom_id', $sourceClassroom->id)
                    ->whereNull('end_date')
                    ->update([
                        'end_date' => $effectiveDate,
                    ]);

                if ($action === 'promote') {
                    $destinationClassroomId = !empty($item['custom_classroom_id']) ? $item['custom_classroom_id'] : $targetClassroom->id;
                    $destClassroom = Classroom::with(['classLevel', 'homeroomTeacher'])->find($destinationClassroomId);

                    $student->update([
                        'classroom_id' => $destinationClassroomId,
                        'class_level_id' => $destClassroom?->class_level_id,
                        'sub_unit' => $destClassroom?->sub_unit ?? $student->sub_unit,
                        'academic_year_id' => $validated['target_academic_year_id'],
                        'status' => 'aktif',
                    ]);

                    StudentClassroomHistory::create([
                        'student_id' => $student->id,
                        'academic_year_id' => $validated['target_academic_year_id'],
                        'classroom_id' => $destinationClassroomId,
                        'sub_unit' => $destClassroom?->sub_unit ?? $student->sub_unit,
                        'classroom_name' => $destClassroom?->name,
                        'grade_level' => $destClassroom?->classLevel?->name,
                        'homeroom_teacher_name' => $destClassroom?->homeroomTeacher?->name,
                        'status' => 'aktif',
                        'start_date' => $effectiveDate,
                        'notes' => "Kenaikan Kelompok dari {$sourceClassroom->name} ke {$destClassroom?->name}",
                    ]);

                    $promotedCount++;
                } elseif ($action === 'stay') {
                    // Tinggal di kelompok yang sama pada TA baru
                    $student->update([
                        'academic_year_id' => $validated['target_academic_year_id'],
                        'status' => 'aktif',
                    ]);

                    StudentClassroomHistory::create([
                        'student_id' => $student->id,
                        'academic_year_id' => $validated['target_academic_year_id'],
                        'classroom_id' => $sourceClassroom->id,
                        'sub_unit' => $sourceClassroom->sub_unit ?? $student->sub_unit,
                        'classroom_name' => $sourceClassroom->name,
                        'grade_level' => $sourceClassroom->classLevel?->name,
                        'homeroom_teacher_name' => $sourceClassroom->homeroomTeacher?->name,
                        'status' => 'aktif',
                        'start_date' => $effectiveDate,
                        'notes' => "Mengulang di Kelompok {$sourceClassroom->name}",
                    ]);

                    $stayedCount++;
                } elseif ($action === 'transfer_out') {
                    $student->update([
                        'status' => 'mutasi',
                    ]);

                    StudentClassroomHistory::create([
                        'student_id' => $student->id,
                        'academic_year_id' => $sourceClassroom->academic_year_id,
                        'classroom_id' => $sourceClassroom->id,
                        'sub_unit' => $sourceClassroom->sub_unit,
                        'classroom_name' => $sourceClassroom->name,
                        'grade_level' => $sourceClassroom->classLevel?->name,
                        'homeroom_teacher_name' => $sourceClassroom->homeroomTeacher?->name,
                        'status' => 'mutasi',
                        'start_date' => $effectiveDate,
                        'end_date' => $effectiveDate,
                        'notes' => 'Mutasi Keluar',
                    ]);

                    $transferredCount++;
                }
            }

            DB::commit();

            $message = "Proses berhasil! {$promotedCount} murid naik kelas";
            if ($stayedCount > 0) $message .= ", {$stayedCount} murid mengulang";
            if ($transferredCount > 0) $message .= ", {$transferredCount} murid mutasi";

            return response()->json([
                'success' => true,
                'message' => $message,
                'counts' => [
                    'promoted' => $promotedCount,
                    'stayed' => $stayedCount,
                    'transferred' => $transferredCount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses kenaikan kelas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process graduation for final year students (KB-B or TK-B).
     */
    public function graduate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'graduation_date' => 'nullable|date',
            'students' => 'required|array|min:1',
            'students.*.id' => 'required|exists:students,id',
            'students.*.is_graduated' => 'required|boolean',
        ]);

        $classroom = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])->findOrFail($validated['classroom_id']);
        $gradDate = $validated['graduation_date'] ?: now()->toDateString();

        $graduatedCount = 0;
        $subUnit = $classroom->sub_unit ?: 'TK';

        DB::beginTransaction();
        try {
            foreach ($validated['students'] as $item) {
                if (!$item['is_graduated']) continue;

                $student = Student::findOrFail($item['id']);

                // Close active history
                StudentClassroomHistory::where('student_id', $student->id)
                    ->where('classroom_id', $classroom->id)
                    ->whereNull('end_date')
                    ->update([
                        'end_date' => $gradDate,
                    ]);

                $student->update([
                    'status' => 'lulus',
                ]);

                StudentClassroomHistory::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $classroom->academic_year_id,
                    'classroom_id' => $classroom->id,
                    'sub_unit' => $subUnit,
                    'classroom_name' => $classroom->name,
                    'grade_level' => $classroom->classLevel?->name,
                    'homeroom_teacher_name' => $classroom->homeroomTeacher?->name,
                    'status' => 'lulus',
                    'start_date' => $student->enrolled_date ?? $gradDate,
                    'end_date' => $gradDate,
                    'notes' => "Lulus / Alumni {$subUnit} dari Kelompok {$classroom->name}",
                ]);

                $graduatedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Selamat! Sebanyak {$graduatedCount} murid di kelompok {$classroom->name} resmi dinyatakan LULUS ({$subUnit}).",
                'graduated_count' => $graduatedCount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses kelulusan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
