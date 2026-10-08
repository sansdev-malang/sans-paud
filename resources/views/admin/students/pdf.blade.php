<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Murid - PAUD Anak Saleh</title>
    <style>
        @page {
            margin: 12mm 10mm 12mm 10mm;
            size: a4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            line-height: 1.35;
        }
        .header-container {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .subtitle {
            text-align: center;
            font-size: 9pt;
            color: #475569;
            margin: 2px 0 0 0;
            font-weight: 600;
        }
        .meta-info {
            display: table;
            width: 100%;
            margin-top: 6px;
            font-size: 7.5pt;
            color: #64748b;
        }
        .meta-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .meta-left {
            text-align: left;
        }
        .meta-right {
            text-align: right;
        }
        .meta-tag {
            font-weight: bold;
            color: #4f46e5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.3px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .label {
            color: #64748b;
            font-size: 7pt;
        }
        .val {
            font-weight: bold;
            color: #0f172a;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6.5pt;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-aktif {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-lulus {
            background-color: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-mutasi {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-other {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .badge-gender-l {
            background-color: #e0e7ff;
            color: #4338ca;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .badge-gender-p {
            background-color: #ffe4e6;
            color: #be123c;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .footer-summary {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-size: 7.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <h1 class="title">Daftar Data Murid (Peserta Didik)</h1>
        <p class="subtitle">PAUD ISLAM ANAK SALEH MALANG (KB - TK - DAYCARE - TPQ)</p>
        <div class="meta-info">
            <div class="meta-cell meta-left">
                <span>Tahun Ajaran: <strong class="meta-tag">{{ $selectedYear ? $selectedYear->name : 'Semua T.A.' }}</strong></span>
                <span style="margin-left: 12px;">Jenjang: <strong class="meta-tag">{{ $selectedJenjang ? $selectedJenjang->name : 'Semua Jenjang' }}</strong></span>
                <span style="margin-left: 12px;">Total Murid: <strong class="meta-tag">{{ number_format($students->count()) }} Orang</strong> (L: {{ $students->where('gender', 'L')->count() }}, P: {{ $students->where('gender', 'P')->count() }})</span>
            </div>
            <div class="meta-cell meta-right">
                <span>Tanggal Cetak: <strong>{{ date('d/m/Y H:i') }} WIB</strong></span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="22%">Identitas Murid</th>
                <th width="17%">Kelahiran & Usia</th>
                <th width="20%">Penempatan & Kelompok</th>
                <th width="20%">Data Orang Tua / Kontak</th>
                <th width="18%">Domisili & Administrasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $s)
                @php
                    $gender = strtoupper($s->gender ?? 'L');
                    $isMale = in_array($gender, ['L', 'LAKI-LAKI', 'MALE']);
                    $genderLabel = $isMale ? 'Putra (L)' : 'Putri (P)';
                    $birthDateFormatted = $s->birth_date ? date('d/m/Y', strtotime($s->birth_date)) : '-';
                    $jName = $s->jenjang?->name ?? $s->classroom?->jenjang?->name ?? $s->classLevel?->jenjang?->name ?? $s->sub_unit ?? '-';
                    $className = $s->classroom?->name ?? '-';
                    $classLevelName = $s->classroom?->classLevel?->name ?? $s->classLevel?->name ?? '-';
                    $homeroomName = $s->classroom?->homeroomTeacher?->name ?? '-';
                    $status = strtolower($s->status ?? 'aktif');
                    $badgeClass = match($status) {
                        'aktif' => 'badge-aktif',
                        'lulus' => 'badge-lulus',
                        'mutasi' => 'badge-mutasi',
                        default => 'badge-other'
                    };
                @endphp
                <tr>
                    <td class="text-center val">{{ $index + 1 }}</td>
                    
                    <!-- Identitas Murid -->
                    <td>
                        <span class="val" style="font-size: 8.5pt;">{{ $s->full_name }}</span>
                        @if(!empty($s->nickname))
                            <span style="color: #64748b;">({{ $s->nickname }})</span>
                        @endif
                        <br>
                        <span class="label">Gender:</span> 
                        <span class="{{ $isMale ? 'badge-gender-l' : 'badge-gender-p' }}">{{ $genderLabel }}</span><br>
                        <span class="label">NIS:</span> <span class="val" style="font-family: monospace;">{{ $s->nis ?? '-' }}</span><br>
                        @if(!empty($s->nisn))
                            <span class="label">NISN:</span> <span class="val" style="font-family: monospace;">{{ $s->nisn }}</span><br>
                        @endif
                        @if(!empty($s->nik))
                            <span class="label">NIK:</span> <span class="val" style="font-family: monospace;">{{ $s->nik }}</span>
                        @endif
                    </td>

                    <!-- Kelahiran & Usia -->
                    <td>
                        <span class="label">TTL:</span> <span class="val">{{ $s->birth_place ?? '-' }}, {{ $birthDateFormatted }}</span><br>
                        <span class="label">Usia:</span> <span class="val">{{ $s->age ?? '-' }}</span><br>
                        <span class="label">Agama:</span> <span class="val">{{ $s->religion ?? 'Islam' }}</span>
                    </td>

                    <!-- Penempatan & Kelompok -->
                    <td>
                        <span class="label">Jenjang:</span> <span class="val" style="color: #4338ca;">{{ $jName }}</span><br>
                        <span class="label">Kelas:</span> <span class="val">{{ $classLevelName }}</span><br>
                        <span class="label">Kelompok:</span> <span class="val">{{ $className }}</span><br>
                        @if($homeroomName !== '-')
                            <span class="label">Wali Kelas:</span> <span class="val">{{ $homeroomName }}</span><br>
                        @endif
                        @if($s->daycareClassroom)
                            <span class="label">Daycare:</span> <span class="val" style="color: #0284c7;">{{ $s->daycareClassroom->name }}</span><br>
                        @endif
                        @if($s->is_tpq || $s->tpqClassroom)
                            <span class="label">TPQ:</span> <span class="val" style="color: #059669;">{{ $s->tpqClassroom?->name ?? 'Ikut TPQ' }}</span>
                        @endif
                    </td>

                    <!-- Orang Tua / Kontak -->
                    <td>
                        <span class="label">Ayah:</span> <span class="val">{{ $s->father_name ?? '-' }}</span>
                        @if(!empty($s->father_phone))
                            <span style="font-size: 7pt; color: #475569; font-family: monospace;">({{ $s->father_phone }})</span>
                        @endif
                        <br>
                        <span class="label">Ibu:</span> <span class="val">{{ $s->mother_name ?? '-' }}</span>
                        @if(!empty($s->mother_phone))
                            <span style="font-size: 7pt; color: #475569; font-family: monospace;">({{ $s->mother_phone }})</span>
                        @endif
                        <br>
                        <span class="label">WA Ortu:</span> <strong style="color: #059669; font-family: monospace;">{{ $s->clean_parent_phone ?? $s->parent_phone ?? '-' }}</strong>
                    </td>

                    <!-- Domisili & Administrasi -->
                    <td>
                        <span class="label">Alamat:</span> <span style="font-size: 7pt;">{{ $s->address ?? '-' }}</span><br>
                        <span class="label">Kota:</span> <span class="val">{{ $s->city ?? 'Malang' }}</span><br>
                        <span class="label">PIN Wali:</span> <span class="val" style="font-family: monospace;">{{ $s->pin_access ?? '-' }}</span><br>
                        <span class="label">Status:</span> <span class="badge {{ $badgeClass }}">{{ strtoupper($status) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 18px; color: #94a3b8;">
                        Tidak ada data murid yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-summary">
        <table style="border: none; margin: 0; padding: 0;">
            <tr style="background: none;">
                <td style="border: none; padding: 0; font-size: 7pt; color: #64748b;">
                    Dicetak otomatis oleh Sistem Administrasi & Manajemen Akademik SANS PAUD Anak Saleh Malang.
                </td>
                <td style="border: none; padding: 0; text-align: right; font-size: 7pt; color: #64748b;">
                    Halaman 1 / 1
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
