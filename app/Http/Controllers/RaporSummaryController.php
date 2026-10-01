<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaporSummaryController extends Controller
{
    /**
     * Display the Rekap & Hasil Rapor PAUD (Read-Only from SANS Rapor).
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;
        $selectedSemester = $request->get('semester', 'ganjil');

        $classrooms = Classroom::where('is_active', true)->orderBy('name')->get();
        $selectedClassroomId = $request->get('classroom_id', $classrooms->first()?->id);
        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);

        $students = [];
        $narratives = collect();
        $attendances = collect();
        $raporUrl = env('SANS_RAPOR_URL', 'http://sans-rapor.test');
        $ssoSecret = env('SSO_SECRET_KEY', 'sans_rapor_secret_sso_key_2026');

        if ($selectedClassroom && $selectedYear) {
            $students = Student::where('classroom_id', $selectedClassroom->id)
                ->where('status', 'aktif')
                ->orderBy('full_name')
                ->get();

            // Fetch narratives and attendances from SANS Rapor database safely
            try {
                $narratives = DB::connection('sans_rapor')
                    ->table('paud_narratives')
                    ->where('classroom_id', $selectedClassroom->id)
                    ->where('academic_year_id', $selectedYear->id)
                    ->where('semester', $selectedSemester)
                    ->get()
                    ->keyBy('student_id');

                $attendances = DB::connection('sans_rapor')
                    ->table('student_attendances')
                    ->where('unit', 'paud')
                    ->where('classroom_id', $selectedClassroom->id)
                    ->where('academic_year_id', $selectedYear->id)
                    ->where('semester', $selectedSemester)
                    ->get()
                    ->keyBy('student_id');
            } catch (\Throwable $e) {
                // SANS Rapor connection fallback
                $narratives = collect();
                $attendances = collect();
            }
        }

        // Summary metrics
        $totalStudents = count($students);
        $completedCount = 0;
        $growthRecordedCount = 0;

        $studentData = collect($students)->map(function ($student) use ($narratives, $attendances, $raporUrl, $selectedYear, $selectedSemester, &$completedCount, &$growthRecordedCount) {
            $narrative = $narratives->get($student->id);
            $attendance = $attendances->get($student->id);

            $hasReligion = !empty($narrative?->element_religion);
            $hasIdentity = !empty($narrative?->element_identity);
            $hasSteam = !empty($narrative?->element_literacy_steam);
            $isComplete = $hasReligion && $hasIdentity && $hasSteam;

            $hasGrowth = !empty($narrative?->growth_height) || !empty($narrative?->growth_weight);
            if ($isComplete) $completedCount++;
            if ($hasGrowth) $growthRecordedCount++;

            $printUrl = "{$raporUrl}/reports/print/{$student->id}?unit=paud&semester={$selectedSemester}&academic_year_id={$selectedYear?->id}";

            return (object) [
                'student' => $student,
                'narrative' => $narrative,
                'attendance' => $attendance,
                'is_complete' => $isComplete,
                'has_religion' => $hasReligion,
                'has_identity' => $hasIdentity,
                'has_steam' => $hasSteam,
                'has_growth' => $hasGrowth,
                'height' => $narrative?->growth_height,
                'weight' => $narrative?->growth_weight,
                'head_circ' => $narrative?->growth_head_circ,
                'health_notes' => $narrative?->health_notes,
                'teacher_reflection' => $narrative?->teacher_reflection,
                'sick' => $attendance?->sick ?? 0,
                'permission' => $attendance?->permission ?? 0,
                'unexcused' => $attendance?->unexcused ?? 0,
                'print_url' => $printUrl,
            ];
        });

        // SSO Link to Edit in SANS Rapor
        $curUser = auth()->user();
        $isAdmin = in_array($curUser?->role, ['super_admin', 'admin_sd', 'admin_paud', 'admin_smp']);
        $ssoPayload = base64_encode(json_encode([
            'id' => $curUser?->id,
            'name' => $curUser?->name,
            'email' => $curUser?->email,
            'employee_id' => $curUser?->employee_id,
            'role' => $isAdmin ? 'super_admin' : 'guru',
            'unit' => 'paud',
            'timestamp' => time(),
        ]));
        $ssoSig = hash_hmac('sha256', $ssoPayload, $ssoSecret);
        $ssoLaunchUrl = "{$raporUrl}/sso/login?data=" . urlencode($ssoPayload) . "&signature=" . urlencode($ssoSig);

        return view('admin.reports.rapor_summary', [
            'academicYears' => $academicYears,
            'activeAcademicYear' => $activeAcademicYear,
            'selectedYearId' => $selectedYearId,
            'selectedYear' => $selectedYear,
            'selectedSemester' => $selectedSemester,
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClassroom,
            'studentData' => $studentData,
            'totalStudents' => $totalStudents,
            'completedCount' => $completedCount,
            'growthRecordedCount' => $growthRecordedCount,
            'ssoLaunchUrl' => $ssoLaunchUrl,
            'raporUrl' => $raporUrl,
        ]);
    }
}
