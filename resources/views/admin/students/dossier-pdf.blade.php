<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dossier Murid - {{ $student->full_name }} ({{ $student->nis ?? $student->id }})</title>
    <style>
        @page {
            margin: 10mm 12mm 10mm 12mm;
            size: a4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            line-height: 1.35;
        }
        
        /* HEADER KOP */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .school-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .school-subtitle {
            font-size: 9pt;
            font-weight: 600;
            color: #4f46e5;
            text-align: center;
            margin-top: 1px;
        }
        .school-address {
            font-size: 7.5pt;
            color: #64748b;
            text-align: center;
            margin-top: 2px;
        }
        .kop-line {
            border-top: 2px solid #4f46e5;
            border-bottom: 1px solid #94a3b8;
            height: 2px;
            margin: 6px 0 10px 0;
        }

        /* DOCUMENT TITLE */
        .doc-title-container {
            text-align: center;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 8pt;
            color: #475569;
            margin-top: 2px;
        }

        /* SECTION HEADER */
        .section-header {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 3.5px 6px;
            border-radius: 2px;
            margin-top: 8px;
            margin-bottom: 4px;
        }

        /* DATA TABLE */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.data-table td, table.data-table th {
            padding: 3.5px 5px;
            font-size: 7.8pt;
            vertical-align: top;
            border: 1px solid #e2e8f0;
        }
        .td-label {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            width: 25%;
        }
        .td-val {
            color: #0f172a;
            font-weight: normal;
            width: 25%;
        }
        .td-val-full {
            color: #0f172a;
            font-weight: normal;
            width: 75%;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-active {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        /* PHOTO BOX */
        .photo-container {
            width: 85px;
            height: 110px;
            border: 1px solid #cbd5e1;
            padding: 2px;
            text-align: center;
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .photo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-placeholder {
            width: 100%;
            height: 100%;
            background-color: #f1f5f9;
            display: table;
            text-align: center;
        }
        .photo-placeholder-text {
            display: table-cell;
            vertical-align: middle;
            font-size: 7pt;
            color: #94a3b8;
            font-weight: bold;
        }

        /* COMPARATIVE TABLE (ORANG TUA) */
        table.comp-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.comp-table th {
            background-color: #3b82f6;
            color: #ffffff;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #93c5fd;
        }
        table.comp-table td {
            padding: 3.5px 6px;
            font-size: 7.8pt;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.comp-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* SIGNATURE SECTION */
        .sig-container {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .sig-container td {
            border: none;
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
            padding: 0 10px;
        }
        .sig-space {
            height: 48px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        .sig-title {
            font-size: 7.5pt;
            color: #64748b;
        }

        .footer-note {
            margin-top: 12px;
            font-size: 6.8pt;
            color: #94a3b8;
            text-align: right;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    @php
        $fd = $student->family_data ?? [];
        $hh = $student->health_habits ?? [];
        $dp = $student->development_psychology ?? [];

        $photoSrc = null;
        if (!empty($student->student_photo_url)) {
            if (filter_var($student->student_photo_url, FILTER_VALIDATE_URL)) {
                $photoSrc = $student->student_photo_url;
            } else {
                $filePath = public_path(ltrim($student->student_photo_url, '/'));
                if (file_exists($filePath)) {
                    $type = pathinfo($filePath, PATHINFO_EXTENSION);
                    $data = file_get_contents($filePath);
                    $photoSrc = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }
        }
    @endphp

    <!-- KOP RESMI LEMBAGA -->
    <table class="kop-table">
        <tr>
            @if(!empty($logoSrc))
                <td style="width: 55px; vertical-align: middle; text-align: left;">
                    <img src="{{ $logoSrc }}" style="height: 46px; width: auto; max-width: 55px;" alt="Logo">
                </td>
            @endif
            <td style="vertical-align: middle; text-align: center;">
                <div class="school-name">{{ strtoupper($schoolName) }}</div>
                <div class="school-subtitle">PENDIDIKAN ANAK USIA DINI: KB &bull; TK &bull; DAYCARE &bull; TPQ</div>
                <div class="school-address">{{ $schoolAddress }} &bull; Telp/WA: {{ $schoolPhone }} &bull; Email: {{ $schoolEmail }}</div>
            </td>
            @if(!empty($logoSrc))
                <td style="width: 55px;"></td>
            @endif
        </tr>
    </table>
    <div class="kop-line"></div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title-container">
        <h1 class="doc-title">LEMBAR DOSSIER &amp; BUKU INDUK MURID</h1>
        <div class="doc-subtitle">
            Nomor Induk Murid (NIS): <strong>{{ $student->nis ?? '-' }}</strong> &bull; NISN: <strong>{{ $student->nisn ?? '-' }}</strong> &bull; Status: <strong>{{ strtoupper($student->status ?? 'Aktif') }}</strong>
        </div>
    </div>

    <!-- HERO PROFIL SINGKAT DENGAN PAS FOTO -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
        <tr>
            <td style="width: 95px; vertical-align: top; border: none; padding: 0 10px 0 0;">
                <div class="photo-container">
                    @if($photoSrc)
                        <img src="{{ $photoSrc }}" class="photo-img" alt="Foto Murid">
                    @else
                        <div class="photo-placeholder">
                            <div class="photo-placeholder-text">PAS FOTO<br>3 x 4 cm</div>
                        </div>
                    @endif
                </div>
            </td>
            <td style="vertical-align: top; border: none; padding: 0;">
                <table class="data-table">
                    <tr>
                        <td class="td-label">Nama Lengkap Murid</td>
                        <td class="td-val-full" colspan="3" style="font-size: 9pt; font-weight: bold; color: #1e1b4b;">{{ strtoupper($student->full_name) }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Nama Panggilan</td>
                        <td class="td-val"><strong>{{ $student->nickname ?? '-' }}</strong></td>
                        <td class="td-label">Jenis Kelamin</td>
                        <td class="td-val">{{ $student->gender == 'L' ? 'Laki-laki' : ($student->gender == 'P' ? 'Perempuan' : $student->gender) }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Tempat, Tanggal Lahir</td>
                        <td class="td-val">{{ $student->birth_place ?? '-' }}, {{ $student->birth_date ? $student->birth_date->translatedFormat('d F Y') : '-' }}</td>
                        <td class="td-label">Usia Saat Ini</td>
                        <td class="td-val">{{ $student->age }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Tahun Ajaran / Jenjang</td>
                        <td class="td-val">{{ $student->academicYear?->name ?? '-' }} / {{ $student->jenjang?->name ?? ($student->sub_unit ?? '-') }}</td>
                        <td class="td-label">Kelompok Belajar (Rombel)</td>
                        <td class="td-val"><strong>{{ $student->classroom?->name ?? '-' }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 1. IDENTITAS & LEGALITAS KEPENDUDUKAN -->
    <div class="section-header">1. Identitas Pokok &amp; Legalitas Kependudukan (Dapodik)</div>
    <table class="data-table">
        <tr>
            <td class="td-label">Nomor Induk Kependudukan (NIK)</td>
            <td class="td-val"><strong>{{ $student->nik ?? '-' }}</strong></td>
            <td class="td-label">Nomor Kartu Keluarga (KK)</td>
            <td class="td-val"><strong>{{ $student->no_kk ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="td-label">No. Registrasi Akta Lahir</td>
            <td class="td-val">{{ $student->no_akta ?? '-' }}</td>
            <td class="td-label">Agama / Kepercayaan</td>
            <td class="td-val">{{ $student->religion ?? '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">Kewarganegaraan</td>
            <td class="td-val">{{ $student->citizenship ?? 'Indonesia (WNI)' }}</td>
            <td class="td-label">Anak Ke- / Jml Saudara</td>
            <td class="td-val">Anak ke-{{ $student->child_order ?? '-' }} dari {{ $student->siblings_count ?? '-' }} bersaudara</td>
        </tr>
        <tr>
            <td class="td-label">Kebutuhan Khusus</td>
            <td class="td-val">
                @if(str_contains(strtolower($student->special_needs ?? ''), 'ya'))
                    <span style="color: #dc2626; font-weight: bold;">Ya: {{ $student->special_needs_detail ?? $student->special_needs }}</span>
                @else
                    Tidak Ada
                @endif
            </td>
            <td class="td-label">PIN Akses Kios Presensi</td>
            <td class="td-val">{{ $student->pin_access ?? '-' }}</td>
        </tr>
    </table>

    <!-- 2. PENEMPATAN AKADEMIK & PROGRAM LAYANAN -->
    <div class="section-header">2. Penempatan Akademik &amp; Program Layanan</div>
    <table class="data-table">
        <tr>
            <td class="td-label">Tahun Masuk / Ajaran</td>
            <td class="td-val">{{ $student->academicYear?->name ?? '-' }}</td>
            <td class="td-label">Tanggal Masuk / Terdaftar</td>
            <td class="td-val">{{ $student->enrolled_date ? $student->enrolled_date->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">Jenjang Utama</td>
            <td class="td-val"><strong>{{ $student->jenjang?->name ?? ($student->sub_unit ?? '-') }}</strong></td>
            <td class="td-label">Kelompok Belajar Utama</td>
            <td class="td-val"><strong>{{ $student->classroom?->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="td-label">Wali Kelas</td>
            <td class="td-val">{{ $student->classroom?->homeroomTeacher?->name ?? '-' }}</td>
            <td class="td-label">Program Tambahan</td>
            <td class="td-val">
                @php
                    $progs = [];
                    if($student->daycareClassroom) $progs[] = 'Daycare (' . $student->daycareClassroom->name . ')';
                    if($student->is_tpq || $student->tpqClassroom) $progs[] = 'TPQ (' . ($student->tpqClassroom?->name ?? 'Program TPQ') . ')';
                @endphp
                {{ count($progs) > 0 ? implode(', ', $progs) : 'Hanya Program Reguler' }}
            </td>
        </tr>
    </table>

    <!-- 3. ALAMAT & DOMISILI -->
    <div class="section-header">3. Alamat Tinggal &amp; Lingkungan Domisili</div>
    <table class="data-table">
        <tr>
            <td class="td-label">Alamat Rumah Lengkap</td>
            <td class="td-val-full" colspan="3">{{ $student->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">RT / RW &bull; Dusun</td>
            <td class="td-val">RT {{ $student->rt ?? '-' }} / RW {{ $student->rw ?? '-' }} &bull; {{ $student->dusun ?? '-' }}</td>
            <td class="td-label">Kelurahan / Desa</td>
            <td class="td-val">{{ $student->kelurahan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">Kecamatan</td>
            <td class="td-val">{{ $student->kecamatan ?? '-' }}</td>
            <td class="td-label">Kota / Kabupaten &bull; Kode Pos</td>
            <td class="td-val">{{ $student->city ?? '-' }} &bull; {{ $student->postal_code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">Tinggal Bersama</td>
            <td class="td-val">{{ $student->living_with ?? 'Orang Tua' }}</td>
            <td class="td-label">Transportasi &bull; Jarak</td>
            <td class="td-val">{{ $student->transportation ?? '-' }} ({{ $student->distance_to_school ? $student->distance_to_school . ' km' : '-' }}, &plusmn; {{ $student->travel_time_minutes ? $student->travel_time_minutes . ' mnt' : '-' }})</td>
        </tr>
    </table>

    <!-- 4. DATA ORANG TUA / WALI -->
    <div class="section-header">4. Data Orang Tua &amp; Wali Murid</div>
    <table class="comp-table">
        <thead>
            <tr>
                <th width="20%">Rincian Data</th>
                <th width="40%">Data Ayah Kandung</th>
                <th width="40%">Data Ibu Kandung</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">Nama Lengkap</td>
                <td><strong>{{ $student->father_name ?? '-' }}</strong></td>
                <td><strong>{{ $student->mother_name ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">NIK</td>
                <td>{{ $student->father_nik ?? '-' }}</td>
                <td>{{ $student->mother_nik ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">Tempat, Tgl Lahir</td>
                <td>{{ $student->father_birth_place ?? '-' }}, {{ $student->father_birth_date ? $student->father_birth_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $student->mother_birth_place ?? '-' }}, {{ $student->mother_birth_date ? $student->mother_birth_date->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">Pendidikan Terakhir</td>
                <td>{{ $student->father_education ?? '-' }}</td>
                <td>{{ $student->mother_education ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">Pekerjaan &bull; Instansi</td>
                <td>{{ $student->father_job ?? '-' }} &bull; {{ $student->father_company ?? '-' }}</td>
                <td>{{ $student->mother_job ?? '-' }} &bull; {{ $student->mother_company ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">Penghasilan Bulanan</td>
                <td>{{ $student->father_income ?? '-' }}</td>
                <td>{{ $student->mother_income ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: 600; background-color: #f8fafc;">Nomor Telepon / WA</td>
                <td><strong>{{ $student->father_phone ?? '-' }}</strong></td>
                <td><strong>{{ $student->mother_phone ?? '-' }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if($student->guardian_name)
    <table class="data-table" style="margin-top: -3px;">
        <tr>
            <td class="td-label" style="width: 20%;">Wali Murid</td>
            <td class="td-val" style="width: 40%;"><strong>{{ $student->guardian_name }}</strong> (Hubungan: {{ $student->guardian_relation ?? 'Wali' }})</td>
            <td class="td-label" style="width: 20%;">Pekerjaan &bull; No. HP</td>
            <td class="td-val" style="width: 20%;">{{ $student->guardian_job ?? '-' }} &bull; {{ $student->guardian_phone ?? '-' }}</td>
        </tr>
    </table>
    @endif

    <table class="data-table" style="margin-top: -1px;">
        <tr>
            <td class="td-label" style="width: 20%;">Kontak Darurat</td>
            <td class="td-val" style="width: 40%;">{{ $student->emergency_contact_name ?? '-' }} ({{ $student->emergency_contact_relation ?? 'Keluarga' }})</td>
            <td class="td-label" style="width: 20%;">No. Telp Darurat</td>
            <td class="td-val" style="width: 20%;"><strong>{{ $student->emergency_contact_phone ?? '-' }}</strong></td>
        </tr>
    </table>

    <!-- 5. FISIK, KESEHATAN & ANTROPOMETRI -->
    <div class="section-header">5. Antropometri &amp; Catatan Kesehatan Murid</div>
    <table class="data-table">
        <tr>
            <td class="td-label">Tinggi Badan (TB)</td>
            <td class="td-val"><strong>{{ $student->height ? $student->height . ' cm' : '-' }}</strong></td>
            <td class="td-label">Berat Badan (BB)</td>
            <td class="td-val"><strong>{{ $student->weight ? $student->weight . ' kg' : '-' }}</strong></td>
        </tr>
        <tr>
            <td class="td-label">Lingkar Kepala &bull; Lengan</td>
            <td class="td-val">{{ $student->head_circumference ? $student->head_circumference . ' cm' : '-' }} &bull; {{ $student->arm_circumference ? $student->arm_circumference . ' cm' : '-' }}</td>
            <td class="td-label">Golongan Darah</td>
            <td class="td-val"><strong>{{ $student->blood_type ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="td-label">Riwayat Penyakit / Alergi</td>
            <td class="td-val-full" colspan="3">{{ $hh['illnesses_accidents'] ?? ($hh['allergies'] ?? 'Tidak ada riwayat alergi berat') }}</td>
        </tr>
        <tr>
            <td class="td-label">Pola Makan &bull; Tidur</td>
            <td class="td-val-full" colspan="3">Makan: {{ $hh['eating_habits'] ?? '-' }} &bull; Tidur: {{ $hh['sleep_habits'] ?? '-' }}</td>
        </tr>
    </table>

    <!-- 6. TUMBUH KEMBANG & PSIKOLOGI -->
    <div class="section-header">6. Tumbuh Kembang, Minat &amp; Harapan Pendidikan</div>
    <table class="data-table">
        <tr>
            <td class="td-label">Hobi / Minat Murid</td>
            <td class="td-val">{{ $student->student_hobby ?? '-' }}</td>
            <td class="td-label">Cita-cita Murid</td>
            <td class="td-val">{{ $student->student_aspiration ?? '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">Harapan Orang Tua</td>
            <td class="td-val-full" colspan="3">{{ $student->parent_aspiration ?? '-' }}</td>
        </tr>
        <tr>
            <td class="td-label">Kemandirian &bull; Bahasa</td>
            <td class="td-val-full" colspan="3">Kemandirian: {{ $dp['independence'] ?? 'Mampu mandiri sesuai tahapan usia' }} &bull; Bahasa Sehari-hari: {{ $dp['spoken_language'] ?? 'Bahasa Indonesia' }}</td>
        </tr>
    </table>

    <!-- TANDA TANGAN PENGESAHAN -->
    <table class="sig-container">
        <tr>
            <td style="width: 33%;">
                Mengetahui,<br>
                Orang Tua / Wali Murid
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $student->father_name ?? ($student->mother_name ?? ($student->guardian_name ?? '..................................')) }} )</div>
                <div class="sig-title">Orang Tua / Wali</div>
            </td>
            <td style="width: 34%;">
                Wali Kelas / Pendidik
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $student->classroom?->homeroomTeacher?->name ?? '..................................' }} )</div>
                <div class="sig-title">Kelompok {{ $student->classroom?->name ?? '-' }}</div>
            </td>
            <td style="width: 33%;">
                Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Kepala Sekolah {{ $schoolName }}
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $principalName ?? 'Kepala Sekolah' }} )</div>
                <div class="sig-title">NIY: {{ $principalNiy ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan resmi melalui Sistem Akademik &amp; Administrasi Digital {{ $schoolName }} (SAANS PAUD) pada {{ date('d/m/Y H:i') }} WIB.
    </div>

</body>
</html>
