<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Http\Request;

class ParentReportPortalController extends Controller
{
    /**
     * Display public portal login for parents.
     */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return view('portal.report-card-login', compact('academicYears'));
    }

    /**
     * Check parent credentials (NIS and PIN) and redirect to digital report card.
     */
    public function check(Request $request)
    {
        $request->validate([
            'nis' => 'required|string',
            'pin' => 'required|string',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester' => 'nullable|in:1,2,Ganjil,Genap',
        ], [
            'nis.required' => 'NIS (Nomor Induk Siswa) wajib diisi.',
            'pin.required' => 'PIN akses 4-digit wajib diisi.',
        ]);

        $nis = trim($request->input('nis'));
        $pin = trim($request->input('pin'));

        $student = Student::with(['classroom.classLevel', 'academicYear'])
            ->where('nis', $nis)
            ->first();

        if (!$student) {
            return redirect()->back()->withInput()->with('error', 'Nomor Induk Siswa (NIS) tidak ditemukan dalam sistem.');
        }

        if (empty($student->pin_access) || $student->pin_access !== $pin) {
            return redirect()->back()->withInput()->with('error', 'PIN Akses yang Anda masukkan salah. Silakan hubungi Wali Kelas / Ustadzah.');
        }

        $academicYearId = $request->input('academic_year_id', $student->academic_year_id);
        $semester = $request->input('semester', '1');

        $reportCard = ReportCard::with([
            'student.classroom.classLevel',
            'academicYear',
            'classroom.homeroomTeacher'
        ])
        ->where('student_id', $student->id)
        ->where('academic_year_id', $academicYearId)
        ->where('semester', $semester)
        ->whereIn('status', ['published', 'approved'])
        ->first();

        if (!$reportCard) {
            return redirect()->back()->withInput()->with('warning', "Rapor ananda {$student->full_name} untuk periode tersebut belum diterbitkan atau masih dalam proses evaluasi dewan guru.");
        }

        session([
            'parent_authenticated_student_id' => $student->id,
            'parent_authenticated_at' => now()->timestamp,
        ]);

        return redirect()->route('portal.report-cards.view', [
            'id' => $reportCard->id,
            'token' => md5($student->nis . $student->pin_access . $reportCard->id),
        ]);
    }

    /**
     * View digital report card on parent portal.
     */
    public function view(Request $request, $id)
    {
        $reportCard = ReportCard::with([
            'student.classroom.classLevel',
            'student.classLevel',
            'academicYear',
            'classroom.homeroomTeacher'
        ])->findOrFail($id);

        $student = $reportCard->student;
        $expectedToken = md5($student->nis . $student->pin_access . $reportCard->id);

        if ($request->get('token') !== $expectedToken && session('parent_authenticated_student_id') !== $student->id) {
            return redirect()->route('portal.report-cards.index')->with('error', 'Sesi verifikasi telah berakhir. Silakan masukkan kembali NIS dan PIN akses Anda.');
        }

        return view('portal.report-card-view', compact('reportCard', 'student'));
    }

    /**
     * Print or direct stream report card from parent portal.
     */
    public function print(Request $request, $id)
    {
        $reportCard = ReportCard::with([
            'student.classroom.classLevel',
            'student.classLevel',
            'academicYear',
            'classroom.homeroomTeacher'
        ])->findOrFail($id);

        $student = $reportCard->student;
        $expectedToken = md5($student->nis . $student->pin_access . $reportCard->id);

        if ($request->get('token') !== $expectedToken && session('parent_authenticated_student_id') !== $student->id) {
            return redirect()->route('portal.report-cards.index')->with('error', 'Sesi verifikasi telah berakhir. Silakan masukkan kembali NIS dan PIN akses Anda.');
        }

        // If PDF mode, stream the PDF file directly to the browser
        if ($reportCard->entry_mode === 'pdf' && $reportCard->pdf_file) {
            $pdfPath = storage_path('app/public/' . $reportCard->pdf_file);
            if (file_exists($pdfPath)) {
                return response()->file($pdfPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="Rapor_' . str_replace(' ', '_', $student->full_name) . '.pdf"',
                ]);
            }
        }

        return view('admin.reports.cards.print', [
            'reports' => collect([$reportCard]),
            'isBatch' => false,
        ]);
    }
}
