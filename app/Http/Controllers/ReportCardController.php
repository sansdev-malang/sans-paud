<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\LearningObjective;
use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportCardController extends Controller
{
    /**
     * Display a listing of Student Report Cards by Classroom, Academic Year, and Semester.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;
        $selectedYearId = $selectedYear?->id;

        $selectedSemester = $request->get('semester', '1');
        $selectedSubUnit = $request->get('sub_unit', 'ALL');

        // Fetch classrooms in selected academic year
        $classroomsQuery = Classroom::with(['classLevel', 'homeroomTeacher', 'academicYear'])
            ->where('is_active', true)
            ->where('academic_year_id', $selectedYearId);

        if ($selectedSubUnit && $selectedSubUnit !== 'ALL') {
            $classroomsQuery->where('sub_unit', $selectedSubUnit);
        }

        $classrooms = $classroomsQuery->orderBy('sub_unit')->orderBy('name')->get();

        $selectedClassroomId = $request->get('classroom_id');
        if (!$selectedClassroomId && $classrooms->isNotEmpty()) {
            $selectedClassroomId = $classrooms->first()->id;
        }

        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);

        // Fetch students in selected classroom (including multi-enrollment Daycare & TPQ)
        $students = collect();
        $reportCards = collect();

        if ($selectedClassroom) {
            $students = Student::where(function ($q) use ($selectedClassroom) {
                $q->where('classroom_id', $selectedClassroom->id)
                  ->orWhere('daycare_classroom_id', $selectedClassroom->id)
                  ->orWhere('tpq_classroom_id', $selectedClassroom->id);
            })
            ->where('academic_year_id', $selectedYearId)
            ->whereIn('status', ['aktif', 'lulus'])
            ->orderBy('full_name', 'asc')
            ->get();

            $reportCards = ReportCard::whereIn('student_id', $students->pluck('id'))
                ->where('academic_year_id', $selectedYearId)
                ->where('semester', $selectedSemester)
                ->get()
                ->keyBy('student_id');
        }

        // Stats calculation
        $totalStudents = $students->count();
        $publishedCount = $reportCards->where('status', 'published')->count();
        $approvedCount = $reportCards->where('status', 'approved')->count();
        $submittedCount = $reportCards->where('status', 'submitted')->count();
        $revisionCount = $reportCards->whereIn('status', ['revision', 'rejected'])->count();
        $draftCount = $reportCards->where('status', 'draft')->count();
        $pdfCount = $reportCards->where('entry_mode', 'pdf')->count();
        $uncreatedCount = $totalStudents - $reportCards->count();

        $stats = [
            'total_students' => $totalStudents,
            'published' => $publishedCount,
            'approved' => $approvedCount,
            'submitted' => $submittedCount,
            'revision' => $revisionCount,
            'draft' => $draftCount,
            'pdf_count' => $pdfCount,
            'uncreated' => max(0, $uncreatedCount),
        ];

        return view('admin.reports.cards.index', compact(
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedYear',
            'selectedSemester',
            'selectedSubUnit',
            'classrooms',
            'selectedClassroomId',
            'selectedClassroom',
            'students',
            'reportCards',
            'stats'
        ));
    }

    /**
     * Show report card edit/fill form for a student.
     */
    public function edit(Request $request, $studentId)
    {
        $student = Student::with(['classroom.classLevel', 'classroom.homeroomTeacher', 'academicYear', 'daycareClassroom', 'tpqClassroom'])
            ->findOrFail($studentId);

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $academicYearId = $request->get('academic_year_id', $student->academic_year_id ?? $activeAcademicYear?->id);
        $semester = $request->get('semester', '1');

        $reportCard = ReportCard::where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();

        if (!$reportCard) {
            $reportCard = new ReportCard([
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
                'classroom_id' => $student->classroom_id,
                'semester' => $semester,
                'entry_mode' => 'form',
                'report_date' => now()->toDateString(),
                'place' => 'Malang',
                'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name ?? '',
                'principal_name' => setting('principal_name', 'Ustadzah Kepala Sekolah, S.Pd'),
                'status' => 'draft',
            ]);
        }

        // Fetch narrative templates
        $templates = LearningObjective::orderBy('category')->orderBy('order')->get()->groupBy('category');

        return view('admin.reports.cards.edit', compact(
            'student',
            'reportCard',
            'academicYears',
            'academicYearId',
            'semester',
            'templates'
        ));
    }

    /**
     * Save or update student report card (supports both narrative form & direct PDF upload).
     */
    public function update(Request $request, $studentId)
    {
        $student = Student::with(['classroom.homeroomTeacher'])->findOrFail($studentId);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2,Ganjil,Genap,mid_ganjil,mid_genap,Mid Ganjil,Mid Genap,Mid Semester Ganjil,Mid Semester Genap,Semester Ganjil,Semester Genap',
            'entry_mode' => 'nullable|in:form,pdf',
            'pdf_file' => 'nullable|file|mimes:pdf|max:15360',
            'report_date' => 'nullable|date',
            'place' => 'nullable|string|max:100',
            'homeroom_teacher_name' => 'nullable|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'height' => 'nullable|numeric|min:0|max:200',
            'weight' => 'nullable|numeric|min:0|max:100',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'attendance_sick' => 'nullable|integer|min:0',
            'attendance_permission' => 'nullable|integer|min:0',
            'attendance_unexcused' => 'nullable|integer|min:0',
            'nabp_narrative' => 'nullable|string',
            'jati_diri_narrative' => 'nullable|string',
            'steam_narrative' => 'nullable|string',
            'p5_project_name' => 'nullable|string|max:255',
            'p5_narrative' => 'nullable|string',
            'daycare_narrative' => 'nullable|string',
            'tpq_jilid' => 'nullable|string|max:100',
            'tpq_surah' => 'nullable|string|max:255',
            'tpq_hadith_doa' => 'nullable|string|max:255',
            'tpq_narrative' => 'nullable|string',
            'teacher_notes' => 'nullable|string',
            'parent_feedback' => 'nullable|string',
            'status' => 'required|in:draft,submitted,approved,published',
            'photos.*' => 'nullable|image|max:3072',
            'existing_photos' => 'nullable|array',
        ]);

        $reportCard = ReportCard::firstOrNew([
            'student_id' => $student->id,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
        ]);

        $entryMode = $validated['entry_mode'] ?? ($request->hasFile('pdf_file') ? 'pdf' : ($reportCard->entry_mode ?: 'form'));
        $reportCard->entry_mode = $entryMode;

        // Handle direct PDF upload if provided
        if ($request->hasFile('pdf_file')) {
            if ($reportCard->pdf_file && Storage::disk('public')->exists($reportCard->pdf_file)) {
                Storage::disk('public')->delete($reportCard->pdf_file);
            }
            $reportCard->pdf_file = $request->file('pdf_file')->store('report_card_pdfs/' . date('Y_m'), 'public');
            $reportCard->entry_mode = 'pdf';
        }

        $reportCard->classroom_id = $student->classroom_id;
        $reportCard->report_date = $validated['report_date'] ?? now()->toDateString();
        $reportCard->place = $validated['place'] ?? 'Malang';
        $reportCard->homeroom_teacher_name = $validated['homeroom_teacher_name'] ?: ($student->classroom?->homeroomTeacher?->name ?? setting('default_teacher', 'Wali Kelas'));
        $reportCard->principal_name = $validated['principal_name'] ?: setting('principal_name', 'Ustadzah Kepala Sekolah, S.Pd');

        $reportCard->height = $validated['height'] ?? null;
        $reportCard->weight = $validated['weight'] ?? null;
        $reportCard->head_circumference = $validated['head_circumference'] ?? null;

        $reportCard->attendance_sick = $validated['attendance_sick'] ?? 0;
        $reportCard->attendance_permission = $validated['attendance_permission'] ?? 0;
        $reportCard->attendance_unexcused = $validated['attendance_unexcused'] ?? 0;

        $reportCard->nabp_narrative = $validated['nabp_narrative'] ?? null;
        $reportCard->jati_diri_narrative = $validated['jati_diri_narrative'] ?? null;
        $reportCard->steam_narrative = $validated['steam_narrative'] ?? null;

        $reportCard->p5_project_name = $validated['p5_project_name'] ?? null;
        $reportCard->p5_narrative = $validated['p5_narrative'] ?? null;

        $reportCard->daycare_narrative = $validated['daycare_narrative'] ?? null;
        $reportCard->tpq_jilid = $validated['tpq_jilid'] ?? null;
        $reportCard->tpq_surah = $validated['tpq_surah'] ?? null;
        $reportCard->tpq_hadith_doa = $validated['tpq_hadith_doa'] ?? null;
        $reportCard->tpq_narrative = $validated['tpq_narrative'] ?? null;

        $reportCard->teacher_notes = $validated['teacher_notes'] ?? null;
        $reportCard->parent_feedback = $validated['parent_feedback'] ?? null;
        $reportCard->status = $validated['status'];

        if ($validated['status'] === 'approved' || $validated['status'] === 'published') {
            $reportCard->approved_by = auth()->id();
            $reportCard->approved_at = now();
        }

        // Handle photos array
        $currentPhotos = $validated['existing_photos'] ?? ($reportCard->photos ?? []);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $path = $file->store('report_card_photos/' . date('Y_m'), 'public');
                $currentPhotos[] = $path;
            }
        }
        $reportCard->photos = array_values(array_filter($currentPhotos));

        $reportCard->created_by = $reportCard->created_by ?: auth()->id();
        $reportCard->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Rapor ananda {$student->full_name} berhasil disimpan.",
                'report_card' => $reportCard,
            ]);
        }

        return redirect()->route('report-cards.index', [
            'classroom_id' => $student->classroom_id,
            'academic_year_id' => $reportCard->academic_year_id,
            'semester' => $reportCard->semester
        ])->with('success', "Rapor ananda {$student->full_name} berhasil disimpan (" . ($reportCard->entry_mode === 'pdf' ? 'Berkas PDF' : 'Form Digital') . ").");
    }

    /**
     * Direct bypass upload of report card PDF file without filling the digital form.
     */
    public function uploadPdf(Request $request, $studentId)
    {
        $student = Student::with(['classroom.homeroomTeacher'])->findOrFail($studentId);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2,Ganjil,Genap,mid_ganjil,mid_genap,Mid Ganjil,Mid Genap,Mid Semester Ganjil,Mid Semester Genap,Semester Ganjil,Semester Genap',
            'pdf_file' => 'required|file|mimes:pdf|max:15360', // up to 15MB
            'status' => 'nullable|in:draft,submitted,approved,published',
            'report_date' => 'nullable|date',
            'teacher_notes' => 'nullable|string',
        ], [
            'pdf_file.required' => 'File PDF Rapor wajib diunggah.',
            'pdf_file.mimes' => 'Format berkas harus berupa dokumen .pdf.',
            'pdf_file.max' => 'Ukuran berkas PDF maksimal 15 MB.',
        ]);

        $reportCard = ReportCard::firstOrNew([
            'student_id' => $student->id,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
        ]);

        // Delete old pdf if replaced
        if ($reportCard->pdf_file && Storage::disk('public')->exists($reportCard->pdf_file)) {
            Storage::disk('public')->delete($reportCard->pdf_file);
        }

        $path = $request->file('pdf_file')->store('report_card_pdfs/' . date('Y_m'), 'public');

        $reportCard->classroom_id = $student->classroom_id;
        $reportCard->entry_mode = 'pdf';
        $reportCard->pdf_file = $path;
        $reportCard->report_date = $validated['report_date'] ?? now()->toDateString();
        $reportCard->place = 'Malang';
        $reportCard->homeroom_teacher_name = $student->classroom?->homeroomTeacher?->name ?? setting('default_teacher', 'Wali Kelas');
        $reportCard->principal_name = setting('principal_name', 'Ustadzah Kepala Sekolah, S.Pd');
        $reportCard->teacher_notes = $validated['teacher_notes'] ?? 'Berkas Rapor PDF resmi diunggah oleh Wali Kelas';
        $reportCard->status = $validated['status'] ?? 'published';
        $reportCard->created_by = $reportCard->created_by ?: auth()->id();

        if ($reportCard->status === 'published' || $reportCard->status === 'approved') {
            $reportCard->approved_by = auth()->id();
            $reportCard->approved_at = now();
        }

        $reportCard->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Berkas PDF Rapor untuk {$student->full_name} berhasil diunggah.",
                'report_card' => $reportCard,
            ]);
        }

        return redirect()->route('report-cards.index', [
            'classroom_id' => $student->classroom_id,
            'academic_year_id' => $reportCard->academic_year_id,
            'semester' => $reportCard->semester
        ])->with('success', "Berkas PDF Rapor ananda {$student->full_name} berhasil diunggah dan terbit.");
    }

    /**
     * Show single printable report card for a student (or stream uploaded PDF).
     */
    public function show(Request $request, $id)
    {
        $reportCard = ReportCard::with([
            'student.classroom.classLevel',
            'student.classLevel',
            'academicYear',
            'classroom.homeroomTeacher'
        ])->findOrFail($id);

        // If report is PDF mode and raw stream requested or direct view
        if ($reportCard->entry_mode === 'pdf' && $reportCard->pdf_file) {
            if ($request->has('raw') || $request->has('download')) {
                return response()->file(storage_path('app/public/' . $reportCard->pdf_file));
            }
        }

        return view('admin.reports.cards.print', [
            'reports' => collect([$reportCard]),
            'isBatch' => false,
        ]);
    }

    /**
     * Batch print report cards for an entire classroom.
     */
    public function batchPrint(Request $request)
    {
        $classroomId = $request->get('classroom_id');
        $academicYearId = $request->get('academic_year_id');
        $semester = $request->get('semester', '1');

        if (!$classroomId || !$academicYearId) {
            return redirect()->back()->with('error', 'Kelompok dan Tahun Ajaran wajib dipilih.');
        }

        $classroom = Classroom::with(['classLevel', 'homeroomTeacher'])->findOrFail($classroomId);

        $students = Student::where(function ($q) use ($classroomId) {
            $q->where('classroom_id', $classroomId)
              ->orWhere('daycare_classroom_id', $classroomId)
              ->orWhere('tpq_classroom_id', $classroomId);
        })
        ->where('academic_year_id', $academicYearId)
        ->whereIn('status', ['aktif', 'lulus'])
        ->get();

        $reports = ReportCard::with([
            'student.classroom.classLevel',
            'student.classLevel',
            'academicYear',
            'classroom.homeroomTeacher'
        ])
        ->whereIn('student_id', $students->pluck('id'))
        ->where('academic_year_id', $academicYearId)
        ->where('semester', $semester)
        ->whereIn('status', ['published', 'approved', 'draft'])
        ->get();

        if ($reports->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada data rapor yang dibuat untuk kelompok ini pada semester yang dipilih.');
        }

        return view('admin.reports.cards.print', [
            'reports' => $reports,
            'isBatch' => true,
            'classroom' => $classroom,
        ]);
    }

    /**
     * Fetch narrative templates (JSON endpoint).
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $query = LearningObjective::query();

        if ($category) {
            $query->where('category', $category);
        }

        $templates = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Approve single report card by Principal / Admin.
     */
    public function approve(Request $request, $id)
    {
        $reportCard = ReportCard::with('student')->findOrFail($id);
        $reportCard->status = 'approved';
        $reportCard->approved_by = auth()->id();
        $reportCard->approved_at = now();
        $reportCard->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Rapor ananda {$reportCard->student->full_name} berhasil disetujui Kepala Sekolah.",
            ]);
        }

        return redirect()->back()->with('success', "Rapor ananda {$reportCard->student->full_name} berhasil disetujui Kepala Sekolah.");
    }

    /**
     * Publish single report card to parent portal.
     */
    public function publish(Request $request, $id)
    {
        $reportCard = ReportCard::with('student')->findOrFail($id);
        $reportCard->status = 'published';
        $reportCard->approved_by = $reportCard->approved_by ?: auth()->id();
        $reportCard->approved_at = $reportCard->approved_at ?: now();
        $reportCard->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Rapor ananda {$reportCard->student->full_name} berhasil diterbitkan ke Portal Wali Murid.",
            ]);
        }

        return redirect()->back()->with('success', "Rapor ananda {$reportCard->student->full_name} berhasil diterbitkan ke Portal Wali Murid.");
    }

    /**
     * Batch approve all report cards in a classroom for the selected semester.
     */
    public function batchApprove(Request $request)
    {
        $classroomId = $request->input('classroom_id');
        $academicYearId = $request->input('academic_year_id');
        $semester = $request->input('semester', '1');

        $studentIds = Student::where(function ($q) use ($classroomId) {
            $q->where('classroom_id', $classroomId)
              ->orWhere('daycare_classroom_id', $classroomId)
              ->orWhere('tpq_classroom_id', $classroomId);
        })->pluck('id');

        $affected = ReportCard::whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        return redirect()->back()->with('success', "Sebanyak {$affected} rapor kelompok berhasil disetujui Kepala Sekolah.");
    }

    /**
     * Batch publish all approved report cards in a classroom to parent portal.
     */
    public function batchPublish(Request $request)
    {
        $classroomId = $request->input('classroom_id');
        $academicYearId = $request->input('academic_year_id');
        $semester = $request->input('semester', '1');

        $studentIds = Student::where(function ($q) use ($classroomId) {
            $q->where('classroom_id', $classroomId)
              ->orWhere('daycare_classroom_id', $classroomId)
              ->orWhere('tpq_classroom_id', $classroomId);
        })->pluck('id');

        $affected = ReportCard::whereIn('student_id', $studentIds)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->update([
                'status' => 'published',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        return redirect()->back()->with('success', "Sebanyak {$affected} rapor kelompok berhasil diterbitkan ke Portal Wali Murid.");
    }

    /**
     * Request revision / reject report card with notes from Principal.
     */
    public function requestRevision(Request $request, $id)
    {
        $validated = $request->validate([
            'revision_notes' => 'required|string|max:1500',
        ], [
            'revision_notes.required' => 'Catatan revisi / poin perbaikan wajib diisi.',
            'revision_notes.max' => 'Catatan revisi maksimal 1500 karakter.',
        ]);

        $reportCard = ReportCard::with('student')->findOrFail($id);
        $reportCard->status = 'revision';
        $reportCard->revision_notes = $validated['revision_notes'];
        $reportCard->rejected_by = auth()->id();
        $reportCard->rejected_at = now();
        $reportCard->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Permintaan revisi rapor ananda {$reportCard->student->full_name} telah dikirim ke wali kelas.",
                'report_card' => $reportCard,
            ]);
        }

        return redirect()->back()->with('warning', "Permintaan revisi rapor ananda {$reportCard->student->full_name} berhasil dikirim ke wali kelas.");
    }
}
