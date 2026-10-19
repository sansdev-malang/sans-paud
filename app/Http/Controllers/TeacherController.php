<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TeacherController extends Controller
{
    /**
     * Get the teacher type model.
     */
    private function getTeacherType()
    {
        return EmployeeType::firstOrCreate(
            ['code' => 'teacher'],
            ['name' => 'Guru', 'description' => 'Tenaga Pendidik']
        );
    }

    /**
     * Display a listing of teachers.
     */
    public function index(Request $request)
    {
        $activeYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear?->id;

        $teacherType = $this->getTeacherType();
        $query = Employee::where('employee_type_id', $teacherType->id)
            ->with(['classrooms' => function ($cq) use ($activeYearId) {
                if ($activeYearId) {
                    $cq->where('classrooms.academic_year_id', $activeYearId);
                }
                $cq->with(['classLevel', 'students' => function ($q) use ($activeYearId) {
                    $q->where('status', 'aktif');
                    if ($activeYearId) {
                        $q->where('academic_year_id', $activeYearId);
                    }
                }]);
            }]);

        // Filter by school unit if configured
        $schoolUnit = config('app.school_unit');
        if ($schoolUnit) {
            $query->where('unit', $schoolUnit);
        }

        // Filter by Sub-Unit diampu (Jenjang)
        $subUnit = $request->input('sub_unit');
        $subUnits = [];
        if ($request->filled('sub_unit') && $subUnit !== 'all') {
            $subUnits = ($subUnit === 'KB' || $subUnit === 'PG') ? ['PG', 'KB'] : [$subUnit];
            $query->whereHas('classrooms', function ($q) use ($subUnits, $activeYearId) {
                if ($activeYearId) {
                    $q->where('academic_year_id', $activeYearId);
                }
                $q->whereIn('sub_unit', $subUnits);
            });
        }

        // Ambil daftar kelompok terkait jenjang yang dipilih
        $classroomsQuery = \App\Models\Classroom::where('is_active', true);
        if ($activeYearId) {
            $classroomsQuery->where('academic_year_id', $activeYearId);
        }
        if (!empty($subUnits)) {
            $classroomsQuery->whereIn('sub_unit', $subUnits);
        } else {
            $classroomsQuery->orderBy('sub_unit');
        }
        $classrooms = $classroomsQuery->orderBy('name')->get();

        // Filter by Kelompok
        $classroomId = $request->input('classroom_id');
        if ($request->filled('classroom_id') && $classroomId !== 'all') {
            // Jika jenjang dipilih namun kelompok yang dipilih tidak termasuk dalam jenjang tersebut, reset filter kelompok
            if (!empty($subUnits) && !$classrooms->contains('id', $classroomId)) {
                $classroomId = null;
            } else {
                $query->whereHas('classrooms', function ($q) use ($classroomId) {
                    $q->where('id', $classroomId);
                });
            }
        }

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")->orWhere('nuptk', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Apply Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Fast single query for teacher statistics
        $statsQuery = Employee::where('employee_type_id', $teacherType->id);
        if ($schoolUnit) {
            $statsQuery->where('unit', $schoolUnit);
        }
        $teacherStats = (clone $statsQuery)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN gender IN ('Male', 'L', 'Laki-laki') THEN 1 ELSE 0 END) as male,
            SUM(CASE WHEN gender IN ('Female', 'P', 'Perempuan') THEN 1 ELSE 0 END) as female,
            SUM(CASE WHEN nuptk IS NOT NULL AND nuptk != '' THEN 1 ELSE 0 END) as certified
        ")->first();

        $totalGuru = (int) ($teacherStats->total ?? 0);
        $guruMale = (int) ($teacherStats->male ?? 0);
        $guruFemale = (int) ($teacherStats->female ?? 0);
        $certifiedCount = (int) ($teacherStats->certified ?? 0);
        $certifiedPercent = $totalGuru > 0 ? round(($certifiedCount / $totalGuru) * 100) : 0;

        // Hitung jumlah guru per jenjang
        $jenjangCounts = \App\Models\Classroom::whereNotNull('homeroom_teacher_id')
            ->when($activeYearId, fn($q) => $q->where('academic_year_id', $activeYearId))
            ->select('sub_unit', \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT homeroom_teacher_id) as total'))
            ->groupBy('sub_unit')
            ->pluck('total', 'sub_unit')
            ->toArray();

        $jenjangStats = [
            'KB' => ($jenjangCounts['PG'] ?? 0) + ($jenjangCounts['KB'] ?? 0),
            'TK' => $jenjangCounts['TK'] ?? 0,
            'DAYCARE' => $jenjangCounts['DAYCARE'] ?? 0,
            'TPQ' => $jenjangCounts['TPQ'] ?? 0,
        ];

        // Fallback untuk kelompok yang sub_unit-nya kosong/null jika ada
        $unassignedSubUnits = \App\Models\Classroom::whereNotNull('homeroom_teacher_id')
            ->when($activeYearId, fn($q) => $q->where('academic_year_id', $activeYearId))
            ->where(function($q) {
                $q->whereNull('sub_unit')->orWhere('sub_unit', '');
            })
            ->get();
        foreach ($unassignedSubUnits as $c) {
            $uName = strtoupper($c->name);
            if (str_contains($uName, 'TK')) {
                $jenjangStats['TK']++;
            } elseif (str_contains($uName, 'KB') || str_contains($uName, 'PG')) {
                $jenjangStats['KB']++;
            }
        }

        $perPage = $request->input('per_page', 10);
        if ($perPage === 'all') {
            $perPage = $query->count() > 0 ? $query->count() : 1;
        }
        $teachers = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        return view('admin.teachers.index', compact('teachers', 'totalGuru', 'guruMale', 'guruFemale', 'certifiedPercent', 'classrooms', 'jenjangStats'));
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
    {
        return redirect()->route('teachers.index');
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request)
    {
                $messages = [
            'front_title.max' => 'Gelar depan tidak boleh lebih dari 50 karakter.',
            'back_title.max' => 'Gelar belakang tidak boleh lebih dari 50 karakter.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Pilihan jenis kelamin tidak valid.',
            'birth_place.max' => 'Tempat lahir terlalu panjang (maks. 255 karakter).',
            'birth_date.date' => 'Format tanggal lahir tidak valid.',
            'address.max' => 'Alamat terlalu panjang (maks. 255 karakter).',
            'phone.max' => 'Nomor HP tidak boleh lebih dari 20 karakter.',
            'nik.max' => 'NIK tidak boleh lebih dari 255 karakter.',
            'niy.max' => 'NIY tidak boleh lebih dari 255 karakter.',
            'nuptk.max' => 'NUPTK tidak boleh lebih dari 255 karakter.',
            'last_education.max' => 'Pendidikan terakhir terlalu panjang.',
            'major.max' => 'Jurusan terlalu panjang.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto tidak boleh lebih dari 2MB.',
            'photo.uploaded' => 'Gagal mengunggah foto. Ukuran file kemungkinan terlalu besar (maksimal 2MB) atau format tidak didukung.',
        ];
        $validated = $request->validate([
            'front_title' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'back_title' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:employees,email',
            'gender' => 'required|in:Male,Female,L,P',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'nik' => 'nullable|string|max:255',
            'niy' => 'nullable|string|max:255',
            'nuptk' => 'nullable|string|max:255',
            'no_ukg' => 'nullable|string|max:255',
            'nrg' => 'nullable|string|max:255',
            'pangkat_golongan' => 'nullable|string|max:255',
            'last_education' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'additional_position' => 'nullable|string|max:255',
            'task_start_date' => 'nullable|date',
            'employment_status' => 'nullable|string|max:255',
            'appointment_date' => 'nullable|date',
            'last_sk_date' => 'nullable|date',
            'last_sk_number' => 'nullable|string|max:255',
            'work_period' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'zkteco_uid' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Leave,Inactive',
        ], $messages);

        // Handle file upload
        if ($request->hasFile('photo')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->decode($request->file('photo'));
            $image->scaleDown(width: 800);
            
            $filename = 'photos/' . uniqid() . '.webp';
            $fullPath = storage_path('app/public/' . $filename);
            
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }
            
            $image->save($fullPath, 80);
            $validated['photo'] = $filename;
        }

        $teacherType = $this->getTeacherType();
        $validated['employee_type_id'] = $teacherType->id;
        $validated['unit'] = config('app.school_unit') ?: 'paud';
        if (empty($validated['position'])) {
            $validated['position'] = 'Guru Kelas';
        }

        Employee::create($validated);

        return redirect()->route('teachers.index')->with('success', 'Berhasil menambahkan data guru baru!');
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit($id)
    {
        $teacherType = $this->getTeacherType();
        $teacher = Employee::where('employee_type_id', $teacherType->id)->findOrFail($id);
        return view('admin.teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, $id)
    {
        $teacherType = $this->getTeacherType();
        $teacher = Employee::where('employee_type_id', $teacherType->id)->findOrFail($id);

                $messages = [
            'front_title.max' => 'Gelar depan tidak boleh lebih dari 50 karakter.',
            'back_title.max' => 'Gelar belakang tidak boleh lebih dari 50 karakter.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Pilihan jenis kelamin tidak valid.',
            'birth_place.max' => 'Tempat lahir terlalu panjang (maks. 255 karakter).',
            'birth_date.date' => 'Format tanggal lahir tidak valid.',
            'address.max' => 'Alamat terlalu panjang (maks. 255 karakter).',
            'phone.max' => 'Nomor HP tidak boleh lebih dari 20 karakter.',
            'nik.max' => 'NIK tidak boleh lebih dari 255 karakter.',
            'niy.max' => 'NIY tidak boleh lebih dari 255 karakter.',
            'nuptk.max' => 'NUPTK tidak boleh lebih dari 255 karakter.',
            'last_education.max' => 'Pendidikan terakhir terlalu panjang.',
            'major.max' => 'Jurusan terlalu panjang.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto tidak boleh lebih dari 2MB.',
            'photo.uploaded' => 'Gagal mengunggah foto. Ukuran file kemungkinan terlalu besar (maksimal 2MB) atau format tidak didukung.',
        ];
        $validated = $request->validate([
            'front_title' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'back_title' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:employees,email,' . $id,
            'gender' => 'required|in:Male,Female,L,P',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'nik' => 'nullable|string|max:255',
            'niy' => 'nullable|string|max:255',
            'nuptk' => 'nullable|string|max:255',
            'no_ukg' => 'nullable|string|max:255',
            'nrg' => 'nullable|string|max:255',
            'pangkat_golongan' => 'nullable|string|max:255',
            'last_education' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'additional_position' => 'nullable|string|max:255',
            'task_start_date' => 'nullable|date',
            'employment_status' => 'nullable|string|max:255',
            'appointment_date' => 'nullable|date',
            'last_sk_date' => 'nullable|date',
            'last_sk_number' => 'nullable|string|max:255',
            'work_period' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'zkteco_uid' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Leave,Inactive',
        ], $messages);

        // Handle file upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($teacher->photo) {
                $oldPath = str_contains($teacher->photo, 'photos/') ? $teacher->photo : 'photos/' . $teacher->photo;
                Storage::disk('public')->delete($oldPath);
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->decode($request->file('photo'));
            $image->scaleDown(width: 800);
            
            $filename = 'photos/' . uniqid() . '.webp';
            $fullPath = storage_path('app/public/' . $filename);
            
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }
            
            $image->save($fullPath, 80);
            $validated['photo'] = $filename;
        }

        if (empty($validated['position'])) {
            $validated['position'] = 'Guru Kelas';
        }

        $teacher->update($validated);

        return redirect()->back()->with('success', 'Berhasil memperbarui data guru!');
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy($id)
    {
        $teacherType = $this->getTeacherType();
        $teacher = Employee::where('employee_type_id', $teacherType->id)->findOrFail($id);

        // Delete photo file if exists
        if ($teacher->photo) {
            $oldPath = str_contains($teacher->photo, 'photos/') ? $teacher->photo : 'photos/' . $teacher->photo;
            Storage::disk('public')->delete($oldPath);
        }

        $teacher->delete();

        return redirect()->back()->with('success', 'Berhasil menghapus data guru!');
    }

    /**
     * Download Excel template for teachers.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'Gelar Depan',
            'Nama Lengkap',
            'Gelar Belakang',
            'Email',
            'Unit Sekolah (paud)',
            'Jenis Kelamin (L/P)',
            'Tempat Lahir',
            'Tanggal Lahir (YYYY-MM-DD)',
            'NIK',
            'NIY',
            'NUPTK',
            'NO UKG',
            'NRG',
            'Pangkat / Golongan',
            'Pendidikan Terakhir',
            'Jurusan',
            'Jabatan Utama',
            'Jabatan Tambahan',
            'Tanggal Mulai Tugas (YYYY-MM-DD)',
            'Status Kepegawaian',
            'Tanggal Diangkat (YYYY-MM-DD)',
            'Tanggal SK Terakhir (YYYY-MM-DD)',
            'Nomor SK Terakhir',
            'Masa Kerja Golongan',
            'Alamat',
            'No. HP / WA',
            'Catatan',
            'ID ZKTeco (Alfanumerik)',
            'Status (Active/Leave/Inactive)'
        ];

        // Example data row
        $example = [
            'Dr.',
            'Budi Santoso',
            'S.Pd.',
            'budi@example.com',
            config('app.school_unit') ?: 'sd',
            'L',
            'Malang',
            '1985-01-01',
            '3573010101850001',
            '123456',
            '198501012010121002',
            'UKG-001',
            'NRG-123',
            'Gol III/A',
            'S1 Administrasi',
            'Administrasi Perkantoran',
            'Guru Kelas',
            'Wali Kelas',
            '2010-01-01',
            'PTT',
            '2010-01-01',
            '2025-01-01',
            'SK-001/2025',
            '15 Tahun',
            'Jl. Merdeka No. 1, Malang',
            '081234567890',
            '-',
            '',
            'Active'
        ];

        // Put headers in row 1
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        // Put example in row 2
        foreach ($example as $colIndex => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '2', $val);
        }

        // Format headers (bold text & light gray background fill)
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Auto-size columns
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        if (request()->filled('download_token')) {
            if (!headers_sent()) {
                @setcookie('download_token', request()->query('download_token'), time() + 60, '/', '', false, false);
            }
        }

        $writer = new Xlsx($spreadsheet);

        $response = response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Template_Guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);

        if (request()->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', request()->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Import teachers from Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file wajib berupa .xlsx atau .xls.'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Remove header row
        $header = array_shift($rows);

        $errors = [];
        $importedCount = 0;
        $teacherType = $this->getTeacherType();

        foreach ($rows as $index => $row) {
            // Skip empty rows (must have name)
            if (empty($row[1])) {
                continue;
            }

            // Map variables
            $front_title = !empty($row[0]) ? trim($row[0]) : null;
            $name = trim($row[1]);
            $back_title = !empty($row[2]) ? trim($row[2]) : null;
            $email = !empty($row[3]) ? trim($row[3]) : null;
            $unit = strtolower(trim($row[4]));
            $gender = trim($row[5]);
            $birth_place = !empty($row[6]) ? trim($row[6]) : null;
            $birth_date = !empty($row[7]) ? date('Y-m-d', strtotime(trim($row[7]))) : null;
            $nik = !empty($row[8]) ? trim($row[8]) : null;
            $niy = !empty($row[9]) ? trim($row[9]) : null;
            $nuptk = !empty($row[10]) ? trim($row[10]) : null;
            $no_ukg = !empty($row[11]) ? trim($row[11]) : null;
            $nrg = !empty($row[12]) ? trim($row[12]) : null;
            $pangkat_golongan = !empty($row[13]) ? trim($row[13]) : null;
            $last_education = !empty($row[14]) ? trim($row[14]) : null;
            $major = !empty($row[15]) ? trim($row[15]) : null;
            $position = !empty($row[16]) ? trim($row[16]) : null;
            $additional_position = !empty($row[17]) ? trim($row[17]) : null;
            $task_start_date = !empty($row[18]) ? date('Y-m-d', strtotime(trim($row[18]))) : null;
            $employment_status = !empty($row[19]) ? trim($row[19]) : null;
            $appointment_date = !empty($row[20]) ? date('Y-m-d', strtotime(trim($row[20]))) : null;
            $last_sk_date = !empty($row[21]) ? date('Y-m-d', strtotime(trim($row[21]))) : null;
            $last_sk_number = !empty($row[22]) ? trim($row[22]) : null;
            $work_period = !empty($row[23]) ? trim($row[23]) : null;
            $address = !empty($row[24]) ? trim($row[24]) : null;
            $phone = !empty($row[25]) ? trim($row[25]) : null;
            $notes = !empty($row[26]) ? trim($row[26]) : null;
            $zkteco_uid = !empty($row[27]) ? trim($row[27]) : null;
            $status = !empty($row[28]) ? trim($row[28]) : 'Active';

            $schoolUnit = config('app.school_unit');
            if ($schoolUnit && $unit !== $schoolUnit) {
                $errors[] = "Baris " . ($index + 2) . ": Unit sekolah harus '{$schoolUnit}' sesuai konfigurasi sistem.";
                continue;
            }

            if (!in_array($unit, ['paud', 'sd', 'smp'])) {
                $errors[] = "Baris " . ($index + 2) . ": Unit sekolah harus 'paud', 'sd', atau 'smp'.";
                continue;
            }

            // Translasi L/P ke Male/Female
            if (strtoupper($gender) === 'L' || strtolower($gender) === 'laki-laki' || strtolower($gender) === 'male') {
                $gender = 'Male';
            } elseif (strtoupper($gender) === 'P' || strtolower($gender) === 'perempuan' || strtolower($gender) === 'female') {
                $gender = 'Female';
            }

            if (!in_array($gender, ['Male', 'Female'])) {
                $errors[] = "Baris " . ($index + 2) . ": Jenis kelamin harus 'L' atau 'P'.";
                continue;
            }

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (Employee::where('email', $email)->exists()) {
                    $errors[] = "Baris " . ($index + 2) . ": Email '{$email}' sudah terdaftar.";
                    continue;
                }
            }

            if ($nik && Employee::where('nik', $nik)->exists()) {
                $errors[] = "Baris " . ($index + 2) . ": NIK '{$nik}' sudah terdaftar.";
                continue;
            }

            if ($nuptk && Employee::where('nuptk', $nuptk)->exists()) {
                $errors[] = "Baris " . ($index + 2) . ": NUPTK '{$nuptk}' sudah terdaftar.";
                continue;
            }

            if ($zkteco_uid) {
                // Check if ID ZKTeco is already taken by another employee (as string check)
                $exists = Employee::where('zkteco_uid', $zkteco_uid)->exists();
                if ($exists) {
                    $errors[] = "Baris " . ($index + 2) . ": ID ZKTeco '{$zkteco_uid}' sudah digunakan oleh pegawai lain.";
                    continue;
                }
            }

            if (strtolower($status) == 'active') {
                $status = 'Active';
            } elseif (strtolower($status) == 'leave') {
                $status = 'Leave';
            } else {
                $status = 'Inactive';
            }

            // Create teacher
            Employee::create([
                'front_title' => $front_title,
                'name' => $name,
                'back_title' => $back_title,
                'email' => $email,
                'employee_type_id' => $teacherType->id,
                'unit' => $unit,
                'gender' => $gender,
                'birth_place' => $birth_place,
                'birth_date' => $birth_date,
                'nik' => $nik,
                'niy' => $niy,
                'nuptk' => $nuptk,
                'no_ukg' => $no_ukg,
                'nrg' => $nrg,
                'pangkat_golongan' => $pangkat_golongan,
                'last_education' => $last_education,
                'major' => $major,
                'position' => $position,
                'additional_position' => $additional_position,
                'task_start_date' => $task_start_date,
                'employment_status' => $employment_status,
                'appointment_date' => $appointment_date,
                'last_sk_date' => $last_sk_date,
                'last_sk_number' => $last_sk_number,
                'work_period' => $work_period,
                'address' => $address,
                'phone' => $phone,
                'notes' => $notes,
                'zkteco_uid' => $zkteco_uid,
                'status' => $status,
            ]);

            $importedCount++;
        }

        if (count($errors) > 0) {
            return redirect()->back()
                ->with('success', "Impor selesai. Berhasil mengimpor {$importedCount} data guru.")
                ->with('import_errors', $errors);
        }

        return redirect()->back()->with('success', "Berhasil mengimpor {$importedCount} data guru!");
    }

    /**
     * Get filtered teachers collection for export.
     */
    private function getFilteredTeachers(Request $request)
    {
        $teacherType = $this->getTeacherType();
        $query = Employee::where('employee_type_id', $teacherType->id)
            ->with(['classrooms.classLevel', 'classrooms.students' => function ($q) {
                $q->where('status', 'aktif');
            }]);

        // Filter by school unit if configured
        $schoolUnit = config('app.school_unit');
        if ($schoolUnit) {
            $query->where('unit', $schoolUnit);
        }

        // Filter by Sub-Unit diampu (Jenjang)
        $subUnit = $request->input('sub_unit');
        $subUnits = [];
        if ($request->filled('sub_unit') && $subUnit !== 'all') {
            $subUnits = ($subUnit === 'KB' || $subUnit === 'PG') ? ['PG', 'KB'] : [$subUnit];
            $query->whereHas('classrooms', function ($q) use ($subUnits) {
                $q->whereIn('sub_unit', $subUnits);
            });
        }

        // Filter by Kelompok
        $classroomId = $request->input('classroom_id');
        if ($request->filled('classroom_id') && $classroomId !== 'all') {
            $query->whereHas('classrooms', function ($q) use ($classroomId) {
                $q->where('id', $classroomId);
            });
        }

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nuptk', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Apply Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Export filtered teachers data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $teachers = $this->getFilteredTeachers($request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');

        $headers = [
            'No',
            'Nama Lengkap & Gelar',
            'Gelar Depan',
            'Nama (Tanpa Gelar)',
            'Gelar Belakang',
            'Jenis Kelamin',
            'Jabatan Utama',
            'Jabatan Tambahan',
            'Kelompok Diampu',
            'Total Murid Asuhan',
            'Status Keaktifan',
            'Status Kepegawaian',
            'NIK',
            'NIY / NIP',
            'NUPTK',
            'No. UKG',
            'NRG',
            'Pangkat / Golongan',
            'Pendidikan Terakhir',
            'Jurusan',
            'Tempat Lahir',
            'Tanggal Lahir',
            'No. HP / WA',
            'Email',
            'TMT (Mulai Tugas)',
            'Tanggal Diangkat',
            'Nomor SK Terakhir',
            'Tanggal SK Terakhir',
            'Masa Kerja',
            'ID ZKTeco (PIN)',
            'Alamat',
            'Catatan'
        ];

        // Tulis Header di baris 1
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        // Tulis Data baris 2 dst
        $row = 2;
        $no = 1;
        foreach ($teachers as $t) {
            $fullName = ($t->front_title ? $t->front_title . ' ' : '') . $t->name . ($t->back_title ? ', ' . $t->back_title : '');
            $genderText = in_array($t->gender, ['Female', 'P', 'Perempuan']) ? 'Perempuan' : 'Laki-laki';
            
            $statusText = 'Aktif';
            if ($t->status == 'Leave') $statusText = 'Cuti';
            if ($t->status == 'Inactive') $statusText = 'Nonaktif';

            $assignedClassrooms = $t->classrooms;
            $classroomsStr = $assignedClassrooms->map(fn($c) => "[{$c->sub_unit}] {$c->name}")->implode(', ');
            if (empty($classroomsStr)) {
                $classroomsStr = '-';
            }
            $totalMurid = $assignedClassrooms->sum(fn($c) => $c->students->count());

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $fullName);
            $sheet->setCellValue('C' . $row, $t->front_title ?? '');
            $sheet->setCellValue('D' . $row, $t->name);
            $sheet->setCellValue('E' . $row, $t->back_title ?? '');
            $sheet->setCellValue('F' . $row, $genderText);
            $sheet->setCellValue('G' . $row, $t->position ?? '-');
            $sheet->setCellValue('H' . $row, $t->additional_position ?? '-');
            $sheet->setCellValue('I' . $row, $classroomsStr);
            $sheet->setCellValue('J' . $row, $totalMurid);
            $sheet->setCellValue('K' . $row, $statusText);
            $sheet->setCellValue('L' . $row, $t->employment_status ?? '-');
            $sheet->setCellValueExplicit('M' . $row, (string) ($t->nik ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('N' . $row, (string) ($t->niy ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('O' . $row, (string) ($t->nuptk ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('P' . $row, (string) ($t->no_ukg ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('Q' . $row, (string) ($t->nrg ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('R' . $row, $t->pangkat_golongan ?? '-');
            $sheet->setCellValue('S' . $row, $t->last_education ?? '-');
            $sheet->setCellValue('T' . $row, $t->major ?? '-');
            $sheet->setCellValue('U' . $row, $t->birth_place ?? '-');
            $sheet->setCellValue('V' . $row, !empty($t->birth_date) ? date('d/m/Y', strtotime($t->birth_date)) : '-');
            $sheet->setCellValueExplicit('W' . $row, (string) ($t->phone ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('X' . $row, $t->email ?? '-');
            $sheet->setCellValue('Y' . $row, !empty($t->task_start_date) ? date('d/m/Y', strtotime($t->task_start_date)) : '-');
            $sheet->setCellValue('Z' . $row, !empty($t->appointment_date) ? date('d/m/Y', strtotime($t->appointment_date)) : '-');
            $sheet->setCellValue('AA' . $row, $t->last_sk_number ?? '-');
            $sheet->setCellValue('AB' . $row, !empty($t->last_sk_date) ? date('d/m/Y', strtotime($t->last_sk_date)) : '-');
            $sheet->setCellValue('AC' . $row, $t->work_period ?? '-');
            $sheet->setCellValueExplicit('AD' . $row, (string) ($t->zkteco_uid ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('AE' . $row, $t->address ?? '-');
            $sheet->setCellValue('AF' . $row, $t->notes ?? '-');

            $row++;
        }

        // Styling Header
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4F46E5'); // Indigo 600
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Auto-size columns
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Border styling untuk seluruh data
        if ($row > 2) {
            $dataRange = 'A1:' . $lastCol . ($row - 1);
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));
        }

        $fileName = 'Data_Guru_PAUD_' . date('Ymd_His') . '.xlsx';

        if ($request->filled('download_token')) {
            setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export filtered teachers data to PDF.
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        $teachers = $this->getFilteredTeachers($request);
        $fileName = 'Data_Guru_PAUD_' . date('Ymd_His') . '.pdf';

        $filterSubUnit = $request->input('sub_unit');
        $filterClassroomId = $request->input('classroom_id');
        $filterStatus = $request->input('status');

        $activeSubUnitLabel = 'Semua Jenjang';
        if ($filterSubUnit && $filterSubUnit !== 'all') {
            $labels = ['PG' => 'Playgroup (KB)', 'TK' => 'TK', 'DAYCARE' => 'Daycare', 'TPQ' => 'TPQ'];
            $activeSubUnitLabel = $labels[$filterSubUnit] ?? $filterSubUnit;
        }

        $activeClassroomLabel = 'Semua Kelompok';
        if ($filterClassroomId && $filterClassroomId !== 'all') {
            $cls = \App\Models\Classroom::find($filterClassroomId);
            if ($cls) {
                $activeClassroomLabel = $cls->name;
            }
        }

        $principal = Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $headmasterName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $headmasterNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.teachers.pdf', [
            'teachers' => $teachers,
            'activeSubUnitLabel' => $activeSubUnitLabel,
            'activeClassroomLabel' => $activeClassroomLabel,
            'filterStatus' => $filterStatus,
            'headmasterName' => $headmasterName,
            'headmasterNip' => $headmasterNip,
            'schoolCity' => $schoolCity,
        ])->setPaper('a4', 'landscape');

        $response = $pdf->download($fileName);
        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }
        return $response;
    }
}

