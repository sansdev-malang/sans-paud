<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class AcademicMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tahun Ajaran
        $ay2627 = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'code' => '2627',
                'semester' => 'genap',
                'is_active' => false,
                'start_date' => '2026-07-15',
                'end_date' => '2027-06-25',
                'description' => 'Tahun Pelajaran 2026/2027',
            ]
        );

        $ay2728 = AcademicYear::firstOrCreate(
            ['name' => '2027/2028'],
            [
                'code' => '2728',
                'semester' => 'ganjil',
                'is_active' => true,
                'start_date' => '2027-07-14',
                'end_date' => '2028-06-24',
                'description' => 'Tahun Pelajaran 2027/2028 (Aktif)',
            ]
        );

        // 2. Tingkat Kelas PAUD
        $levelKB = ClassLevel::firstOrCreate(
            ['code' => 'KB'],
            [
                'name' => 'Kelompok Bermain (KB)',
                'order' => 1,
                'description' => 'Kelompok Bermain / Playgroup Usia 2-4 Tahun',
            ]
        );

        $levelTKA = ClassLevel::firstOrCreate(
            ['code' => 'TK-A'],
            [
                'name' => 'TK A',
                'order' => 2,
                'description' => 'Taman Kanak-kanak A Usia 4-5 Tahun',
            ]
        );

        $levelTKB = ClassLevel::firstOrCreate(
            ['code' => 'TK-B'],
            [
                'name' => 'TK B',
                'order' => 3,
                'description' => 'Taman Kanak-kanak B Usia 5-6 Tahun',
            ]
        );

        // 3. Guru Wali Kelas (Ambil guru yang tersedia)
        $teachers = Employee::take(10)->pluck('id')->toArray();

        // 4. Rombongan Belajar (Classrooms) untuk 2027/2028
        $classrooms = [
            [
                'name' => 'KB - Al-Fatih',
                'code' => 'KB-ALF',
                'class_level_id' => $levelKB->id,
                'academic_year_id' => $ay2728->id,
                'homeroom_teacher_id' => $teachers[0] ?? null,
                'capacity' => 15,
                'description' => 'Rombel Kelompok Bermain Al-Fatih',
            ],
            [
                'name' => 'KB - An-Nuur',
                'code' => 'KB-ANN',
                'class_level_id' => $levelKB->id,
                'academic_year_id' => $ay2728->id,
                'homeroom_teacher_id' => $teachers[1] ?? null,
                'capacity' => 15,
                'description' => 'Rombel Kelompok Bermain An-Nuur',
            ],
            [
                'name' => 'TK A - Bintang',
                'code' => 'TKA-BIN',
                'class_level_id' => $levelTKA->id,
                'academic_year_id' => $ay2728->id,
                'homeroom_teacher_id' => $teachers[2] ?? null,
                'capacity' => 20,
                'description' => 'Rombel TK A Bintang',
            ],
            [
                'name' => 'TK A - Bulan',
                'code' => 'TKA-BUL',
                'class_level_id' => $levelTKA->id,
                'academic_year_id' => $ay2728->id,
                'homeroom_teacher_id' => $teachers[3] ?? null,
                'capacity' => 20,
                'description' => 'Rombel TK A Bulan',
            ],
            [
                'name' => 'TK B - Mentari',
                'code' => 'TKB-MEN',
                'class_level_id' => $levelTKB->id,
                'academic_year_id' => $ay2728->id,
                'homeroom_teacher_id' => $teachers[4] ?? null,
                'capacity' => 20,
                'description' => 'Rombel TK B Mentari',
            ],
            [
                'name' => 'TK B - Pelangi',
                'code' => 'TKB-PEL',
                'class_level_id' => $levelTKB->id,
                'academic_year_id' => $ay2728->id,
                'homeroom_teacher_id' => $teachers[5] ?? null,
                'capacity' => 20,
                'description' => 'Rombel TK B Pelangi',
            ],
        ];

        foreach ($classrooms as $c) {
            Classroom::firstOrCreate(
                ['name' => $c['name'], 'academic_year_id' => $c['academic_year_id']],
                $c
            );
        }
    }
}
