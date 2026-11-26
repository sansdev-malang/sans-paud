<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\HomeroomAssignment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaporEkstraSummaryController extends Controller
{
    /**
     * Tampilkan Halaman Rekap & Data Rapor Ekstrakurikuler PAUD.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $isAdmin = $currentUser?->isAdmin() ?? false;
        $isHomeroom = $currentUser?->isActiveHomeroomTeacher() ?? false;
        $isCoordinator = $currentUser?->isExtraCoordinator() ?? false;

        if (!$isAdmin && !$isHomeroom && !$isCoordinator) {
            abort(403, 'Akses menu Data Rapor Ekstra hanya untuk Administrator, Wali Kelas aktif, atau Koordinator Ekstrakurikuler.');
        }

        // 1. Inisialisasi Koneksi Database SAANS Rapor
        $raporUrl = \App\Models\Setting::get('rapor_url', env('SANS_RAPOR_URL', 'http://sans-rapor.test'));
        $ssoSecret = \App\Models\Setting::get('rapor_sso_secret', env('SSO_SECRET_KEY', 'sans_rapor_secret_sso_key_2026'));
        $raporDb = \App\Models\Setting::get('rapor_db_name', env('DB_RAPOR_DATABASE', 'sans-rapor'));

        try {
            $baseConfig = config('database.connections.sans_rapor') ?: config('database.connections.mysql');
            $baseConfig['database'] = $raporDb;
            config(['database.connections.sans_rapor' => $baseConfig]);
            DB::purge('sans_rapor');
        } catch (\Throwable $e) {
            // Abaikan jika konfigurasi sudah siap
        }

        // 2. Filter Tahun Ajaran
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;

        // 3. Filter Semester (Ekstrakurikuler PAUD hanya PAS-1 / Ganjil dan PAT-2 / Genap)
        $semesters = collect([
            ['id' => 2, 'code' => 'PAS-1', 'name' => 'Semester 1 (Ganjil)', 'description' => 'Akhir Semester 1', 'is_active' => false],
            ['id' => 4, 'code' => 'PAT-2', 'name' => 'Semester 2 (Genap)', 'description' => 'Akhir Semester 2', 'is_active' => false],
        ]);

        $selectedSemester = $request->get('semester');
        if (!$selectedSemester) {
            $selectedSemester = 'PAS-1';
        }
        if (in_array(strtolower($selectedSemester), ['genap', 'pat-2', 'pat_2', 'pts-2', '2'])) {
            $selectedSemester = 'PAT-2';
        } else {
            $selectedSemester = 'PAS-1';
        }

        // 4. Deteksi Kegiatan Ekstra yang Dikoordinasikan oleh User Ini
        $cleanName = trim(explode(',', $currentUser->name)[0]);
        $emp = $currentUser->employee;
        $empNiy = ($emp && !empty($emp->niy) && $emp->niy !== '-') ? $emp->niy : null;

        $userCoordinatedActivityIds = [];
        try {
            $userCoordinatedActivityIds = DB::connection('sans_rapor')
                ->table('paud_extracurricular_activities')
                ->where('is_active', true)
                ->where('unit', 'paud')
                ->where(function ($q) use ($currentUser, $cleanName, $empNiy) {
                    $q->where('coordinator_id', $currentUser->id)
                      ->orWhere('coordinator_name', 'like', "%{$cleanName}%")
                      ->orWhere('level_coordinators', 'like', "%{$cleanName}%");
                    if ($empNiy) {
                        $q->orWhere('coordinator_niy', $empNiy)
                          ->orWhere('level_coordinators', 'like', "%{$empNiy}%");
                    }
                })
                ->pluck('id')
                ->toArray();
        } catch (\Throwable $e) {
            $userCoordinatedActivityIds = [];
        }

        $isStrictCoordinator = $isCoordinator && !$isAdmin && !$isHomeroom;

        // 5. Query Master Kegiatan Ekstrakurikuler Sesuai Hak Akses
        $activitiesQuery = DB::connection('sans_rapor')
            ->table('paud_extracurricular_activities')
            ->where('is_active', true)
            ->where('unit', 'paud')
            ->orderBy('type', 'desc') // 'wajib' terlebih dahulu, baru 'pilihan'
            ->orderBy('name');

        if ($isStrictCoordinator) {
            // Guru yang hanya koordinator: HANYA bisa melihat kegiatan ekstra miliknya
            $activitiesQuery->whereIn('id', $userCoordinatedActivityIds);
        }

        $activities = $activitiesQuery->get();

        if ($isStrictCoordinator && $activities->isEmpty()) {
            abort(403, 'Anda belum ditugaskan sebagai koordinator pada kegiatan ekstrakurikuler aktif manapun.');
        }

        // 6. Penentuan Selected Activity
        $rawActivityId = $request->get('activity_id');
        if ($isStrictCoordinator) {
            if (!$rawActivityId || !in_array((int)$rawActivityId, $userCoordinatedActivityIds)) {
                $selectedActivityId = (string) ($activities->first()?->id ?? 'none');
            } else {
                $selectedActivityId = (string) $rawActivityId;
            }
        } else {
            $selectedActivityId = $rawActivityId ?: 'all';
        }
        $selectedActivity = $selectedActivityId !== 'all' ? $activities->firstWhere('id', (int)$selectedActivityId) : null;

        // 7. Scoping Kelompok / Rombel Belajar Sesuai Hak Akses
        $assignedClassroomIds = collect();
        if ($isHomeroom && $currentUser->employee_id) {
            $assignedClassroomIds = HomeroomAssignment::where('employee_id', $currentUser->employee_id)
                ->where('academic_year_id', $selectedYear?->id)
                ->where('is_active', true)
                ->pluck('classroom_id');
        }

        $classroomQuery = Classroom::where('is_active', true)->orderBy('name');
        if ($selectedYear?->id) {
            $classroomQuery->where('academic_year_id', $selectedYear->id);
        }

        if ($isHomeroom && !$isAdmin && !$isStrictCoordinator) {
            // Wali Kelas: dibatasi hanya ke rombel binaannya
            if ($assignedClassroomIds->isNotEmpty()) {
                $classroomQuery->whereIn('id', $assignedClassroomIds);
            }
        }

        $classrooms = $classroomQuery->get();

        // 8. Penentuan Selected Classroom
        $rawClassroomId = $request->get('classroom_id');
        if ($isHomeroom && !$isAdmin && !$isStrictCoordinator) {
            // Wali Kelas: default ke kelas binaan pertamanya
            if (!$rawClassroomId || !$classrooms->contains('id', (int)$rawClassroomId)) {
                $selectedClassroomId = (string) ($classrooms->first()?->id ?? '');
            } else {
                $selectedClassroomId = (string) $rawClassroomId;
            }
        } else {
            // Admin & Koordinator Ekstra: default 'all' (Semua Rombel)
            $selectedClassroomId = $rawClassroomId ?: 'all';
        }
        $selectedClassroom = $selectedClassroomId !== 'all' ? $classrooms->firstWhere('id', (int)$selectedClassroomId) : null;

        // 9. Ambil Data Keanggotaan & Penilaian Ekstrakurikuler
        $items = collect();
        $stats = [
            'total_items' => 0,
            'lengkap_count' => 0,
            'belum_lengkap_count' => 0,
            'belum_diisi_count' => 0,
            'progress_percent' => 0,
        ];

        if ($selectedYear) {
            // Ambil semua murid relevan
            $studentsQuery = Student::where('status', 'aktif');
            if ($selectedClassroom) {
                $studentsQuery->where('classroom_id', $selectedClassroom->id);
            } elseif ($isHomeroom && !$isAdmin && !$isStrictCoordinator && $assignedClassroomIds->isNotEmpty()) {
                $studentsQuery->whereIn('classroom_id', $assignedClassroomIds);
            }

            $allStudents = $studentsQuery->with(['classroom.jenjang'])->orderBy('full_name')->get()->keyBy('id');
            $studentIds = $allStudents->keys()->toArray();

            if (!empty($studentIds)) {
                // Ambil pendaftaran anggota dari paud_extracurricular_members
                $membersQuery = DB::connection('sans_rapor')
                    ->table('paud_extracurricular_members')
                    ->where('academic_year_id', $selectedYear->id)
                    ->whereIn('student_id', $studentIds)
                    ->where(function ($q) use ($selectedSemester) {
                        $q->where('semester', $selectedSemester);
                        if ($selectedSemester === 'PAS-1') $q->orWhere('semester', 'ganjil');
                        if ($selectedSemester === 'PAT-2') $q->orWhere('semester', 'genap');
                    });

                if ($selectedActivity) {
                    $membersQuery->where('activity_id', $selectedActivity->id);
                } elseif ($isStrictCoordinator) {
                    $membersQuery->whereIn('activity_id', $userCoordinatedActivityIds);
                }

                $members = $membersQuery->get();

                // Ambil assessments dari paud_extracurricular_assessments
                $assessmentsQuery = DB::connection('sans_rapor')
                    ->table('paud_extracurricular_assessments')
                    ->where('academic_year_id', $selectedYear->id)
                    ->whereIn('student_id', $studentIds)
                    ->where(function ($q) use ($selectedSemester) {
                        $q->where('semester', $selectedSemester);
                        if ($selectedSemester === 'PAS-1') $q->orWhere('semester', 'ganjil');
                        if ($selectedSemester === 'PAT-2') $q->orWhere('semester', 'genap');
                    });

                if ($selectedActivity) {
                    $assessmentsQuery->where('activity_id', $selectedActivity->id);
                } elseif ($isStrictCoordinator) {
                    $assessmentsQuery->whereIn('activity_id', $userCoordinatedActivityIds);
                }

                $assessments = $assessmentsQuery->get();

                // Group assessments by student_id and activity_id
                $assessmentMap = [];
                foreach ($assessments as $a) {
                    $assessmentMap[$a->student_id . '_' . $a->activity_id] = $a;
                }

                // Ambil data naratif untuk fallback ekstra wajib (Renang)
                $narratives = DB::connection('sans_rapor')
                    ->table('paud_narratives')
                    ->where('academic_year_id', $selectedYear->id)
                    ->whereIn('student_id', $studentIds)
                    ->where(function ($q) use ($selectedSemester) {
                        $q->where('semester', $selectedSemester);
                        if ($selectedSemester === 'PAS-1') $q->orWhere('semester', 'ganjil');
                        if ($selectedSemester === 'PAT-2') $q->orWhere('semester', 'genap');
                    })
                    ->get()
                    ->keyBy('student_id');

                // Map master activities by ID
                $activityMap = $activities->keyBy('id');

                // Kumpulkan setiap entri (Student + Activity)
                $processedKeys = [];

                foreach ($members as $member) {
                    $student = $allStudents->get($member->student_id);
                    $activity = $activityMap->get($member->activity_id);
                    if (!$student || !$activity) continue;

                    $pairKey = $student->id . '_' . $activity->id;
                    $processedKeys[$pairKey] = true;

                    $assessment = $assessmentMap[$pairKey] ?? null;

                    // Fallback untuk Renang (wajib) dari paud_narratives
                    $savedGrades = [];
                    $teacherAdvice = $assessment?->teacher_advice;
                    $attendancePresent = $assessment?->attendance_present ?? ($activity->total_meetings ?: 6);
                    $attendanceTotal = $assessment?->attendance_total ?? ($activity->total_meetings ?: 6);
                    $pdfPath = $assessment?->pdf_path;
                    $pdfOriginalName = $assessment?->pdf_original_name ?? null;

                    if ($assessment && !empty($assessment->indicator_grades)) {
                        $savedGrades = is_string($assessment->indicator_grades)
                            ? json_decode($assessment->indicator_grades, true)
                            : (array) $assessment->indicator_grades;
                    } elseif ($activity->type === 'wajib' && $narratives->has($student->id)) {
                        $narrative = $narratives->get($student->id);
                        $extras = !empty($narrative->extracurriculars)
                            ? (is_string($narrative->extracurriculars) ? json_decode($narrative->extracurriculars, true) : (array)$narrative->extracurriculars)
                            : [];
                        foreach ($extras as $ex) {
                            if (($ex['id'] ?? '') === $activity->code || ($ex['activity_id'] ?? null) == $activity->id || ($ex['type'] ?? '') === 'wajib') {
                                if (isset($ex['indicators']) && is_array($ex['indicators'])) {
                                    foreach ($ex['indicators'] as $ind) {
                                        if (isset($ind['id']) && isset($ind['grade'])) {
                                            $savedGrades[$ind['id']] = $ind['grade'];
                                        }
                                    }
                                }
                                if (empty($teacherAdvice) && !empty($ex['teacher_advice'])) {
                                    $teacherAdvice = $ex['teacher_advice'];
                                }
                                break;
                            }
                        }
                    }

                    // Parsing default indicators
                    $indicators = !empty($activity->default_indicators)
                        ? (is_string($activity->default_indicators) ? json_decode($activity->default_indicators, true) : (array)$activity->default_indicators)
                        : [];
                    $totalIndCount = count($indicators);
                    $gradedCount = count($savedGrades);

                    // Evaluasi Status Kelengkapan
                    $isRobotic = ($activity->code === 'robotic') || ($activity->assessment_type ?? '') === 'pdf_upload';
                    $status = 'belum_diisi';

                    if ($isRobotic) {
                        if (!empty($pdfPath) || $assessment) {
                            $status = 'lengkap';
                        }
                    } else {
                        if ($totalIndCount > 0 && $gradedCount >= $totalIndCount && !empty($teacherAdvice)) {
                            $status = 'lengkap';
                        } elseif ($gradedCount > 0 || !empty($teacherAdvice)) {
                            $status = 'belum_lengkap';
                        }
                    }

                    if ($status === 'lengkap') $stats['lengkap_count']++;
                    elseif ($status === 'belum_lengkap') $stats['belum_lengkap_count']++;
                    else $stats['belum_diisi_count']++;

                    $classId = $student->classroom_id ?: ($selectedClassroom?->id ?: '');

                    $printUrl = "{$raporUrl}/reports/print-extra/{$student->id}?classroom_id={$classId}&semester={$selectedSemester}&academic_year_id={$selectedYear->id}&activity_id={$activity->id}";
                    $editUrl = "{$raporUrl}/paud/extracurricular?activity_id={$activity->id}&student_id={$student->id}&classroom_id={$classId}&semester={$selectedSemester}";

                    $items->push((object) [
                        'student_id' => $student->id,
                        'nis' => $student->nis ?? '-',
                        'nisn' => $student->nisn ?? null,
                        'full_name' => $student->full_name,
                        'gender' => $student->gender ?? 'L',
                        'classroom_id' => $classId,
                        'classroom_name' => $student->classroom?->name ?? ($classrooms->firstWhere('id', $classId)?->name ?? '-'),
                        'activity_id' => $activity->id,
                        'activity_name' => $activity->name,
                        'activity_type' => $activity->type,
                        'activity_code' => $activity->code,
                        'is_robotic' => $isRobotic,
                        'total_meetings' => $activity->total_meetings ?: 6,
                        'attendance_present' => $attendancePresent,
                        'attendance_total' => $attendanceTotal,
                        'indicator_grades' => $savedGrades,
                        'graded_count' => $gradedCount,
                        'total_indicators' => $totalIndCount,
                        'indicators' => $indicators,
                        'teacher_advice' => $teacherAdvice,
                        'pdf_path' => $pdfPath,
                        'pdf_original_name' => $pdfOriginalName,
                        'coach_name' => $assessment?->coach_name ?: ($activity->coordinator_name ?: '-'),
                        'status' => $status,
                        'print_url' => $printUrl,
                        'edit_url' => $editUrl,
                        'updated_at' => $assessment?->updated_at ? Carbon::parse($assessment->updated_at)->translatedFormat('d M Y H:i') : null,
                    ]);
                }

                // Khusus Ekstra Wajib (Renang): Otomatis sertakan murid TK di rombel binaan jika belum ada di tabel members
                $renangActivity = $activities->firstWhere('type', 'wajib');
                if ($renangActivity && ($selectedActivityId === 'all' || (int)$selectedActivityId === (int)$renangActivity->id)) {
                    foreach ($allStudents as $student) {
                        $cls = $student->classroom;
                        $isTkClass = $cls && ($cls->jenjang?->code === 'TK' || str_contains(strtoupper($cls->name), 'TK'));
                        if ($isTkClass) {
                            $pairKey = $student->id . '_' . $renangActivity->id;
                            if (!isset($processedKeys[$pairKey])) {
                                $processedKeys[$pairKey] = true;
                                $assessment = $assessmentMap[$pairKey] ?? null;

                                $savedGrades = [];
                                $teacherAdvice = $assessment?->teacher_advice;
                                $attendancePresent = $assessment?->attendance_present ?? ($renangActivity->total_meetings ?: 6);
                                $attendanceTotal = $assessment?->attendance_total ?? ($renangActivity->total_meetings ?: 6);

                                if ($assessment && !empty($assessment->indicator_grades)) {
                                    $savedGrades = is_string($assessment->indicator_grades)
                                        ? json_decode($assessment->indicator_grades, true)
                                        : (array) $assessment->indicator_grades;
                                } elseif ($narratives->has($student->id)) {
                                    $narrative = $narratives->get($student->id);
                                    $extras = !empty($narrative->extracurriculars)
                                        ? (is_string($narrative->extracurriculars) ? json_decode($narrative->extracurriculars, true) : (array)$narrative->extracurriculars)
                                        : [];
                                    foreach ($extras as $ex) {
                                        if (($ex['id'] ?? '') === $renangActivity->code || ($ex['activity_id'] ?? null) == $renangActivity->id || ($ex['type'] ?? '') === 'wajib') {
                                            if (isset($ex['indicators']) && is_array($ex['indicators'])) {
                                                foreach ($ex['indicators'] as $ind) {
                                                    if (isset($ind['id']) && isset($ind['grade'])) {
                                                        $savedGrades[$ind['id']] = $ind['grade'];
                                                    }
                                                }
                                            }
                                            if (empty($teacherAdvice) && !empty($ex['teacher_advice'])) {
                                                $teacherAdvice = $ex['teacher_advice'];
                                            }
                                            break;
                                        }
                                    }
                                }

                                $indicators = !empty($renangActivity->default_indicators)
                                    ? (is_string($renangActivity->default_indicators) ? json_decode($renangActivity->default_indicators, true) : (array)$renangActivity->default_indicators)
                                    : [];
                                $totalIndCount = count($indicators);
                                $gradedCount = count($savedGrades);

                                $status = 'belum_diisi';
                                if ($totalIndCount > 0 && $gradedCount >= $totalIndCount && !empty($teacherAdvice)) {
                                    $status = 'lengkap';
                                } elseif ($gradedCount > 0 || !empty($teacherAdvice)) {
                                    $status = 'belum_lengkap';
                                }

                                if ($status === 'lengkap') $stats['lengkap_count']++;
                                elseif ($status === 'belum_lengkap') $stats['belum_lengkap_count']++;
                                else $stats['belum_diisi_count']++;

                                $classId = $student->classroom_id ?: ($selectedClassroom?->id ?: '');
                                $printUrl = "{$raporUrl}/reports/print-extra/{$student->id}?classroom_id={$classId}&semester={$selectedSemester}&academic_year_id={$selectedYear->id}&activity_id={$renangActivity->id}";
                                $editUrl = "{$raporUrl}/paud/extracurricular?activity_id={$renangActivity->id}&student_id={$student->id}&classroom_id={$classId}&semester={$selectedSemester}";

                                $items->push((object) [
                                    'student_id' => $student->id,
                                    'nis' => $student->nis ?? '-',
                                    'nisn' => $student->nisn ?? null,
                                    'full_name' => $student->full_name,
                                    'gender' => $student->gender ?? 'L',
                                    'classroom_id' => $classId,
                                    'classroom_name' => $student->classroom?->name ?? ($classrooms->firstWhere('id', $classId)?->name ?? '-'),
                                    'activity_id' => $renangActivity->id,
                                    'activity_name' => $renangActivity->name,
                                    'activity_type' => $renangActivity->type,
                                    'activity_code' => $renangActivity->code,
                                    'is_robotic' => false,
                                    'total_meetings' => $renangActivity->total_meetings ?: 6,
                                    'attendance_present' => $attendancePresent,
                                    'attendance_total' => $attendanceTotal,
                                    'indicator_grades' => $savedGrades,
                                    'graded_count' => $gradedCount,
                                    'total_indicators' => $totalIndCount,
                                    'indicators' => $indicators,
                                    'teacher_advice' => $teacherAdvice,
                                    'pdf_path' => null,
                                    'pdf_original_name' => null,
                                    'coach_name' => $assessment?->coach_name ?: ($renangActivity->coordinator_name ?: '-'),
                                    'status' => $status,
                                    'print_url' => $printUrl,
                                    'edit_url' => $editUrl,
                                    'updated_at' => $assessment?->updated_at ? Carbon::parse($assessment->updated_at)->translatedFormat('d M Y H:i') : null,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $stats['total_items'] = $items->count();
        $stats['progress_percent'] = $stats['total_items'] > 0
            ? min(100, (int) round(($stats['lengkap_count'] / $stats['total_items']) * 100))
            : 0;

        // 10. Link SSO Peluncuran ke E-Rapor
        $ssoRole = $currentUser?->role;
        if ($currentUser?->hasRole('super_admin')) {
            $ssoRole = 'super_admin';
        } elseif ($currentUser?->hasRole('kepala_sekolah')) {
            $ssoRole = 'kepala_sekolah';
        } elseif ($isHomeroom) {
            $ssoRole = 'wali_kelas';
        } elseif ($isCoordinator) {
            $ssoRole = 'koordinator_ekstra';
        }

        $ssoPayload = base64_encode(json_encode([
            'id' => $currentUser?->id,
            'name' => $currentUser?->name,
            'email' => $currentUser?->email,
            'employee_id' => $currentUser?->employee_id,
            'role' => $ssoRole ?? 'guru',
            'unit' => 'paud',
            'timestamp' => time(),
        ]));
        $ssoSig = hash_hmac('sha256', $ssoPayload, $ssoSecret);
        $ssoLaunchUrl = "{$raporUrl}/sso/login?data=" . urlencode($ssoPayload) . "&signature=" . urlencode($ssoSig);

        // Link Mass Action Cetak & Leger
        $actParam = $selectedActivityId !== 'all' ? $selectedActivityId : '';
        $clsParam = $selectedClassroomId !== 'all' ? $selectedClassroomId : '';

        $exportLegerUrl = "{$raporUrl}/reports/export-leger?classroom_id={$clsParam}&semester={$selectedSemester}&academic_year_id={$selectedYear?->id}&activity_id={$actParam}&type=extra";
        $printClassroomUrl = "{$raporUrl}/reports/print-classroom/{$clsParam}?semester={$selectedSemester}&academic_year_id={$selectedYear?->id}&activity_id={$actParam}&type=extra";

        return view('admin.reports.rapor_ekstra_summary', [
            'academicYears' => $academicYears,
            'activeAcademicYear' => $activeAcademicYear,
            'selectedYearId' => $selectedYearId,
            'selectedYear' => $selectedYear,
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'activities' => $activities,
            'selectedActivityId' => $selectedActivityId,
            'selectedActivity' => $selectedActivity,
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClassroom,
            'items' => $items,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
            'isHomeroom' => $isHomeroom,
            'isCoordinator' => $isCoordinator,
            'isStrictCoordinator' => $isStrictCoordinator,
            'ssoLaunchUrl' => $ssoLaunchUrl,
            'exportLegerUrl' => $exportLegerUrl,
            'printClassroomUrl' => $printClassroomUrl,
            'raporUrl' => $raporUrl,
        ]);
    }
}
