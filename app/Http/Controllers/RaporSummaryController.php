<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaporSummaryController extends Controller
{
    /**
     * Display the Data Rapor PAUD (Interactive & Synced with SANS Rapor).
     */
    public function index(Request $request)
    {
        if (!auth()->user()?->canAccessRapor()) {
            abort(403, 'Akses menu Data Rapor hanya untuk Administrator dan Guru yang sedang ditugaskan sebagai Wali Kelas aktif.');
        }

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        // Semesters (Diambil dinamis dari database Master Semester dengan fallback standar 4 semester)
        try {
            $dbSemesters = Semester::orderBy('order', 'asc')->get();
            if ($dbSemesters->isNotEmpty()) {
                $semesters = $dbSemesters->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'code' => $s->code ?: $s->name,
                        'name' => $s->name,
                        'description' => $s->description,
                        'is_active' => (bool) $s->is_active,
                    ];
                });
            } else {
                throw new \Exception('No semesters in database');
            }
        } catch (\Throwable $e) {
            $semesters = collect([
                ['id' => 1, 'code' => 'PTS-1', 'name' => 'Tengah Semester Ganjil', 'description' => 'Penilaian Tengah Semester 1', 'is_active' => true],
                ['id' => 2, 'code' => 'PAS-1', 'name' => 'Semester Ganjil', 'description' => 'Akhir Semester 1', 'is_active' => false],
                ['id' => 3, 'code' => 'PTS-2', 'name' => 'Tengah Semester Genap', 'description' => 'Penilaian Tengah Semester 2', 'is_active' => false],
                ['id' => 4, 'code' => 'PAT-2', 'name' => 'Semester Genap', 'description' => 'Akhir Semester 2', 'is_active' => false],
            ]);
        }

        $activeSemester = $semesters->firstWhere('is_active', true) ?? $semesters->first();
        $selectedSemester = $request->get('semester');
        if (!$selectedSemester) {
            $selectedSemester = $activeSemester ? $activeSemester['code'] : 'PTS-1';
        }

        // Normalisasi format lama jika ada
        if ($selectedSemester === 'ganjil') $selectedSemester = 'PAS-1';
        if ($selectedSemester === 'genap') $selectedSemester = 'PAT-2';
        if ($selectedSemester === 'tengah_ganjil') $selectedSemester = 'PTS-1';
        if ($selectedSemester === 'tengah_genap') $selectedSemester = 'PTS-2';

        $classrooms = Classroom::where('is_active', true)->orderBy('name')->get();
        $selectedClassroomId = $request->get('classroom_id', $classrooms->first()?->id);
        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);

        $students = [];
        $raporUrl = \App\Models\Setting::get('rapor_url', env('SANS_RAPOR_URL', 'http://sans-rapor.test'));
        $ssoSecret = \App\Models\Setting::get('rapor_sso_secret', env('SSO_SECRET_KEY', 'sans_rapor_secret_sso_key_2026'));
        $raporDb = \App\Models\Setting::get('rapor_db_name', env('DB_RAPOR_DATABASE', 'sans-rapor'));

        $narratives = collect();
        $attendances = collect();

        if ($selectedClassroom && $selectedYear) {
            $students = Student::where('classroom_id', $selectedClassroom->id)
                ->where('status', 'aktif')
                ->orderBy('full_name')
                ->get();

            // Fetch narratives and attendances from SANS Rapor database safely
            try {
                $baseConfig = config('database.connections.sans_rapor') ?: config('database.connections.mysql');
                $baseConfig['database'] = $raporDb;
                config(['database.connections.sans_rapor' => $baseConfig]);
                DB::purge('sans_rapor');
                
                $narratives = DB::connection('sans_rapor')
                    ->table('paud_narratives')
                    ->where('classroom_id', $selectedClassroom->id)
                    ->where('academic_year_id', $selectedYear->id)
                    ->where(function($q) use ($selectedSemester) {
                        $q->where('semester', $selectedSemester);
                        if ($selectedSemester === 'PAS-1') $q->orWhere('semester', 'ganjil');
                        if ($selectedSemester === 'PAT-2') $q->orWhere('semester', 'genap');
                        if ($selectedSemester === 'PTS-1') $q->orWhere('semester', 'tengah_ganjil');
                        if ($selectedSemester === 'PTS-2') $q->orWhere('semester', 'tengah_genap');
                    })
                    ->get()
                    ->keyBy('student_id');

                $attendances = DB::connection('sans_rapor')
                    ->table('student_attendances')
                    ->where('unit', 'paud')
                    ->where('classroom_id', $selectedClassroom->id)
                    ->where('academic_year_id', $selectedYear->id)
                    ->where(function($q) use ($selectedSemester) {
                        $q->where('semester', $selectedSemester);
                        if ($selectedSemester === 'PAS-1') $q->orWhere('semester', 'ganjil');
                        if ($selectedSemester === 'PAT-2') $q->orWhere('semester', 'genap');
                        if ($selectedSemester === 'PTS-1') $q->orWhere('semester', 'tengah_ganjil');
                        if ($selectedSemester === 'PTS-2') $q->orWhere('semester', 'tengah_genap');
                    })
                    ->get()
                    ->keyBy('student_id');
            } catch (\Throwable $e) {
                $narratives = collect();
                $attendances = collect();
            }
        }

        // Summary metrics & Status breakdown
        $stats = [
            'total_students' => count($students),
            'approved_count' => 0,
            'submitted_count' => 0,
            'revisi_count' => 0,
            'draft_count' => 0,
            'unfilled_count' => 0,
            'completed_count' => 0,
        ];

        $studentData = collect($students)->map(function ($student) use ($narratives, $attendances, $raporUrl, $selectedClassroom, $selectedYear, $selectedSemester, &$stats) {
            $narrative = $narratives->get($student->id);
            $attendance = $attendances->get($student->id);

            $elemNarratives = !empty($narrative?->element_narratives) 
                ? (is_string($narrative->element_narratives) ? json_decode($narrative->element_narratives, true) : (array) $narrative->element_narratives) 
                : [];

            $hasReligion = !empty($narrative?->element_religion) || !empty($elemNarratives['religion']) || !empty($elemNarratives['agama']);
            $hasIdentity = !empty($narrative?->element_identity) || !empty($elemNarratives['identity']) || !empty($elemNarratives['jati_diri']);
            $hasSteam = !empty($narrative?->element_literacy_steam) || !empty($elemNarratives['literacy_steam']) || !empty($elemNarratives['steam']);
            $hasNarrative = $hasReligion && $hasIdentity && $hasSteam;

            $hasGrowth = !empty($narrative?->growth_height) || !empty($narrative?->growth_weight) || !empty($narrative?->growth_head_circ);
            if ($hasNarrative) $stats['completed_count']++;

            // Status Rapor Calculation
            $status = 'belum_diisi';
            if ($narrative) {
                if (!empty($narrative->status)) {
                    $status = $narrative->status;
                } elseif ($hasNarrative || $hasGrowth) {
                    $status = 'draft';
                }
            }

            if ($status === 'approved') $stats['approved_count']++;
            elseif ($status === 'submitted') $stats['submitted_count']++;
            elseif ($status === 'revisi') $stats['revisi_count']++;
            elseif ($status === 'draft') $stats['draft_count']++;
            else $stats['unfilled_count']++;

            $relText = $narrative?->element_religion ?: ($elemNarratives['religion'] ?? ($elemNarratives['agama'] ?? null));
            $idText = $narrative?->element_identity ?: ($elemNarratives['identity'] ?? ($elemNarratives['jati_diri'] ?? null));
            $stText = $narrative?->element_literacy_steam ?: ($elemNarratives['literacy_steam'] ?? ($elemNarratives['steam'] ?? null));

            $printUrl = "{$raporUrl}/reports/print/{$student->id}?classroom_id={$selectedClassroom?->id}&semester={$selectedSemester}&academic_year_id={$selectedYear?->id}";
            $editUrl = "{$raporUrl}/paud?student_id={$student->id}&classroom_id={$selectedClassroom?->id}&semester={$selectedSemester}";

            return (object) [
                'id' => $student->id,
                'nis' => $student->nis ?? '-',
                'nisn' => $student->nisn ?? null,
                'full_name' => $student->full_name,
                'gender' => $student->gender ?? 'L',
                'status' => $status,
                'review_notes' => $narrative?->review_notes ?? null,
                'has_narrative' => $hasNarrative,
                'has_growth' => $hasGrowth,
                'element_religion' => $relText,
                'element_identity' => $idText,
                'element_literacy_steam' => $stText,
                'height' => $narrative?->growth_height,
                'weight' => $narrative?->growth_weight,
                'head_circ' => $narrative?->growth_head_circ,
                'health_notes' => $narrative?->health_notes,
                'teacher_reflection' => $narrative?->teacher_reflection,
                'sick' => $attendance?->sick ?? 0,
                'permission' => $attendance?->permission ?? 0,
                'unexcused' => $attendance?->unexcused ?? 0,
                'print_url' => $printUrl,
                'edit_url' => $editUrl,
                'student' => $student,
                'narrative' => $narrative,
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
            'role' => $curUser?->role ?? ($isAdmin ? 'super_admin' : 'guru'),
            'unit' => 'paud',
            'timestamp' => time(),
        ]));
        $ssoSig = hash_hmac('sha256', $ssoPayload, $ssoSecret);
        $ssoLaunchUrl = "{$raporUrl}/sso/login?data=" . urlencode($ssoPayload) . "&signature=" . urlencode($ssoSig);

        $exportLegerUrl = "{$raporUrl}/reports/export-leger?classroom_id={$selectedClassroomId}&semester={$selectedSemester}&academic_year_id={$selectedYear?->id}";
        $printClassroomUrl = "{$raporUrl}/reports/print-classroom/{$selectedClassroomId}?semester={$selectedSemester}&academic_year_id={$selectedYear?->id}";

        return view('admin.reports.rapor_summary', [
            'academicYears' => $academicYears,
            'activeAcademicYear' => $activeAcademicYear,
            'selectedYearId' => $selectedYearId,
            'selectedYear' => $selectedYear,
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClassroom,
            'studentData' => $studentData,
            'stats' => $stats,
            'ssoLaunchUrl' => $ssoLaunchUrl,
            'exportLegerUrl' => $exportLegerUrl,
            'printClassroomUrl' => $printClassroomUrl,
            'raporUrl' => $raporUrl,
        ]);
    }
}
