<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\StudentAttendance;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $academicYearId = $activeYear ? $activeYear->id : 1;

        $records = [];

        // Helper to add record
        $add = function ($studentId, $classroomId, $date, $status, $recordedBy) use (&$records, $academicYearId) {
            $records[] = [
                'student_id' => $studentId,
                'classroom_id' => $classroomId,
                'date' => $date,
                'status' => $status,
                'recorded_by' => $recordedBy,
                'academic_year_id' => $academicYearId,
            ];
        };

        // 1. TPA 2 (Classroom ID 21, Recorded By 39)
        // Isa Syahdan Al Biruni (46)
        $add(46, 21, '2026-11-05', 'H', 39);
        $add(46, 21, '2026-11-11', 'H', 39);
        $add(46, 21, '2026-11-12', 'S', 39);
        $add(46, 21, '2026-11-13', 'S', 39);

        // Muhammad Keenan (51)
        $add(51, 21, '2026-11-05', 'H', 39);
        $add(51, 21, '2026-11-11', 'H', 39);
        $add(51, 21, '2026-11-12', 'H', 39);
        $add(51, 21, '2026-11-13', 'H', 39);

        // 2. TPA 3 (Classroom ID 22, Recorded By 41)
        // Latisha Kyna Maisara (137) - all active dates in Nov
        $tpa3ActiveDates = [
            '2026-11-02', '2026-11-03', '2026-11-04', '2026-11-05', '2026-11-06',
            '2026-11-09', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13',
            '2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20',
            '2026-11-21', '2026-11-23', '2026-11-24'
        ];
        foreach ($tpa3ActiveDates as $d) {
            $add(137, 22, $d, 'H', 41);
        }

        // Danish Al Fath Prastyo (82)
        $danishTpa3 = [
            '2026-11-09' => 'H', '2026-11-10' => 'H', '2026-11-11' => 'I',
            '2026-11-12' => 'H', '2026-11-13' => 'I', '2026-11-16' => 'I',
            '2026-11-17' => 'I', '2026-11-18' => 'I', '2026-11-19' => 'I',
            '2026-11-20' => 'I', '2026-11-23' => 'H',
        ];
        foreach ($danishTpa3 as $d => $s) {
            $add(82, 22, $d, $s, 41);
        }

        // Jibran Fatih Abdurrazzaq (84)
        $jibranTpa3Dates = [
            '2026-11-09', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13',
            '2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19', '2026-11-20',
            '2026-11-23'
        ];
        foreach ($jibranTpa3Dates as $d) {
            $add(84, 22, $d, 'H', 41);
        }

        // Aqila Farzana Firmansyah (58) in TPA 3
        $add(58, 22, '2026-11-18', 'H', 41);
        $add(58, 22, '2026-11-19', 'S', 41);
        $add(58, 22, '2026-11-20', 'S', 41);

        // Nafisa Adeeva Alfian Putri (65) in TPA 3
        $add(65, 22, '2026-11-18', 'H', 41);
        $add(65, 22, '2026-11-19', 'H', 41);
        $add(65, 22, '2026-11-20', 'H', 41);

        // Jihan (110), Kayzan (111), Shafalauna (116) in TPA 3
        foreach ([110, 111, 116] as $sid) {
            $add($sid, 22, '2026-11-23', 'H', 41);
            $add($sid, 22, '2026-11-24', 'H', 41);
        }

        // Muhammad Gibran Ekawira (128) in TPA 3
        foreach (['2026-11-18', '2026-11-19', '2026-11-20', '2026-11-23', '2026-11-24'] as $d) {
            $add(128, 22, $d, 'H', 41);
        }

        // Nayyara Aufaa Azkadina (155) in TPA 3
        $add(155, 22, '2026-11-18', 'H', 41);
        $add(155, 22, '2026-11-19', 'H', 41);
        $add(155, 22, '2026-11-20', 'H', 41);

        // 3. Restore formal classes where records were previously overwritten
        // Aqila (58) in TK A1 (Class 12, Recorded by 20)
        $aqilaFormal = [
            '2026-11-02' => 'H', '2026-11-03' => 'H', '2026-11-05' => 'H', '2026-11-06' => 'H',
            '2026-11-09' => 'H', '2026-11-10' => 'H', '2026-11-16' => 'H', '2026-11-17' => 'I',
            '2026-11-23' => 'S', '2026-11-24' => 'S'
        ];
        foreach ($aqilaFormal as $d => $s) {
            $add(58, 12, $d, $s, 20);
        }

        // Nafisa (65) in TK A1 (Class 12, Recorded by 20)
        $nafisaFormal = [
            '2026-11-02' => 'H', '2026-11-03' => 'H', '2026-11-05' => 'H', '2026-11-06' => 'A',
            '2026-11-09' => 'S', '2026-11-10' => 'S', '2026-11-16' => 'H', '2026-11-17' => 'H',
            '2026-11-23' => 'H', '2026-11-24' => 'A'
        ];
        foreach ($nafisaFormal as $d => $s) {
            $add(65, 12, $d, $s, 20);
        }

        // Jihan (110) in TK B1 (Class 16, Recorded by 36)
        $jihanFormal = [
            '2026-11-02' => 'H', '2026-11-09' => 'H', '2026-11-10' => 'H',
            '2026-11-16' => 'H', '2026-11-17' => 'H', '2026-11-18' => 'S', '2026-11-19' => 'H'
        ];
        foreach ($jihanFormal as $d => $s) {
            $add(110, 16, $d, $s, 36);
        }

        // Kayzan (111) in TK B1 (Class 16, Recorded by 36)
        $kayzanFormal = [
            '2026-11-02' => 'H', '2026-11-09' => 'H', '2026-11-10' => 'H',
            '2026-11-16' => 'H', '2026-11-17' => 'H', '2026-11-18' => 'H', '2026-11-19' => 'H'
        ];
        foreach ($kayzanFormal as $d => $s) {
            $add(111, 16, $d, $s, 36);
        }

        // Shafalauna (116) in TK B1 (Class 16, Recorded by 36)
        $shafalaunaFormal = [
            '2026-11-02' => 'H', '2026-11-09' => 'H', '2026-11-10' => 'H',
            '2026-11-16' => 'H', '2026-11-17' => 'H', '2026-11-18' => 'S', '2026-11-19' => 'S'
        ];
        foreach ($shafalaunaFormal as $d => $s) {
            $add(116, 16, $d, $s, 36);
        }

        // Nayyara (155) in TK B4 (Class 19, Recorded by 44)
        $nayyaraFormal = [
            '2026-11-02' => 'H', '2026-11-03' => 'H', '2026-11-04' => 'H', '2026-11-05' => 'H',
            '2026-11-06' => 'H', '2026-11-09' => 'H', '2026-11-10' => 'H', '2026-11-11' => 'H',
            '2026-11-12' => 'H', '2026-11-13' => 'H', '2026-11-16' => 'H', '2026-11-17' => 'I'
        ];
        foreach ($nayyaraFormal as $d => $s) {
            $add(155, 19, $d, $s, 44);
        }

        // Isa (46) & Keenan (51) in KB B3 (Class 11, Recorded by 43)
        $add(46, 11, '2026-11-16', 'S', 43);
        $add(46, 11, '2026-11-18', 'S', 43);
        $add(51, 11, '2026-11-13', 'H', 43);
        $add(51, 11, '2026-11-16', 'H', 43);
        $add(51, 11, '2026-11-18', 'H', 43);

        // Jibran (84) in TK A3 (Class 14, Recorded by 9)
        $add(84, 14, '2026-11-11', 'H', 9);

        // Process all via idempotent firstOrCreate
        DB::transaction(function () use ($records) {
            $now = now();
            foreach ($records as $item) {
                StudentAttendance::firstOrCreate(
                    [
                        'student_id' => $item['student_id'],
                        'classroom_id' => $item['classroom_id'],
                        'date' => $item['date'],
                    ],
                    [
                        'status' => $item['status'],
                        'recorded_by' => $item['recorded_by'],
                        'academic_year_id' => $item['academic_year_id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to avoid accidental loss of attendance
    }
};
