<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Student;
use App\Models\StudentClassroomHistory;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ImportStudentsDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:import-database {file? : Path to the excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import official student database, levels, and classrooms from DB_RAPORT_ANAK_SALEH.xlsx for PG - TK - DAYCARE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?: public_path('DB_RAPORT_ANAK_SALEH.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan di: {$filePath}");
            return 1;
        }

        $this->info("Membaca file Excel: {$filePath}...");

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        // 1. Setup / Dapatkan Tahun Ajaran 2026/2027 (Berjalan)
        $academicYear = AcademicYear::where('name', '2026/2027')->where('is_active', true)->first()
            ?: (AcademicYear::where('name', '2026/2027')->first()
            ?: (AcademicYear::where('name', '2026-2027')->first()
            ?: (AcademicYear::where('code', '2627')->first()
            ?: (AcademicYear::where('is_active', true)->first()
            ?: AcademicYear::firstOrCreate(
                ['name' => '2026/2027'],
                [
                    'code' => '2627',
                    'semester' => 'ganjil',
                    'is_active' => true,
                    'start_date' => '2026-07-15',
                    'end_date' => '2027-06-30',
                    'description' => 'Tahun Pelajaran 2026/2027 (Berjalan) PG-TK-Daycare Anak Saleh',
                ]
            )))));

        $this->info("Tahun Ajaran Terpilih: {$academicYear->name} (ID: {$academicYear->id})");

        // 2. Setup Jenjang per Sub-Unit (PG, TK, DAYCARE, TPQ)
        $levelDefinitions = [
            'KB-A' => ['name' => 'KB-A', 'sub_unit' => 'PG', 'order' => 1, 'desc' => 'Jenjang Kelompok Bermain A (Playgroup)'],
            'KB-B' => ['name' => 'KB-B', 'sub_unit' => 'PG', 'order' => 2, 'desc' => 'Jenjang Kelompok Bermain B (Playgroup)'],
            'TK-A' => ['name' => 'TK-A', 'sub_unit' => 'TK', 'order' => 3, 'desc' => 'Jenjang Taman Kanak-Kanak A'],
            'TK-B' => ['name' => 'TK-B', 'sub_unit' => 'TK', 'order' => 4, 'desc' => 'Jenjang Taman Kanak-Kanak B'],
            'TPA-1' => ['name' => 'TPA 1', 'sub_unit' => 'DAYCARE', 'order' => 5, 'desc' => 'Layanan Daycare / TPA 1'],
            'TPA-2' => ['name' => 'TPA 2', 'sub_unit' => 'DAYCARE', 'order' => 6, 'desc' => 'Layanan Daycare / TPA 2'],
            'TPA-3' => ['name' => 'TPA 3', 'sub_unit' => 'DAYCARE', 'order' => 7, 'desc' => 'Layanan Daycare / TPA 3'],
            'TPQ' => ['name' => 'TPQ', 'sub_unit' => 'TPQ', 'order' => 8, 'desc' => 'Program Taman Pendidikan Al-Qur\'an'],
        ];

        $levels = [];
        foreach ($levelDefinitions as $code => $def) {
            $levels[$code] = ClassLevel::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $def['name'],
                    'sub_unit' => $def['sub_unit'],
                    'order' => $def['order'],
                    'description' => $def['desc'],
                ]
            );
        }

        $this->info("Jenjang untuk PG, TK, DAYCARE, dan TPQ berhasil disiapkan!");

        // 3. Load Daftar Pegawai untuk pemetaan Wali Kelas
        $employees = Employee::whereIn('status', ['Active', 'aktif', 'active', 'Aktif'])->get();

        // 4. Setup 16 Kelompok (Rombel) Resmi PG-TK-Daycare-TPQ
        $classroomDefinitions = [
            // Sub-Unit PG
            'KB A1' => ['level' => 'KB-A', 'sub_unit' => 'PG', 'name' => 'KB A1', 'code' => 'KB-A1', 'wali' => 'Lilis Rachmawati', 'capacity' => 15],
            'KB A2' => ['level' => 'KB-A', 'sub_unit' => 'PG', 'name' => 'KB A2', 'code' => 'KB-A2', 'wali' => 'Lilis Rachmawati', 'capacity' => 15],
            'KB B1' => ['level' => 'KB-B', 'sub_unit' => 'PG', 'name' => 'KB B1', 'code' => 'KB-B1', 'wali' => 'Linda Romadhona', 'capacity' => 15],
            'KB B2' => ['level' => 'KB-B', 'sub_unit' => 'PG', 'name' => 'KB B2', 'code' => 'KB-B2', 'wali' => 'Ermia Widayanti', 'capacity' => 15],
            'KB B3' => ['level' => 'KB-B', 'sub_unit' => 'PG', 'name' => 'KB B3', 'code' => 'KB-B3', 'wali' => 'Septi Ismawiyanti', 'capacity' => 15],

            // Sub-Unit TK
            'TK A1' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A1', 'code' => 'TK-A1', 'wali' => 'Fauzia Faricha', 'capacity' => 20],
            'TK A2' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A2', 'code' => 'TK-A2', 'wali' => 'Martdiyah', 'capacity' => 20],
            'TK A3' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A3', 'code' => 'TK-A3', 'wali' => 'Alfi Azizah', 'capacity' => 20],
            'TK A4' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A4', 'code' => 'TK-A4', 'wali' => 'Kholilah', 'capacity' => 20],
            'TK B1' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B1', 'code' => 'TK-B1', 'wali' => 'Novita Rokhmawati', 'capacity' => 20],
            'TK B2' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B2', 'code' => 'TK-B2', 'wali' => 'Evi Dwi Jayanti', 'capacity' => 20],
            'TK B3' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B3', 'code' => 'TK-B3', 'wali' => 'Endriyanti Kumalasari', 'capacity' => 20],
            'TK B4' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B4', 'code' => 'TK-B4', 'wali' => 'Susy Suryani', 'capacity' => 20],

            // Sub-Unit DAYCARE
            'TPA 1' => ['level' => 'TPA-1', 'sub_unit' => 'DAYCARE', 'name' => 'TPA 1', 'code' => 'TPA-1', 'wali' => 'Diah Irawati', 'capacity' => 15],
            'TPA 2' => ['level' => 'TPA-2', 'sub_unit' => 'DAYCARE', 'name' => 'TPA 2', 'code' => 'TPA-2', 'wali' => 'Rahayu Dwi Widyanti', 'capacity' => 15],
            'TPA 3' => ['level' => 'TPA-3', 'sub_unit' => 'DAYCARE', 'name' => 'TPA 3', 'code' => 'TPA-3', 'wali' => 'Risty Dwi Rahmawati', 'capacity' => 15],

            // Sub-Unit TPQ
            'TPQ' => ['level' => 'TPQ', 'sub_unit' => 'TPQ', 'name' => 'TPQ', 'code' => 'TPQ', 'wali' => 'Naimatul Khoiriyah', 'capacity' => 30],
        ];

        $classroomMap = [];
        foreach ($classroomDefinitions as $key => $def) {
            $homeroomEmployee = null;
            if (!empty($def['wali'])) {
                $homeroomEmployee = $employees->first(function ($e) use ($def) {
                    return stripos($e->name, $def['wali']) !== false;
                });
            }

            $classroom = Classroom::updateOrCreate(
                [
                    'code' => $def['code'],
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'name' => $def['name'],
                    'sub_unit' => $def['sub_unit'],
                    'class_level_id' => $levels[$def['level']]->id,
                    'homeroom_teacher_id' => $homeroomEmployee?->id,
                    'capacity' => $def['capacity'],
                    'is_active' => true,
                    'description' => "Kelompok {$def['name']} ({$def['sub_unit']}) TA {$academicYear->name}",
                ]
            );

            // Register various aliases for matching
            $classroomMap[$key] = $classroom;
            $classroomMap[strtoupper($key)] = $classroom;
            $classroomMap[$def['code']] = $classroom;
            $classroomMap[strtoupper($def['code'])] = $classroom;
            $classroomMap[str_replace(' ', '', strtoupper($key))] = $classroom;
            $classroomMap[str_replace('-', '', strtoupper($def['code']))] = $classroom;
            $classroomMap[str_replace('-', ' ', strtoupper($def['code']))] = $classroom;
        }

        $this->info("Kelompok PG-TK-Daycare-TPQ berhasil disiapkan!");

        // 5. Helper Normalisasi
        $formatTitleCase = function (?string $string): ?string {
            if ($string === null) return null;
            $string = trim(preg_replace('/\s+/', ' ', $string));
            if ($string === '') return null;

            $words = explode(' ', $string);
            $result = [];

            $specialTerms = [
                'dr.' => 'Dr.', 'drh.' => 'Drh.', 'dr' => 'Dr.', 'drh' => 'Drh.',
                'ir.' => 'Ir.', 'ir' => 'Ir.', 'prof.' => 'Prof.', 'prof' => 'Prof.',
                'h.' => 'H.', 'hj.' => 'Hj.', 's.t.' => 'S.T.', 's.t' => 'S.T.',
                's.pd.' => 'S.Pd.', 's.pd' => 'S.Pd.', 'm.pd.' => 'M.Pd.', 'm.pd' => 'M.Pd.',
                's.kom.' => 'S.Kom.', 's.kom' => 'S.Kom.', 's.si.' => 'S.Si.', 's.si' => 'S.Si.',
                's.ap.' => 'S.Ap.', 's.ap' => 'S.Ap.', 's.e.' => 'S.E.', 's.e' => 'S.E.',
                'se.' => 'S.E.', 'se' => 'S.E.', 's.sos.' => 'S.Sos.', 's.sos' => 'S.Sos.',
                's.h.' => 'S.H.', 's.h' => 'S.H.', 's.psi.' => 'S.Psi.', 's.psi' => 'S.Psi.',
                's.ked.' => 'S.Ked.', 's.ked' => 'S.Ked.', 's.ag.' => 'S.Ag.', 's.ag' => 'S.Ag.',
                's.hum.' => 'S.Hum.', 's.hum' => 'S.Hum.', 's.sn.' => 'S.Sn.', 's.sn' => 'S.Sn.',
                's.farm.' => 'S.Farm.', 's.farm' => 'S.Farm.', 's.ik.' => 'S.IK.', 's.ik' => 'S.IK.',
                's.kel.' => 'S.Kel.', 's.kel' => 'S.Kel.', 's.mat.' => 'S.Mat.', 's.mat' => 'S.Mat.',
                's.stat.' => 'S.Stat.', 's.stat' => 'S.Stat.', 's.tr.kom' => 'S.Tr.Kom',
                's.pd.sd' => 'S.Pd.SD', 's.pd.i' => 'S.Pd.I',
                'm.si.' => 'M.Si.', 'm.si' => 'M.Si.', 'm.m.' => 'M.M.', 'm.m' => 'M.M.',
                'm.ag.' => 'M.Ag.', 'm.ag' => 'M.Ag.', 'm.hum.' => 'M.Hum.', 'm.hum' => 'M.Hum.',
                'm.kom.' => 'M.Kom.', 'm.kom' => 'M.Kom.', 'm.psi.' => 'M.Psi.', 'm.psi' => 'M.Psi.',
                'sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA', 'smk' => 'SMK',
                'tk' => 'TK', 'ra' => 'RA', 'ba' => 'BA', 'mi' => 'MI', 'mts' => 'MTs', 'ma' => 'MA', 'pg' => 'PG', 'kb' => 'KB', 'tpa' => 'TPA', 'tpq' => 'TPQ',
                'wni' => 'WNI', 'wna' => 'WNA', 'pdbk' => 'PDBK', 'adhd' => 'ADHD',
                'rt' => 'RT', 'rw' => 'RW', 'kk' => 'KK', 'nik' => 'NIK', 'nis' => 'NIS', 'nisn' => 'NISN',
            ];

            foreach ($words as $w) {
                $cleanLower = mb_strtolower($w, 'UTF-8');
                if (isset($specialTerms[$cleanLower])) {
                    $result[] = $specialTerms[$cleanLower];
                } else {
                    $result[] = mb_convert_case($w, MB_CASE_TITLE, 'UTF-8');
                }
            }

            return implode(' ', $result);
        };

        $cleanStr = function ($val) {
            if ($val === null) return null;
            $s = trim((string)$val);
            return ($s === '' || $s === '-' || $s === '#VALUE!' || $s === 'N/A' || $s === 'null') ? null : $s;
        };

        $cleanPhone = function ($raw) {
            if (empty($raw) || trim((string)$raw) === '-' || trim((string)$raw) === '0') return null;
            $p = preg_replace('/[^0-9]/', '', (string)$raw);
            if (empty($p)) return null;
            if (str_starts_with($p, '0')) {
                $p = '62' . substr($p, 1);
            } elseif (str_starts_with($p, '8')) {
                $p = '62' . $p;
            }
            return $p;
        };

        $parseDate = function ($val) {
            if (empty($val)) return null;
            $str = trim((string)$val);
            if ($str === '' || $str === '-' || $str === '#VALUE!') return null;

            if (is_numeric($val) && (int)$val > 1000) {
                try {
                    return ExcelDate::excelToDateTimeObject($val)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }

            // Handle date formats like "24/01/2023" or "24-01-2023"
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
                $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mNum = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $y = $m[3];
                return "{$y}-{$mNum}-{$d}";
            }

            // Handle Indonesian month names like "1 Oktober 2023", "16 AGUSTUS 2023"
            $indoMonths = [
                'JANUARI' => '01', 'FEBRUARI' => '02', 'MARET' => '03', 'APRIL' => '04',
                'MEI' => '05', 'JUNI' => '06', 'JULI' => '07', 'AGUSTUS' => '08',
                'SEPTEMBER' => '09', 'OKTOBER' => '10', 'NOVEMBER' => '11', 'DESEMBER' => '12',
            ];
            $upper = strtoupper($str);
            foreach ($indoMonths as $mName => $mNum) {
                if (str_contains($upper, $mName)) {
                    if (preg_match('/(\d{1,2})\s+' . $mName . '\s+(\d{4})/i', $upper, $matches)) {
                        $d = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $y = $matches[2];
                        return "{$y}-{$mNum}-{$d}";
                    }
                }
            }

            try {
                return Carbon::parse($str)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        };

        // 6. Import Data Murid dari Sheet DB_Murid
        $sheetMurid = $spreadsheet->getSheetByName('DB_Murid') ?? $spreadsheet->getActiveSheet();
        $highestRow = $sheetMurid->getHighestRow();

        $this->info("Mengimpor data murid dari Sheet DB_Murid (Total baris: {$highestRow})...");

        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        $imported = 0;
        $updated = 0;

        for ($r = 2; $r <= $highestRow; $r++) {
            $rawFullName = $cleanStr($sheetMurid->getCell('B' . $r)->getValue());
            if (empty($rawFullName)) {
                $bar->advance();
                continue;
            }

            $fullName = $formatTitleCase($rawFullName);
            $rawNis = $cleanStr($sheetMurid->getCell('A' . $r)->getValue());
            $nis = !empty($rawNis) ? (string)$rawNis : 'PAUD.' . str_pad($r, 4, '0', STR_PAD_LEFT);

            $rawJenjang = strtoupper($cleanStr($sheetMurid->getCell('E' . $r)->getValue()) ?? 'TK-A');
            $rawKelompok = strtoupper($cleanStr($sheetMurid->getCell('F' . $r)->getValue()) ?? 'TK A1');

            // Determine Sub-Unit
            $subUnit = 'TK';
            if (str_contains($rawJenjang, 'KB') || str_contains($rawJenjang, 'PG')) {
                $subUnit = 'PG';
            } elseif (str_contains($rawJenjang, 'TK') || str_contains($rawJenjang, 'RA') || str_contains($rawJenjang, 'BA')) {
                $subUnit = 'TK';
            } elseif (str_contains($rawJenjang, 'TPA') || str_contains($rawJenjang, 'DAYCARE')) {
                $subUnit = 'DAYCARE';
            } elseif (str_contains($rawJenjang, 'TPQ')) {
                $subUnit = 'TPQ';
            }

            // Match ClassLevel
            $cleanLevelKey = str_replace(' ', '-', $rawJenjang);
            $classLevel = $levels[$cleanLevelKey]
                ?? ($levels[$rawJenjang]
                ?? ($levels['TK-A'] ?? ClassLevel::first()));

            // Match Classroom
            $cleanKelompokKey = str_replace('-', ' ', $rawKelompok);
            $classroom = $classroomMap[$rawKelompok]
                ?? ($classroomMap[$cleanKelompokKey]
                ?? ($classroomMap[str_replace(' ', '', $rawKelompok)]
                ?? Classroom::where('class_level_id', $classLevel?->id)->where('academic_year_id', $academicYear->id)->first()));

            $genderRaw = strtoupper($cleanStr($sheetMurid->getCell('D' . $r)->getValue()) ?? '');
            $gender = (str_contains($genderRaw, 'PEREMPUAN') || $genderRaw === 'P' || $genderRaw === 'FEMALE') ? 'P' : 'L';

            $pinAccess = $cleanStr($sheetMurid->getCell('N' . $r)->getValue()) ?? $nis;
            $parentPhone = $cleanPhone($sheetMurid->getCell('K' . $r)->getValue());

            // Handle Daycare and TPQ specific flags
            $daycareClassroomId = ($subUnit === 'DAYCARE') ? $classroom?->id : null;
            $isTpq = ($subUnit === 'TPQ');
            $tpqClassroomId = $isTpq ? $classroom?->id : null;

            $studentData = [
                'nis' => $nis,
                'full_name' => $fullName,
                'nickname' => $formatTitleCase($cleanStr($sheetMurid->getCell('C' . $r)->getValue())),
                'gender' => $gender,
                'birth_place' => $formatTitleCase($cleanStr($sheetMurid->getCell('G' . $r)->getValue())),
                'birth_date' => $parseDate($sheetMurid->getCell('H' . $r)->getValue()),
                'religion' => 'Islam',

                // Akademik & Kelompok
                'academic_year_id' => $academicYear->id,
                'sub_unit' => $subUnit,
                'class_level_id' => $classLevel?->id,
                'classroom_id' => $classroom?->id,
                'daycare_classroom_id' => $daycareClassroomId,
                'is_tpq' => $isTpq,
                'tpq_classroom_id' => $tpqClassroomId,

                // Kontak & Alamat
                'address' => $cleanStr($sheetMurid->getCell('M' . $r)->getValue()),
                'father_name' => $formatTitleCase($cleanStr($sheetMurid->getCell('I' . $r)->getValue())),
                'mother_name' => $formatTitleCase($cleanStr($sheetMurid->getCell('J' . $r)->getValue())),
                'parent_phone' => $parentPhone,
                'pin_access' => $pinAccess,

                'enrolled_date' => '2026-07-15',
                'status' => 'aktif',
            ];

            $existing = Student::where('nis', $nis)->first();
            if ($existing) {
                $existing->update($studentData);
                $student = $existing;
                $updated++;
            } else {
                $student = Student::create($studentData);
                $imported++;
            }

            // Synchronize StudentClassroomHistory
            if ($student && $student->classroom_id) {
                $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
                StudentClassroomHistory::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'classroom_id' => $student->classroom_id,
                        'sub_unit' => $subUnit,
                        'classroom_name' => $student->classroom ? $student->classroom->name : null,
                        'grade_level' => $student->classroom && $student->classroom->classLevel ? $student->classroom->classLevel->name : $classLevel?->name,
                        'homeroom_teacher_name' => $student->classroom && $student->classroom->homeroomTeacher ? $student->classroom->homeroomTeacher->name : null,
                        'status' => 'aktif',
                        'start_date' => $student->enrolled_date ?? '2026-07-15',
                        'notes' => "Import Master Data Murid {$subUnit} Anak Saleh",
                    ]
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=========================================");
        $this->info(" IMPORT DATABASE MURID PG-TK-DAYCARE-TPQ SELESAI");
        $this->info("=========================================");
        $this->table(
            ['Kategori', 'Jumlah'],
            [
                ['Murid Baru Diimpor', $imported],
                ['Murid Diperbarui', $updated],
                ['Total Murid Terproses', $imported + $updated],
                ['Tahun Ajaran', $academicYear->name],
            ]
        );

        return 0;
    }
}
