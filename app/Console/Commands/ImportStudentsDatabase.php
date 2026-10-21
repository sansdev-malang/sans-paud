<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Jenjang;
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
    protected $signature = 'students:import-database {file? : Path to the excel file (defaults to Data_Siswa.xlsx or DB_RAPORT_ANAK_SALEH.xlsx)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import student database (supporting comprehensive 175 columns and legacy formats), class levels, and classrooms';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        if (!$filePath) {
            if (file_exists(public_path('Data_Siswa.xlsx'))) {
                $filePath = public_path('Data_Siswa.xlsx');
            } elseif (file_exists(public_path('DB_RAPORT_ANAK_SALEH.xlsx'))) {
                $filePath = public_path('DB_RAPORT_ANAK_SALEH.xlsx');
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            $this->error("File Excel tidak ditemukan! Pastikan file Data_Siswa.xlsx atau DB_RAPORT_ANAK_SALEH.xlsx ada di folder public/ atau tentukan jalurnya.");
            return 1;
        }

        $this->info("==================================================================");
        $this->info(" MEMBACA FILE EXCEL: {$filePath}");
        $this->info("==================================================================");

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

        $this->info("Tahun Ajaran Aktif: {$academicYear->name} (ID: {$academicYear->id})");

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

        // 3. Load Pegawai untuk Pemetaan Wali Kelas
        $employees = Employee::whereIn('status', ['Active', 'aktif', 'active', 'Aktif'])->get();

        // 4. Setup 16 Kelompok (Rombel) Resmi PG-TK-Daycare-TPQ
        $classroomDefinitions = [
            // PG
            'KB A1' => ['level' => 'KB-A', 'sub_unit' => 'PG', 'name' => 'KB A1', 'code' => 'KB-A1', 'wali' => 'Lilis Rachmawati', 'capacity' => 15],
            'KB A2' => ['level' => 'KB-A', 'sub_unit' => 'PG', 'name' => 'KB A2', 'code' => 'KB-A2', 'wali' => 'Lilis Rachmawati', 'capacity' => 15],
            'KB B1' => ['level' => 'KB-B', 'sub_unit' => 'PG', 'name' => 'KB B1', 'code' => 'KB-B1', 'wali' => 'Linda Romadhona', 'capacity' => 15],
            'KB B2' => ['level' => 'KB-B', 'sub_unit' => 'PG', 'name' => 'KB B2', 'code' => 'KB-B2', 'wali' => 'Ermia Widayanti', 'capacity' => 15],
            'KB B3' => ['level' => 'KB-B', 'sub_unit' => 'PG', 'name' => 'KB B3', 'code' => 'KB-B3', 'wali' => 'Septi Ismawiyanti', 'capacity' => 15],

            // TK
            'TK A1' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A1', 'code' => 'TK-A1', 'wali' => 'Fauzia Faricha', 'capacity' => 20],
            'TK A2' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A2', 'code' => 'TK-A2', 'wali' => 'Martdiyah', 'capacity' => 20],
            'TK A3' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A3', 'code' => 'TK-A3', 'wali' => 'Alfi Azizah', 'capacity' => 20],
            'TK A4' => ['level' => 'TK-A', 'sub_unit' => 'TK', 'name' => 'TK A4', 'code' => 'TK-A4', 'wali' => 'Kholilah', 'capacity' => 20],
            'TK B1' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B1', 'code' => 'TK-B1', 'wali' => 'Novita Rokhmawati', 'capacity' => 20],
            'TK B2' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B2', 'code' => 'TK-B2', 'wali' => 'Evi Dwi Jayanti', 'capacity' => 20],
            'TK B3' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B3', 'code' => 'TK-B3', 'wali' => 'Endriyanti Kumalasari', 'capacity' => 20],
            'TK B4' => ['level' => 'TK-B', 'sub_unit' => 'TK', 'name' => 'TK B4', 'code' => 'TK-B4', 'wali' => 'Susy Suryani', 'capacity' => 20],

            // DAYCARE
            'TPA 1' => ['level' => 'TPA-1', 'sub_unit' => 'DAYCARE', 'name' => 'TPA 1', 'code' => 'TPA-1', 'wali' => 'Diah Irawati', 'capacity' => 15],
            'TPA 2' => ['level' => 'TPA-2', 'sub_unit' => 'DAYCARE', 'name' => 'TPA 2', 'code' => 'TPA-2', 'wali' => 'Rahayu Dwi Widyanti', 'capacity' => 15],
            'TPA 3' => ['level' => 'TPA-3', 'sub_unit' => 'DAYCARE', 'name' => 'TPA 3', 'code' => 'TPA-3', 'wali' => 'Risty Dwi Rahmawati', 'capacity' => 15],

            // TPQ
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

            $classroomMap[$key] = $classroom;
            $classroomMap[strtoupper($key)] = $classroom;
            $classroomMap[$def['code']] = $classroom;
            $classroomMap[strtoupper($def['code'])] = $classroom;
            $classroomMap[str_replace(' ', '', strtoupper($key))] = $classroom;
            $classroomMap[str_replace('-', '', strtoupper($def['code']))] = $classroom;
            $classroomMap[str_replace('-', ' ', strtoupper($def['code']))] = $classroom;
        }

        $this->info("Master Jenjang dan 16 Kelompok berhasil diverifikasi!");

        // 5. Normalizer Helpers
        $cleanStr = function ($val) {
            if ($val === null) return null;
            $s = trim((string)$val);
            return ($s === '' || $s === '-' || $s === '#VALUE!' || $s === 'N/A' || $s === 'null') ? null : $s;
        };

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

            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
                $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $mNum = str_pad($m[2], 2, '0', STR_PAD_LEFT);
                $y = $m[3];
                return "{$y}-{$mNum}-{$d}";
            }

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

        // 6. Tentukan Sheet & Deteksi Mode (Komprehensif 175 Kolom vs Legacy 14 Kolom)
        $sheet = $spreadsheet->getSheetByName('DB_Murid') ?? $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, false, false);
        $totalRawRows = count($rawRows);

        if ($totalRawRows < 2) {
            $this->error("File Excel tidak memiliki data murid!");
            return 1;
        }

        // Cari baris header
        $headerRowIdx = 0;
        foreach (array_slice($rawRows, 0, 3) as $idx => $r) {
            $rowString = strtolower(implode(' ', array_filter(array_map('strval', $r))));
            if (str_contains($rowString, 'nama lengkap') || (str_contains($rowString, 'nama') && str_contains($rowString, 'nis'))) {
                $headerRowIdx = $idx;
                break;
            }
        }

        $headers = $rawRows[$headerRowIdx];
        $dataRows = array_slice($rawRows, $headerRowIdx + 1);
        $headerCount = count(array_filter($headers));

        $this->info("Terdeteksi baris header di index {$headerRowIdx} dengan total {$headerCount} kolom.");

        // Jika terdeteksi file komprehensif (>= 50 kolom atau ada pertanyaan spesifik)
        $isComprehensive = ($headerCount >= 40) || in_array('ayah', array_map('strtolower', array_map('trim', $headers))) || in_array('nama lengkap murid', array_map('strtolower', array_map('trim', $headers)));

        $imported = 0;
        $updated = 0;

        if ($isComprehensive) {
            $this->info("Menjalankan Impor Mode Komprehensif (175 Kolom & JSON Kuesioner)...");

            // Mapping header
            $colMap = [];
            foreach ($headers as $cIdx => $hText) {
                $h = trim(mb_strtolower((string)$hText));
                if ($h === '') continue;

                if (str_contains($h, 'upload scan') || str_starts_with($h, 'upload')) {
                    if (str_contains($h, 'nisn')) { $colMap['doc_nisn'] = $cIdx; }
                    elseif (str_contains($h, 'sekolah lama')) { $colMap['doc_previous_school'] = $cIdx; }
                    elseif (str_contains($h, 'kia')) { $colMap['doc_kia'] = $cIdx; }
                    elseif (str_contains($h, 'rapot')) { $colMap['doc_report'] = $cIdx; }
                    elseif (str_contains($h, 'assesment') || str_contains($h, 'assessment')) { $colMap['doc_assessment'] = $cIdx; }
                    elseif (str_contains($h, 'kms')) { $colMap['doc_kms'] = $cIdx; }
                    continue;
                }

                if (str_contains($h, 'fasilitas game') || str_contains($h, 'game dan sejenisnya')) $colMap['game_facility'] = $cIdx;
                elseif (str_contains($h, 'difasilitasi menonton atau membuka youtube') || (str_contains($h, 'menonton atau membuka youtube') && !str_contains($h, 'bagaimana reaksi'))) $colMap['youtube_access'] = $cIdx;
                elseif (str_contains($h, 'reaksi ananda setelah difasilitasi menonton atau membuka youtube') || (str_contains($h, 'bagaimana reaksi') && str_contains($h, 'youtube'))) $colMap['youtube_reaction'] = $cIdx;
                elseif (str_contains($h, 'selesai menempuh pendidikan di paud') && str_contains($h, 'mengharapkan kemampuan')) $colMap['parent_aspiration_paud'] = $cIdx;
                elseif (str_contains($h, 'pernah paud non-formal') || str_contains($h, 'paud non-formal')) $colMap['is_paud_formal_before'] = $cIdx;
                elseif (str_contains($h, 'dusun')) $colMap['dusun'] = $cIdx;
                elseif (str_contains($h, 'hubungan ananda dengan ayah')) $colMap['rel_father'] = $cIdx;
                elseif (str_contains($h, 'hubungan ananda dengan bunda')) $colMap['rel_mother'] = $cIdx;
                elseif (str_contains($h, 'hubungan ananda dengan') && (str_contains($h, 'saudara') || str_contains($h, 'saudaranya'))) $colMap['rel_siblings'] = $cIdx;
                elseif (str_contains($h, 'hubungan ananda dengan kakek')) $colMap['rel_grandparents'] = $cIdx;
                elseif (str_contains($h, 'hubungan ananda dengan teman sebaya')) $colMap['rel_peers'] = $cIdx;
                elseif (str_contains($h, 'kesempatan bergaul')) $colMap['peer_opportunity'] = $cIdx;
                elseif (str_contains($h, 'kebersamaan ayah') || (str_contains($h, 'komunikasi') && str_contains($h, 'ayah'))) $colMap['comm_father'] = $cIdx;
                elseif (str_contains($h, 'kebersamaan bunda') || (str_contains($h, 'komunikasi') && str_contains($h, 'bunda'))) $colMap['comm_mother'] = $cIdx;
                elseif (str_contains($h, 'sehari - hari paling erat') || str_contains($h, 'paling erat dengan')) $colMap['closest_bond'] = $cIdx;
                elseif (str_contains($h, 'status rumah tinggal') || str_contains($h, 'tempat tinggal keluarga')) $colMap['house_ownership'] = $cIdx;
                elseif (str_contains($h, 'lingkungan rumah tergolong')) $colMap['housing_environment'] = $cIdx;
                elseif (str_contains($h, 'keadaannya') && str_contains($h, 'luar rumah')) $colMap['playyard_state'] = $cIdx;
                elseif (str_contains($h, 'memiliki halaman tempat bermain')) $colMap['has_playyard'] = $cIdx;
                elseif (str_contains($h, 'penghuni dewasa')) $colMap['adult_residents'] = $cIdx;
                elseif (str_contains($h, 'penghuni anak-anak')) $colMap['child_residents'] = $cIdx;
                elseif (str_contains($h, 'mengantar - jemput') || str_contains($h, 'mengantar-jemput')) $colMap['trusted_pickup'] = $cIdx;
                elseif (str_contains($h, 'anda tinggal bersama') || str_contains($h, 'tinggal bersama') || ($h === 'tempat tinggal')) $colMap['living_with'] = $cIdx;
                elseif (str_contains($h, 'iklim keagamaan')) $colMap['religious_climate'] = $cIdx;
                elseif (str_contains($h, 'mengajak ananda beribadah')) $colMap['family_worship'] = $cIdx;
                elseif (str_contains($h, 'ada masjid')) $colMap['nearby_mosque'] = $cIdx;
                elseif (str_contains($h, 'radius 300m') || str_contains($h, 'taman pendidikan al-qur')) $colMap['nearby_tpq'] = $cIdx;
                elseif (str_contains($h, 'nama ayah') || ($h === 'ayah')) $colMap['father_name'] = $cIdx;
                elseif (str_contains($h, 'nik ayah')) $colMap['father_nik'] = $cIdx;
                elseif (str_contains($h, 'tempat lahir ayah')) $colMap['father_birth_place'] = $cIdx;
                elseif (str_contains($h, 'tanggal lahir ayah')) $colMap['father_birth_date'] = $cIdx;
                elseif (str_contains($h, 'pendidikan') && str_contains($h, 'ayah')) $colMap['father_education'] = $cIdx;
                elseif (str_contains($h, 'pekerjaan ayah')) $colMap['father_job'] = $cIdx;
                elseif (str_contains($h, 'instansi ayah')) $colMap['father_company'] = $cIdx;
                elseif (str_contains($h, 'penghasilan ayah')) $colMap['father_income'] = $cIdx;
                elseif (str_contains($h, 'depan ayah') || str_contains($h, 'gelas depan ayah')) $colMap['father_title_prefix'] = $cIdx;
                elseif (str_contains($h, 'belakang ayah')) $colMap['father_title_suffix'] = $cIdx;
                elseif ((str_contains($h, 'telepon ayah') || str_contains($h, 'hp ayah')) && str_contains($h, 'ayah')) $colMap['father_phone'] = $cIdx;
                elseif (str_contains($h, 'nama bunda') || str_contains($h, 'nama ibu')) $colMap['mother_name'] = $cIdx;
                elseif (str_contains($h, 'nik bunda') || str_contains($h, 'nik ibu')) $colMap['mother_nik'] = $cIdx;
                elseif (str_contains($h, 'tempat lahir bunda') || str_contains($h, 'tempat lahir ibu')) $colMap['mother_birth_place'] = $cIdx;
                elseif (str_contains($h, 'tanggal lahir bunda') || str_contains($h, 'tanggal lahir ibu')) $colMap['mother_birth_date'] = $cIdx;
                elseif (str_contains($h, 'pendidikan') && (str_contains($h, 'bunda') || str_contains($h, 'ibu'))) $colMap['mother_education'] = $cIdx;
                elseif (str_contains($h, 'pekerjaan bunda') || str_contains($h, 'pekerjaan ibu')) $colMap['mother_job'] = $cIdx;
                elseif (str_contains($h, 'instansi bunda') || str_contains($h, 'instansi ibu')) $colMap['mother_company'] = $cIdx;
                elseif (str_contains($h, 'penghasilan bunda') || str_contains($h, 'penghasilan ibu')) $colMap['mother_income'] = $cIdx;
                elseif (str_contains($h, 'depan bunda') || str_contains($h, 'depan ibu')) $colMap['mother_title_prefix'] = $cIdx;
                elseif (str_contains($h, 'belakang bunda') || str_contains($h, 'belakang ibu')) $colMap['mother_title_suffix'] = $cIdx;
                elseif (str_contains($h, 'telepon bunda') || str_contains($h, 'telepon ibu') || str_contains($h, 'hp bunda') || str_contains($h, 'hp ibu')) $colMap['mother_phone'] = $cIdx;
                elseif (str_contains($h, 'nama wali')) $colMap['guardian_name'] = $cIdx;
                elseif (str_contains($h, 'nik wali')) $colMap['guardian_nik'] = $cIdx;
                elseif (str_contains($h, 'tempat lahir wali')) $colMap['guardian_birth_place'] = $cIdx;
                elseif (str_contains($h, 'tanggal lahir wali')) $colMap['guardian_birth_date'] = $cIdx;
                elseif (str_contains($h, 'pendidikan') && str_contains($h, 'wali')) $colMap['guardian_education'] = $cIdx;
                elseif (str_contains($h, 'pekerjaan wali')) $colMap['guardian_job'] = $cIdx;
                elseif (str_contains($h, 'instansi wali')) $colMap['guardian_company'] = $cIdx;
                elseif (str_contains($h, 'penghasilan wali')) $colMap['guardian_income'] = $cIdx;
                elseif (str_contains($h, 'depan') && str_contains($h, 'wali')) $colMap['guardian_title_prefix'] = $cIdx;
                elseif (str_contains($h, 'belakang') && str_contains($h, 'wali')) $colMap['guardian_title_suffix'] = $cIdx;
                elseif (str_contains($h, 'hubungan keluarga')) $colMap['guardian_relation'] = $cIdx;
                elseif (str_contains($h, 'didampingi wali')) $colMap['is_accompanied_by_guardian'] = $cIdx;
                elseif (str_contains($h, 'telepon wali') || str_contains($h, 'hp wali')) $colMap['guardian_phone'] = $cIdx;
                elseif (str_contains($h, 'nomor kontak darurat') || str_contains($h, 'no hp darurat') || str_contains($h, 'telepon darurat') || str_contains($h, 'hp darurat')) $colMap['emergency_contact_phone'] = $cIdx;
                elseif (str_contains($h, 'hubungan kontak darurat') || str_contains($h, 'hubungan darurat')) $colMap['emergency_contact_relation'] = $cIdx;
                elseif (str_contains($h, 'darurat')) $colMap['emergency_contact'] = $cIdx;
                elseif (str_contains($h, 'email') || str_contains($h, 'surel')) $colMap['parent_email'] = $cIdx;
                elseif (str_contains($h, 'whatsapp') || str_contains($h, 'kontak utama') || $h === 'nomor telepon' || $h === 'telepon' || $h === 'no hp' || $h === 'no wa') $colMap['parent_phone'] = $cIdx;
                elseif (str_contains($h, 'asal sekolah')) $colMap['previous_school'] = $cIdx;
                elseif (str_contains($h, 'sekolah yang direncanakan')) $colMap['planned_school_after'] = $cIdx;
                elseif (str_contains($h, 'hobby siswa') || str_contains($h, 'hobi')) $colMap['student_hobby'] = $cIdx;
                elseif (str_contains($h, 'cita-cita siswa')) $colMap['student_aspiration'] = $cIdx;
                elseif (str_contains($h, 'cita-cita orangtua')) $colMap['parent_aspiration'] = $cIdx;
                elseif (str_contains($h, 'usia kandungan')) $colMap['gestational_age'] = $cIdx;
                elseif (str_contains($h, 'waktu dalam kandungan')) $colMap['prenatal_condition'] = $cIdx;
                elseif (str_contains($h, 'cara persalinan')) $colMap['delivery_method'] = $cIdx;
                elseif (str_contains($h, 'tindakan persalinan')) $colMap['delivery_reason'] = $cIdx;
                elseif (str_contains($h, 'panjang badan ananda pada saat lahir') || str_contains($h, 'pada saat lahir (kg/cm)')) $colMap['birth_weight_length'] = $cIdx;
                elseif (str_contains($h, 'disusui oleh')) $colMap['breastfed_by'] = $cIdx;
                elseif (str_contains($h, 'mendapatkan asi')) $colMap['breastfed_duration'] = $cIdx;
                elseif (str_contains($h, 'susu formula')) $colMap['formula_start'] = $cIdx;
                elseif (str_contains($h, 'makanan tambahan')) $colMap['mpasi_start'] = $cIdx;
                elseif (str_contains($h, 'tumbuh gigi')) $colMap['teeth_age'] = $cIdx;
                elseif (str_contains($h, 'dapat berjalan')) $colMap['walking_age'] = $cIdx;
                elseif (str_contains($h, 'dapat berbicara')) $colMap['talking_age'] = $cIdx;
                elseif (str_contains($h, 'gangguan sebutkan')) $colMap['developmental_issues'] = $cIdx;
                elseif (str_contains($h, 'imunisasi yang pernah')) $colMap['immunizations'] = $cIdx;
                elseif (str_contains($h, 'penyakit berat')) $colMap['severe_diseases'] = $cIdx;
                elseif (str_contains($h, 'penyakit yang sering')) $colMap['frequent_diseases'] = $cIdx;
                elseif (str_contains($h, 'alergi terhadap makanan')) $colMap['food_allergy'] = $cIdx;
                elseif (str_contains($h, 'reaksi ananda terhadap alergi')) $colMap['allergy_reaction'] = $cIdx;
                elseif (str_contains($h, 'penyembuhan alergi')) $colMap['allergy_treatment'] = $cIdx;
                elseif (str_contains($h, 'alergi terhadap minuman')) $colMap['drink_allergy'] = $cIdx;
                elseif (str_contains($h, 'diperiksakan ke')) $colMap['preferred_doctor'] = $cIdx;
                elseif (str_contains($h, 'alternatif penyembuhan')) $colMap['treatment_alternative'] = $cIdx;
                elseif (str_contains($h, 'pesan orang tua atas kesehatan')) $colMap['school_health_notes'] = $cIdx;
                elseif (str_contains($h, 'memakai diapers')) $colMap['uses_diapers'] = $cIdx;
                elseif (str_contains($h, 'buang air pada umumnya')) $colMap['toilet_habit'] = $cIdx;
                elseif (str_contains($h, 'kencing/ngompol') || str_contains($h, 'ngompol')) $colMap['bedwetting'] = $cIdx;
                elseif (str_contains($h, 'kebiasaan ananda dalam menggosok gigi')) $colMap['toothbrush_habit'] = $cIdx;
                elseif (str_contains($h, 'berapa kali ananda gosok gigi')) $colMap['toothbrush_frequency'] = $cIdx;
                elseif (str_contains($h, 'menggosok gigi apakah ananda dibantu')) $colMap['toothbrush_assisted'] = $cIdx;
                elseif (str_contains($h, 'selera makan pagi')) $colMap['breakfast_appetite'] = $cIdx;
                elseif (str_contains($h, 'selera makan siang')) $colMap['lunch_appetite'] = $cIdx;
                elseif (str_contains($h, 'selera makan malam')) $colMap['dinner_appetite'] = $cIdx;
                elseif (str_contains($h, 'makan diantara waktu')) $colMap['snack_habit'] = $cIdx;
                elseif (str_contains($h, 'waktu makan, dilakukan sambil')) $colMap['eating_manner'] = $cIdx;
                elseif (str_contains($h, 'berdoa pada saat sebelum dan sesudah makan')) $colMap['prays_before_after_meal'] = $cIdx;
                elseif (str_contains($h, 'makanan yang disukai')) $colMap['favorite_food'] = $cIdx;
                elseif (str_contains($h, 'makanan yang tidak disukai')) $colMap['disliked_food'] = $cIdx;
                elseif (str_contains($h, 'minuman yang disukai')) $colMap['favorite_drink'] = $cIdx;
                elseif (str_contains($h, 'minuman yang tidak disukai')) $colMap['disliked_drink'] = $cIdx;
                elseif (str_contains($h, 'bagaimana ananda tidur')) $colMap['sleeping_with'] = $cIdx;
                elseif (str_contains($h, 'tidur malam pukul')) $colMap['bedtime'] = $cIdx;
                elseif (str_contains($h, 'tidur siang pukul')) $colMap['nap_time'] = $cIdx;
                elseif (str_contains($h, 'bangun tidur siang')) $colMap['wake_nap_time'] = $cIdx;
                elseif (str_contains($h, 'bangun pagi pukul')) $colMap['wake_morning_time'] = $cIdx;
                elseif (str_contains($h, 'kamar tidur sendiri')) $colMap['has_own_bedroom'] = $cIdx;
                elseif (str_contains($h, 'mengigau dalam tidur')) $colMap['sleep_talking'] = $cIdx;
                elseif (str_contains($h, 'diajari doa')) $colMap['sleep_prayers'] = $cIdx;
                elseif (str_contains($h, 'hal - hal pada waktu tidur') || str_contains($h, 'hal-hal pada waktu tidur')) $colMap['sleep_notes'] = $cIdx;
                elseif (str_contains($h, 'karakter atau sifat')) $colMap['character_traits'] = $cIdx;
                elseif (str_contains($h, 'kepatuhan ananda')) $colMap['obedience'] = $cIdx;
                elseif (str_contains($h, 'tingkah laku ananda')) $colMap['behavior_notes'] = $cIdx;
                elseif (str_contains($h, 'kebiasaan bermain')) $colMap['play_habits'] = $cIdx;
                elseif (str_contains($h, 'tempat bermain')) $colMap['play_location'] = $cIdx;
                elseif (str_contains($h, 'bermain bersama siapa')) $colMap['playmates'] = $cIdx;
                elseif (str_contains($h, 'mainan yang disenangi')) $colMap['favorite_toys'] = $cIdx;
                elseif (str_contains($h, 'mainan yang tidak disenangi')) $colMap['disliked_toys'] = $cIdx;
                elseif (str_contains($h, 'dinilai orang tua berbahaya')) $colMap['dangerous_toys'] = $cIdx;
                elseif (str_contains($h, 'melihat televisi') && str_contains($h, 'sering')) $colMap['tv_frequency'] = $cIdx;
                elseif (str_contains($h, 'mendampingi ananda saat menonton')) $colMap['tv_companion'] = $cIdx;
                elseif (str_contains($h, 'didampingi ketika melihat televisi')) $colMap['tv_accompanied'] = $cIdx;
                elseif (str_contains($h, 'kosentrasi ananda ketika melihat')) $colMap['tv_concentration'] = $cIdx;
                elseif (str_contains($h, 'acara tv yang disukai')) $colMap['favorite_tv'] = $cIdx;
                elseif (str_contains($h, 'acara tv yang tidak disukai')) $colMap['disliked_tv'] = $cIdx;
                elseif (str_contains($h, 'menindaklanjuti')) $colMap['assessment_agreement'] = $cIdx;
                elseif (str_contains($h, 'tindak lanjut yang ayah-bunda setujui')) $colMap['agreed_followup_types'] = $cIdx;
                elseif (str_contains($h, 'saran-saran orang tua')) $colMap['parent_suggestions'] = $cIdx;
                elseif ($h === 'nisn' || (str_contains($h, 'nisn') && !str_contains($h, 'upload') && !str_contains($h, 'game') && !str_contains($h, 'sejenisnya'))) $colMap['nisn'] = $cIdx;
                elseif (str_contains($h, 'kelamin') || str_contains($h, 'l/p') || str_contains($h, 'gender') || $h === 'jk') $colMap['gender'] = $cIdx;
                elseif (($h === 'nis' || str_starts_with($h, 'nis ') || str_contains($h, 'nomor induk siswa') || str_contains($h, 'no. induk')) && !str_contains($h, 'nisn') && !str_contains($h, 'imunisasi')) {
                    if (!isset($colMap['nis'])) $colMap['nis'] = $cIdx;
                }
                elseif (str_contains($h, 'nik ananda') || str_contains($h, 'nik siswa') || ($h === 'nik')) $colMap['nik'] = $cIdx;
                elseif (str_contains($h, 'kartu keluarga') || str_contains($h, 'no. kk') || str_contains($h, 'nomor kk') || $h === 'kk') $colMap['no_kk'] = $cIdx;
                elseif (str_contains($h, 'akta')) $colMap['no_akta'] = $cIdx;
                elseif (str_contains($h, 'nama lengkap') || (str_contains($h, 'nama') && (str_contains($h, 'ananda') || str_contains($h, 'siswa')))) {
                    if (!isset($colMap['full_name']) || str_contains($h, 'nama lengkap')) $colMap['full_name'] = $cIdx;
                }
                elseif (str_contains($h, 'panggilan')) $colMap['nickname'] = $cIdx;
                elseif (str_contains($h, 'tempat lahir')) $colMap['birth_place'] = $cIdx;
                elseif (str_contains($h, 'tanggal lahir') || str_contains($h, 'tgl lahir')) $colMap['birth_date'] = $cIdx;
                elseif ($h === 'agama') $colMap['religion'] = $cIdx;
                elseif (str_contains($h, 'kewarganegaraan') || str_contains($h, 'wni')) $colMap['citizenship'] = $cIdx;
                elseif (str_contains($h, 'alamat')) $colMap['address'] = $cIdx;
                elseif ($h === 'rt') $colMap['rt'] = $cIdx;
                elseif ($h === 'rw') $colMap['rw'] = $cIdx;
                elseif (str_contains($h, 'kelurahan') || str_contains($h, 'desa')) $colMap['kelurahan'] = $cIdx;
                elseif (str_contains($h, 'kecamatan')) $colMap['kecamatan'] = $cIdx;
                elseif (str_contains($h, 'kota') || str_contains($h, 'kabupaten')) $colMap['city'] = $cIdx;
                elseif (str_contains($h, 'kode pos') || str_contains($h, 'kodepos')) $colMap['postal_code'] = $cIdx;
                elseif (str_contains($h, 'transportasi')) $colMap['transportation'] = $cIdx;
                elseif (str_contains($h, 'anak ke')) $colMap['child_order'] = $cIdx;
                elseif (str_contains($h, 'jumlah saudara kandung') || str_contains($h, 'saudara kandung')) $colMap['siblings_count'] = $cIdx;
                elseif (str_contains($h, 'golongan darah') || $h === 'darah') $colMap['blood_type'] = $cIdx;
                elseif (str_contains($h, 'detail kebutuhan khusus') || str_contains($h, 'rincian kebutuhan khusus') || str_contains($h, 'kebutuhan khusus detail')) $colMap['special_needs_detail'] = $cIdx;
                elseif (str_contains($h, 'kebutuhan khusus') || str_contains($h, 'disabilitas')) {
                    if (!isset($colMap['special_needs'])) $colMap['special_needs'] = $cIdx;
                }
                elseif (str_contains($h, 'tinggi badan') || str_contains($h, 'tinggi bandan') || $h === 'tb') $colMap['height'] = $cIdx;
                elseif (str_contains($h, 'berat badan') || $h === 'bb') $colMap['weight'] = $cIdx;
                elseif (str_contains($h, 'lingkar kepala') || $h === 'lk') $colMap['head_circumference'] = $cIdx;
                elseif (str_contains($h, 'lingkar lengan') || str_contains($h, 'lila')) $colMap['arm_circumference'] = $cIdx;
                elseif (str_contains($h, 'jarak')) $colMap['distance_to_school'] = $cIdx;
                elseif (str_contains($h, 'waktu tempuh')) $colMap['travel_time_minutes'] = $cIdx;
                elseif (str_contains($h, 'sub unit')) $colMap['sub_unit'] = $cIdx;
                elseif (str_contains($h, 'jenjang')) $colMap['jenjang'] = $cIdx;
                elseif (str_contains($h, 'daycare')) $colMap['daycare'] = $cIdx;
                elseif (str_contains($h, 'ikut tpq')) $colMap['is_tpq'] = $cIdx;
                elseif (str_contains($h, 'kelompok tpq')) $colMap['tpq_rombel'] = $cIdx;
                elseif (str_contains($h, 'kelompok') || str_contains($h, 'rombel') || $h === 'kelas') {
                    if (!isset($colMap['classroom'])) $colMap['classroom'] = $cIdx;
                }
                elseif (str_contains($h, 'pin')) $colMap['pin_access'] = $cIdx;
                elseif (str_contains($h, 'status')) $colMap['status'] = $cIdx;
                elseif (str_contains($h, 'catatan')) $colMap['notes'] = $cIdx;
            }

            $bar = $this->output->createProgressBar(count($dataRows));
            $bar->start();

            foreach ($dataRows as $rIdx => $row) {
                $getVal = function ($key) use ($row, $colMap) {
                    if (isset($colMap[$key]) && isset($row[$colMap[$key]])) {
                        $v = trim((string)$row[$colMap[$key]]);
                        return $v !== '' ? $v : null;
                    }
                    return null;
                };

                $fullName = $formatTitleCase($getVal('full_name'));
                if (empty($fullName)) {
                    $bar->advance();
                    continue;
                }

                $rawNis = $getVal('nis');
                $nis = !empty($rawNis) ? (string)$rawNis : 'PAUD.' . str_pad($rIdx + 1, 4, '0', STR_PAD_LEFT);
                $nisn = $getVal('nisn');
                $nik = $getVal('nik');
                $noKk = $getVal('no_kk');
                $noAkta = $getVal('no_akta');
                $nickname = $formatTitleCase($getVal('nickname'));

                $genderRaw = strtoupper($getVal('gender') ?? '');
                $gender = (str_contains($genderRaw, 'PEREMPUAN') || $genderRaw === 'P' || $genderRaw === 'FEMALE') ? 'P' : 'L';

                $birthPlace = $formatTitleCase($getVal('birth_place'));
                $birthDate = $parseDate($getVal('birth_date'));
                $religion = $getVal('religion') ?? 'Islam';
                $citizenship = $getVal('citizenship') ?? 'WNI';

                $address = $getVal('address');
                $dusun = $formatTitleCase($getVal('dusun'));
                $rt = $getVal('rt');
                $rw = $getVal('rw');
                $kelurahan = $formatTitleCase($getVal('kelurahan'));
                $kecamatan = $formatTitleCase($getVal('kecamatan'));
                $city = $formatTitleCase($getVal('city')) ?? 'Malang';
                $postalCode = $getVal('postal_code');
                $livingWith = $getVal('living_with') ?? 'Bersama Orang Tua';
                $transportation = $getVal('transportation');

                $childOrder = is_numeric($getVal('child_order')) ? (int)$getVal('child_order') : null;
                $siblingsCount = is_numeric($getVal('siblings_count')) ? (int)$getVal('siblings_count') : null;
                $bloodType = $getVal('blood_type');
                $specialNeeds = $getVal('special_needs') ?? 'Tidak';
                $specialNeedsDetail = $getVal('special_needs_detail');

                $extractNum = function ($val, $max = 250) {
                    if ($val === null || $val === '') return null;
                    $valStr = trim((string)$val);
                    $num = null;
                    if (preg_match('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2})\b/i', $valStr, $m)) {
                        $num = (float)$m[1];
                    } elseif (preg_match('/^\d{4}-\d{2}-(\d{1,2})/', $valStr, $m)) {
                        $num = (float)$m[1];
                    } elseif (preg_match('/(\d+(?:[\.,]\d+)?)/', $valStr, $m)) {
                        $num = (float)str_replace(',', '.', $m[1]);
                    } elseif (is_numeric($val)) {
                        $num = (float)$val;
                    }
                    return ($num !== null && $num > 0 && $num <= $max) ? $num : null;
                };

                $height = $extractNum($getVal('height'), 250);
                $weight = $extractNum($getVal('weight'), 150);
                $headCirc = $extractNum($getVal('head_circumference'), 100);
                $armCirc = $extractNum($getVal('arm_circumference'), 60);
                $distanceToSchool = $getVal('distance_to_school');
                $travelTime = is_numeric($getVal('travel_time_minutes')) ? (int)$getVal('travel_time_minutes') : null;

                $previousSchool = $getVal('previous_school');
                $isPaudFormalBefore = in_array(strtolower($getVal('is_paud_formal_before') ?? ''), ['ya', 'true', '1', 'pernah']);
                $plannedSchoolAfter = $getVal('planned_school_after');
                $studentAspiration = $getVal('student_aspiration');
                $studentHobby = $getVal('student_hobby');
                $parentAspiration = $getVal('parent_aspiration');
                $parentAspirationPaud = $getVal('parent_aspiration_paud');
                if (!empty($parentAspirationPaud)) {
                    $parentAspiration = $parentAspiration ? ($parentAspiration . ' | Harapan selesai PAUD: ' . $parentAspirationPaud) : $parentAspirationPaud;
                }

                // Sub Unit & Kelompok Resolving
                $rawJenjang = strtoupper($getVal('jenjang') ?? 'TK-A');
                $rawKelompok = strtoupper($getVal('classroom') ?? 'TK A1');

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

                $cleanLevelKey = str_replace(' ', '-', $rawJenjang);
                $classLevel = $levels[$cleanLevelKey]
                    ?? ($levels[$rawJenjang]
                    ?? ($levels['TK-A'] ?? ClassLevel::first()));

                $cleanKelompokKey = str_replace('-', ' ', $rawKelompok);
                $classroom = $classroomMap[$rawKelompok]
                    ?? ($classroomMap[$cleanKelompokKey]
                    ?? ($classroomMap[str_replace(' ', '', $rawKelompok)]
                    ?? Classroom::where('class_level_id', $classLevel?->id)->where('academic_year_id', $academicYear->id)->first()));

                $daycareClassroomId = ($subUnit === 'DAYCARE') ? $classroom?->id : null;
                $isTpq = ($subUnit === 'TPQ');
                $tpqClassroomId = $isTpq ? $classroom?->id : null;

                // Father fields
                $fatherName = $formatTitleCase($getVal('father_name'));
                $fatherNik = $getVal('father_nik');
                $fatherBirthPlace = $formatTitleCase($getVal('father_birth_place'));
                $fatherBirthDate = $parseDate($getVal('father_birth_date'));
                $fatherEdu = $getVal('father_education');
                $fatherJob = $formatTitleCase($getVal('father_job'));
                $fatherCompany = $formatTitleCase($getVal('father_company'));
                $fatherIncome = $getVal('father_income');
                $fatherTitlePrefix = $getVal('father_title_prefix');
                $fatherTitleSuffix = $getVal('father_title_suffix');
                $fatherPhone = $cleanPhone($getVal('father_phone'));

                // Mother fields
                $motherName = $formatTitleCase($getVal('mother_name'));
                $motherNik = $getVal('mother_nik');
                $motherBirthPlace = $formatTitleCase($getVal('mother_birth_place'));
                $motherBirthDate = $parseDate($getVal('mother_birth_date'));
                $motherEdu = $getVal('mother_education');
                $motherJob = $formatTitleCase($getVal('mother_job'));
                $motherCompany = $formatTitleCase($getVal('mother_company'));
                $motherIncome = $getVal('mother_income');
                $motherTitlePrefix = $getVal('mother_title_prefix');
                $motherTitleSuffix = $getVal('mother_title_suffix');
                $motherPhone = $cleanPhone($getVal('mother_phone'));

                // Guardian fields
                $guardianName = $formatTitleCase($getVal('guardian_name'));
                $guardianNik = $getVal('guardian_nik');
                $guardianBirthPlace = $formatTitleCase($getVal('guardian_birth_place'));
                $guardianBirthDate = $parseDate($getVal('guardian_birth_date'));
                $guardianEdu = $getVal('guardian_education');
                $guardianJob = $formatTitleCase($getVal('guardian_job'));
                $guardianCompany = $formatTitleCase($getVal('guardian_company'));
                $guardianIncome = $getVal('guardian_income');
                $guardianTitlePrefix = $getVal('guardian_title_prefix');
                $guardianTitleSuffix = $getVal('guardian_title_suffix');
                $guardianRelation = $getVal('guardian_relation');
                $isAccompaniedGuardian = in_array(strtolower($getVal('is_accompanied_by_guardian') ?? ''), ['ya', 'true', '1']);
                $guardianPhone = $cleanPhone($getVal('guardian_phone'));

                $parentPhone = $cleanPhone($getVal('parent_phone')) ?? ($fatherPhone ?: $motherPhone ?: $guardianPhone);
                $parentEmail = $getVal('parent_email');
                $emergencyContact = $formatTitleCase($getVal('emergency_contact'));
                $emergencyContactPhone = $cleanPhone($getVal('emergency_contact_phone'));
                $emergencyContactRelation = $getVal('emergency_contact_relation');
                $pinAccess = $getVal('pin_access') ?? $nis;

                // JSON Structures
                $familyData = [
                    'father' => [
                        'name' => $fatherName,
                        'nik' => $fatherNik,
                        'birth_place' => $fatherBirthPlace,
                        'birth_date' => $fatherBirthDate,
                        'education' => $fatherEdu,
                        'job' => $fatherJob,
                        'company' => $fatherCompany,
                        'income' => $fatherIncome,
                        'title_prefix' => $fatherTitlePrefix,
                        'title' => $fatherTitleSuffix,
                        'phone' => $fatherPhone,
                    ],
                    'mother' => [
                        'name' => $motherName,
                        'nik' => $motherNik,
                        'birth_place' => $motherBirthPlace,
                        'birth_date' => $motherBirthDate,
                        'education' => $motherEdu,
                        'job' => $motherJob,
                        'company' => $motherCompany,
                        'income' => $motherIncome,
                        'title_prefix' => $motherTitlePrefix,
                        'title' => $motherTitleSuffix,
                        'phone' => $motherPhone,
                    ],
                    'guardian' => [
                        'is_active' => !empty($guardianName),
                        'name' => $guardianName,
                        'nik' => $guardianNik,
                        'birth_place' => $guardianBirthPlace,
                        'birth_date' => $guardianBirthDate,
                        'education' => $guardianEdu,
                        'job' => $guardianJob,
                        'company' => $guardianCompany,
                        'income' => $guardianIncome,
                        'title_prefix' => $guardianTitlePrefix,
                        'title' => $guardianTitleSuffix,
                        'relation' => $guardianRelation,
                        'is_accompanied' => $isAccompaniedGuardian,
                        'phone' => $guardianPhone,
                    ],
                    'environment' => [
                        'house_ownership' => $getVal('house_ownership'),
                        'housing_environment' => $getVal('housing_environment'),
                        'has_playyard' => $getVal('has_playyard'),
                        'playyard_state' => $getVal('playyard_state'),
                        'adult_residents' => $getVal('adult_residents'),
                        'child_residents' => $getVal('child_residents'),
                    ],
                    'relationships' => [
                        'closest_bond' => $getVal('closest_bond'),
                        'rel_father' => $getVal('rel_father'),
                        'rel_mother' => $getVal('rel_mother'),
                        'rel_siblings' => $getVal('rel_siblings'),
                        'rel_grandparents' => $getVal('rel_grandparents'),
                        'rel_peers' => $getVal('rel_peers'),
                        'peer_opportunity' => $getVal('peer_opportunity'),
                        'comm_father' => $getVal('comm_father'),
                        'comm_mother' => $getVal('comm_mother'),
                        'trusted_pickup' => $getVal('trusted_pickup'),
                    ],
                    'religious_climate' => [
                        'climate' => $getVal('religious_climate'),
                        'family_worship' => $getVal('family_worship'),
                        'nearby_mosque' => $getVal('nearby_mosque'),
                        'nearby_tpq' => $getVal('nearby_tpq'),
                    ]
                ];

                $healthHabits = [
                    'surroundings' => $getVal('housing_environment'),
                    'near_mosque' => $getVal('nearby_mosque'),
                    'external_tpq' => $getVal('nearby_tpq'),
                    'birth_history' => [
                        'gestational_age' => $getVal('gestational_age'),
                        'prenatal_condition' => $getVal('prenatal_condition'),
                        'delivery_method' => $getVal('delivery_method'),
                        'delivery_reason' => $getVal('delivery_reason'),
                        'birth_weight_length' => $getVal('birth_weight_length'),
                    ],
                    'milestones' => [
                        'breastfed_by' => $getVal('breastfed_by'),
                        'breastfed_duration' => $getVal('breastfed_duration'),
                        'formula_start' => $getVal('formula_start'),
                        'mpasi_start' => $getVal('mpasi_start'),
                        'teeth_age' => $getVal('teeth_age'),
                        'walking_age' => $getVal('walking_age'),
                        'talking_age' => $getVal('talking_age'),
                        'developmental_issues' => $getVal('developmental_issues'),
                    ],
                    'medical_history' => [
                        'immunizations' => $getVal('immunizations'),
                        'severe_diseases' => $getVal('severe_diseases'),
                        'frequent_diseases' => $getVal('frequent_diseases'),
                        'food_allergy' => $getVal('food_allergy'),
                        'allergy_reaction' => $getVal('allergy_reaction'),
                        'allergy_treatment' => $getVal('allergy_treatment'),
                        'drink_allergy' => $getVal('drink_allergy'),
                        'preferred_doctor' => $getVal('preferred_doctor'),
                        'treatment_alternative' => $getVal('treatment_alternative'),
                        'school_health_notes' => $getVal('school_health_notes'),
                    ],
                    'toilet_training' => [
                        'uses_diapers' => $getVal('uses_diapers'),
                        'toilet_habit' => $getVal('toilet_habit'),
                        'bedwetting' => $getVal('bedwetting'),
                        'toothbrush_habit' => $getVal('toothbrush_habit'),
                        'toothbrush_frequency' => $getVal('toothbrush_frequency'),
                        'toothbrush_assisted' => $getVal('toothbrush_assisted'),
                    ],
                    'meals' => [
                        'breakfast' => $getVal('breakfast_appetite'),
                        'lunch' => $getVal('lunch_appetite'),
                        'dinner' => $getVal('dinner_appetite'),
                        'snack_habit' => $getVal('snack_habit'),
                        'eating_manner' => $getVal('eating_manner'),
                        'prays_before_after' => $getVal('prays_before_after_meal'),
                        'favorite' => $getVal('favorite_food'),
                        'avoided' => $getVal('disliked_food'),
                        'favorite_drink' => $getVal('favorite_drink'),
                        'avoided_drink' => $getVal('disliked_drink'),
                    ],
                    'sleeping' => [
                        'sleeping_with' => $getVal('sleeping_with'),
                        'bedtime' => $getVal('bedtime'),
                        'nap_time' => $getVal('nap_time'),
                        'wake_nap_time' => $getVal('wake_nap_time'),
                        'wake_morning_time' => $getVal('wake_morning_time'),
                        'has_own_bedroom' => $getVal('has_own_bedroom'),
                        'sleep_talking' => $getVal('sleep_talking'),
                        'sleep_prayers' => $getVal('sleep_prayers'),
                        'sleep_notes' => $getVal('sleep_notes'),
                    ]
                ];

                $devPsychology = [
                    'traits' => [
                        'character_traits' => $getVal('character_traits'),
                        'obedience' => $getVal('obedience'),
                        'behavior_notes' => $getVal('behavior_notes'),
                    ],
                    'play' => [
                        'play_habits' => $getVal('play_habits'),
                        'play_location' => $getVal('play_location'),
                        'playmates' => $getVal('playmates'),
                        'favorite_toys' => $getVal('favorite_toys'),
                        'disliked_toys' => $getVal('disliked_toys'),
                        'dangerous_toys' => $getVal('dangerous_toys'),
                    ],
                    'screen_time' => [
                        'game_facility' => $getVal('game_facility'),
                        'youtube_access' => $getVal('youtube_access'),
                        'tv_frequency' => $getVal('tv_frequency'),
                        'tv_companion' => $getVal('tv_companion'),
                        'tv_accompanied' => $getVal('tv_accompanied'),
                        'tv_concentration' => $getVal('tv_concentration'),
                        'favorite_tv' => $getVal('favorite_tv'),
                        'disliked_tv' => $getVal('disliked_tv'),
                        'youtube_reaction' => $getVal('youtube_reaction'),
                    ],
                    'commitments' => [
                        'assessment_agreement' => $getVal('assessment_agreement'),
                        'agreed_followup_types' => $getVal('agreed_followup_types'),
                        'paud_outcome_expectation' => $getVal('parent_aspiration_paud'),
                        'parent_suggestions' => $getVal('parent_suggestions'),
                    ]
                ];

                $studentData = [
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'nik' => $nik,
                    'no_kk' => $noKk,
                    'no_akta' => $noAkta,
                    'full_name' => $fullName,
                    'nickname' => $nickname,
                    'gender' => $gender,
                    'birth_place' => $birthPlace,
                    'birth_date' => $birthDate,
                    'religion' => $religion,
                    'citizenship' => $citizenship,
                    'address' => $address,
                    'dusun' => $dusun,
                    'rt' => $rt,
                    'rw' => $rw,
                    'kelurahan' => $kelurahan,
                    'kecamatan' => $kecamatan,
                    'city' => $city,
                    'postal_code' => $postalCode,
                    'living_with' => $livingWith,
                    'transportation' => $transportation,
                    'child_order' => $childOrder,
                    'siblings_count' => $siblingsCount,
                    'blood_type' => $bloodType,
                    'special_needs' => $specialNeeds,
                    'special_needs_detail' => $specialNeedsDetail,
                    'height' => $height,
                    'weight' => $weight,
                    'head_circumference' => $headCirc,
                    'arm_circumference' => $armCirc,
                    'distance_to_school' => $distanceToSchool,
                    'travel_time_minutes' => $travelTime,
                    'previous_school' => $previousSchool,
                    'is_paud_formal_before' => $isPaudFormalBefore,
                    'planned_school_after' => $plannedSchoolAfter,
                    'student_aspiration' => $studentAspiration,
                    'student_hobby' => $studentHobby,
                    'parent_aspiration' => $parentAspiration,

                    'academic_year_id' => $academicYear->id,
                    'sub_unit' => $subUnit,
                    'class_level_id' => $classLevel?->id,
                    'classroom_id' => $classroom?->id,
                    'daycare_classroom_id' => $daycareClassroomId,
                    'is_tpq' => $isTpq,
                    'tpq_classroom_id' => $tpqClassroomId,

                    'father_name' => $fatherName,
                    'father_nik' => $fatherNik,
                    'father_birth_place' => $fatherBirthPlace,
                    'father_birth_date' => $fatherBirthDate,
                    'father_education' => $fatherEdu,
                    'father_job' => $fatherJob,
                    'father_company' => $fatherCompany,
                    'father_income' => $fatherIncome,
                    'father_title_prefix' => $fatherTitlePrefix,
                    'father_title_suffix' => $fatherTitleSuffix,
                    'father_phone' => $fatherPhone,

                    'mother_name' => $motherName,
                    'mother_nik' => $motherNik,
                    'mother_birth_place' => $motherBirthPlace,
                    'mother_birth_date' => $motherBirthDate,
                    'mother_education' => $motherEdu,
                    'mother_job' => $motherJob,
                    'mother_company' => $motherCompany,
                    'mother_income' => $motherIncome,
                    'mother_title_prefix' => $motherTitlePrefix,
                    'mother_title_suffix' => $motherTitleSuffix,
                    'mother_phone' => $motherPhone,

                    'guardian_name' => $guardianName,
                    'guardian_nik' => $guardianNik,
                    'guardian_birth_place' => $guardianBirthPlace,
                    'guardian_birth_date' => $guardianBirthDate,
                    'guardian_education' => $guardianEdu,
                    'guardian_job' => $guardianJob,
                    'guardian_company' => $guardianCompany,
                    'guardian_income' => $guardianIncome,
                    'guardian_title_prefix' => $guardianTitlePrefix,
                    'guardian_title_suffix' => $guardianTitleSuffix,
                    'guardian_relation' => $guardianRelation,
                    'is_accompanied_by_guardian' => $isAccompaniedGuardian,
                    'guardian_phone' => $guardianPhone,

                    'parent_phone' => $parentPhone,
                    'parent_email' => $parentEmail,
                    'emergency_contact_name' => $emergencyContact,
                    'emergency_contact_phone' => $emergencyContactPhone,
                    'emergency_contact_relation' => $emergencyContactRelation,
                    'family_data' => $familyData,
                    'health_habits' => $healthHabits,
                    'development_psychology' => $devPsychology,
                    'pin_access' => $pinAccess,
                    'enrolled_date' => '2026-07-15',
                    'status' => 'aktif',
                ];

                $existing = Student::where('nis', $nis)->first();
                if (!$existing && !empty($nik)) {
                    $existing = Student::where('nik', $nik)->first();
                }
                if (!$existing && $birthDate && $fullName) {
                    $existing = Student::where('full_name', $fullName)->where('birth_date', $birthDate)->first();
                }

                if ($existing) {
                    $existing->update($studentData);
                    $student = $existing;
                    $updated++;
                } else {
                    $student = Student::create($studentData);
                    $imported++;
                }

                // History
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

        } else {
            // Mode Legacy (14 Kolom)
            $this->info("Menjalankan Impor Mode Legacy (Sheet DB_Murid)...");

            $bar = $this->output->createProgressBar(count($dataRows));
            $bar->start();

            foreach ($dataRows as $rIdx => $row) {
                $rawFullName = $cleanStr($row[1] ?? null);
                if (empty($rawFullName)) {
                    $bar->advance();
                    continue;
                }

                $fullName = $formatTitleCase($rawFullName);
                $rawNis = $cleanStr($row[0] ?? null);
                $nis = !empty($rawNis) ? (string)$rawNis : 'PAUD.' . str_pad($rIdx + 1, 4, '0', STR_PAD_LEFT);

                $rawJenjang = strtoupper($cleanStr($row[4] ?? null) ?? 'TK-A');
                $rawKelompok = strtoupper($cleanStr($row[5] ?? null) ?? 'TK A1');

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

                $cleanLevelKey = str_replace(' ', '-', $rawJenjang);
                $classLevel = $levels[$cleanLevelKey]
                    ?? ($levels[$rawJenjang]
                    ?? ($levels['TK-A'] ?? ClassLevel::first()));

                $cleanKelompokKey = str_replace('-', ' ', $rawKelompok);
                $classroom = $classroomMap[$rawKelompok]
                    ?? ($classroomMap[$cleanKelompokKey]
                    ?? ($classroomMap[str_replace(' ', '', $rawKelompok)]
                    ?? Classroom::where('class_level_id', $classLevel?->id)->where('academic_year_id', $academicYear->id)->first()));

                $genderRaw = strtoupper($cleanStr($row[3] ?? null) ?? '');
                $gender = (str_contains($genderRaw, 'PEREMPUAN') || $genderRaw === 'P' || $genderRaw === 'FEMALE') ? 'P' : 'L';

                $pinAccess = $cleanStr($row[13] ?? null) ?? $nis;
                $parentPhone = $cleanPhone($row[10] ?? null);

                $daycareClassroomId = ($subUnit === 'DAYCARE') ? $classroom?->id : null;
                $isTpq = ($subUnit === 'TPQ');
                $tpqClassroomId = $isTpq ? $classroom?->id : null;

                $studentData = [
                    'nis' => $nis,
                    'full_name' => $fullName,
                    'nickname' => $formatTitleCase($cleanStr($row[2] ?? null)),
                    'gender' => $gender,
                    'birth_place' => $formatTitleCase($cleanStr($row[6] ?? null)),
                    'birth_date' => $parseDate($row[7] ?? null),
                    'religion' => 'Islam',

                    'academic_year_id' => $academicYear->id,
                    'sub_unit' => $subUnit,
                    'class_level_id' => $classLevel?->id,
                    'classroom_id' => $classroom?->id,
                    'daycare_classroom_id' => $daycareClassroomId,
                    'is_tpq' => $isTpq,
                    'tpq_classroom_id' => $tpqClassroomId,

                    'address' => $cleanStr($row[12] ?? null),
                    'father_name' => $formatTitleCase($cleanStr($row[8] ?? null)),
                    'mother_name' => $formatTitleCase($cleanStr($row[9] ?? null)),
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
        }

        $this->info("==================================================================");
        $this->info(" IMPORT DATABASE MURID PG-TK-DAYCARE-TPQ SELESAI");
        $this->info("==================================================================");
        $this->table(
            ['Kategori', 'Jumlah / Keterangan'],
            [
                ['File Sumber', basename($filePath)],
                ['Mode Impor', $isComprehensive ? 'Komprehensif (175 Kolom + JSON)' : 'Legacy (14 Kolom)'],
                ['Murid Baru Ditambahkan', $imported],
                ['Murid Diperbarui', $updated],
                ['Total Murid Terproses', $imported + $updated],
                ['Tahun Ajaran Aktif', $academicYear->name],
            ]
        );

        return 0;
    }
}
