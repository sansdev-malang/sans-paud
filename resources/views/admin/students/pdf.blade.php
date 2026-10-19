<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $mainTitle ?? 'Daftar Lengkap Data Murid' }} - PAUD Anak Saleh</title>
    <style>
        @page {
            margin: 8mm 7mm 8mm 7mm;
            size: a4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            color: #1e293b;
            line-height: 1.3;
        }
        .header-container {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .subtitle {
            text-align: center;
            font-size: 8.5pt;
            color: #475569;
            margin: 2px 0 0 0;
            font-weight: 600;
        }
        .meta-info {
            display: table;
            width: 100%;
            margin-top: 5px;
            font-size: 7pt;
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
            margin-bottom: 6px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow: hidden;
        }
        th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 6.8pt;
            letter-spacing: 0.3px;
            text-align: center;
        }
        tr {
            page-break-inside: avoid;
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
            font-size: 6.5pt;
        }
        .val {
            font-weight: bold;
            color: #0f172a;
        }
        .mono {
            font-family: "Courier New", Courier, monospace;
            font-size: 6.5pt;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6pt;
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
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 6pt;
        }
        .badge-gender-p {
            background-color: #ffe4e6;
            color: #be123c;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 6pt;
        }
        .footer-summary {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            font-size: 6.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <h1 class="title">{{ $mainTitle ?? 'Daftar Data Lengkap Murid (Peserta Didik)' }}</h1>
        <p class="subtitle">KB - TK - DAYCARE - TPQ ISLAM ANAK SALEH MALANG</p>
        <div class="meta-info">
            <div class="meta-cell meta-left">
                <span>Tahun Ajaran: <strong class="meta-tag">{{ $selectedYear ? $selectedYear->name : 'Semua T.A.' }}</strong></span>
                <span style="margin-left: 10px;">Jenjang: <strong class="meta-tag">{{ $selectedJenjang ? $selectedJenjang->name : 'Semua Jenjang' }}</strong></span>
                @if(!empty($filterDetails))
                    @foreach($filterDetails as $k => $v)
                        <span style="margin-left: 10px;">{{ $k }}: <strong class="meta-tag">{{ $v }}</strong></span>
                    @endforeach
                @endif
                <span style="margin-left: 10px;">Total Murid: <strong class="meta-tag">{{ number_format($students->count()) }} Orang</strong> (L: {{ $students->where('gender', 'L')->count() }}, P: {{ $students->where('gender', 'P')->count() }})</span>
            </div>
            <div class="meta-cell meta-right">
                <span>Tanggal Unduh: <strong>{{ date('d/m/Y H:i') }} WIB</strong></span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="2.5%">No</th>
                <th width="19%">Identitas & Dokumen Resmi</th>
                <th width="16%">Akademik & Penempatan</th>
                <th width="16%">Domisili & Transportasi</th>
                <th width="20.5%">Data Orang Tua & Wali</th>
                <th width="14%">Fisik & Kesehatan</th>
                <th width="12%">Status & Akun</th>
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
                    $daycareName = $s->daycareClassroom?->name ?? '-';
                    $tpqStatus = ($s->is_tpq || $s->tpq_classroom_id || $s->sub_unit === 'TPQ') ? 'Ya' : 'Tidak';
                    $tpqName = $s->tpqClassroom?->name ?? ($tpqStatus === 'Ya' ? 'TPQ' : '-');
                    $spmbNumber = $s->spmbCandidate?->registration_number ?? ($s->spmb_candidate_id ? '#' . $s->spmb_candidate_id : null);

                    $fd = is_array($s->family_data) ? $s->family_data : [];
                    $fatherName = $s->father_name ?? ($fd['father']['name'] ?? '-');
                    $fatherNik = $fd['father']['nik'] ?? null;
                    $fatherEdu = $fd['father']['education'] ?? null;
                    $fatherJob = $s->father_job ?? ($fd['father']['job'] ?? null);
                    $fatherPhone = $s->father_phone ?? ($fd['father']['phone'] ?? null);

                    $motherName = $s->mother_name ?? ($fd['mother']['name'] ?? '-');
                    $motherNik = $fd['mother']['nik'] ?? null;
                    $motherEdu = $fd['mother']['education'] ?? null;
                    $motherJob = $s->mother_job ?? ($fd['mother']['job'] ?? null);
                    $motherPhone = $s->mother_phone ?? ($fd['mother']['phone'] ?? null);

                    $guardianName = $s->guardian_name ?? ($fd['guardian']['name'] ?? null);
                    $guardianRelation = $fd['guardian']['relation'] ?? ($s->guardian_name ? 'Wali' : null);
                    $guardianPhone = $s->guardian_phone ?? ($fd['guardian']['phone'] ?? null);

                    $hh = is_array($s->health_habits) ? $s->health_habits : [];
                    $allergy = ($hh['allergy']['has_allergies'] ?? false) ? ($hh['allergy']['types'] ?? 'Ada Alergi') : 'Tidak ada';
                    $illness = !empty($hh['illness_history']) ? $hh['illness_history'] : '';
                    $healthNotes = trim(($allergy !== 'Tidak ada' ? "Alergi: {$allergy}. " : '') . ($illness ? "Penyakit: {$illness}" : ''));

                    $habits = [];
                    if (!empty($hh['diet']['eating_morning']) || !empty($hh['diet']['eating_afternoon'])) {
                        $habits[] = 'Makan: ' . ($hh['diet']['eating_morning'] ?? '-') . '/' . ($hh['diet']['eating_afternoon'] ?? '-');
                    }
                    if (!empty($hh['habits']['toilet_training'])) {
                        $habits[] = 'Toilet: ' . $hh['habits']['toilet_training'];
                    }
                    $habitsStr = !empty($habits) ? implode('; ', $habits) : null;

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
                    
                    <!-- 1. Identitas & Dokumen Resmi Ananda -->
                    <td>
                        <span class="val" style="font-size: 7.5pt; color: #0f172a;">{{ $s->full_name }}</span>
                        @if(!empty($s->nickname))
                            <span style="color: #64748b;">({{ $s->nickname }})</span>
                        @endif
                        <br>
                        <span class="{{ $isMale ? 'badge-gender-l' : 'badge-gender-p' }}">{{ $genderLabel }}</span>
                        <span class="label" style="margin-left: 4px;">Usia:</span> <span class="val">{{ $s->age ?? '-' }}</span>
                        <br>
                        <span class="label">TTL:</span> <span>{{ $s->birth_place ?? '-' }}, {{ $birthDateFormatted }}</span>
                        <br>
                        <span class="label">Agama:</span> <span>{{ $s->religion ?? 'Islam' }}</span> | <span class="label">Warga:</span> <span>{{ $s->citizenship ?? 'WNI' }}</span>
                        <br>
                        <span class="label">NIS:</span> <span class="mono val">{{ $s->nis ?? '-' }}</span>
                        @if(!empty($s->nisn))
                            | <span class="label">NISN:</span> <span class="mono">{{ $s->nisn }}</span>
                        @endif
                        <br>
                        <span class="label">NIK:</span> <span class="mono">{{ $s->nik ?? '-' }}</span>
                        @if(!empty($s->no_kk))
                            <br><span class="label">No. KK:</span> <span class="mono">{{ $s->no_kk }}</span>
                        @endif
                        @if(!empty($s->no_akta))
                            | <span class="label">Akta:</span> <span class="mono">{{ $s->no_akta }}</span>
                        @endif
                        @if(!empty($s->previous_school))
                            <br><span class="label">Asal Sekolah:</span> <span>{{ $s->previous_school }}</span>
                        @endif
                    </td>

                    <!-- 2. Akademik & Penempatan -->
                    <td>
                        <span class="label">T.A.:</span> <strong style="color: #4338ca;">{{ $s->academicYear?->name ?? ($selectedYear?->name ?? '-') }}</strong>
                        <br>
                        <span class="label">Sub Unit:</span> <strong>{{ $s->sub_unit ?? 'TK' }}</strong> ({{ $jName }})
                        <br>
                        <span class="label">Kelas:</span> <span>{{ $classLevelName }}</span>
                        <br>
                        <span class="label">Kelompok:</span> <strong style="color: #4f46e5;">{{ $className }}</strong>
                        @if($homeroomName !== '-')
                            <br><span class="label">Wali:</span> <span>{{ $homeroomName }}</span>
                        @endif
                        @if($s->daycareClassroom)
                            <br><span class="label">Daycare:</span> <span style="color: #0284c7; font-weight: bold;">{{ $s->daycareClassroom->name }}</span>
                        @endif
                        @if($s->is_tpq || $s->tpqClassroom)
                            <br><span class="label">TPQ:</span> <span style="color: #059669; font-weight: bold;">{{ $s->tpqClassroom?->name ?? 'Ikut TPQ' }}</span>
                        @endif
                        @if($s->enrolled_date)
                            <br><span class="label">Tgl Masuk:</span> <span>{{ date('d/m/Y', strtotime($s->enrolled_date)) }}</span>
                        @endif
                        @if($spmbNumber)
                            <br><span class="label">No. SPMB:</span> <span class="mono">{{ $spmbNumber }}</span>
                        @endif
                    </td>

                    <!-- 3. Domisili & Transportasi -->
                    <td>
                        <span class="label">Alamat:</span> <span>{{ $s->address ?? '-' }}</span>
                        @if($s->rt || $s->rw)
                            <span>(RT {{ $s->rt ?? '-' }}/RW {{ $s->rw ?? '-' }})</span>
                        @endif
                        @if($s->kelurahan || $s->kecamatan)
                            <br><span class="label">Kel/Kec:</span> <span>{{ $s->kelurahan ?? '-' }}, {{ $s->kecamatan ?? '-' }}</span>
                        @endif
                        <br><span class="label">Kota/Prov:</span> <span>{{ $s->city ?? 'Malang' }}, {{ $s->province ?? 'Jawa Timur' }} {{ $s->postal_code ? '('.$s->postal_code.')' : '' }}</span>
                        <br><span class="label">Tinggal:</span> <span>{{ $s->living_with ?? 'Bersama orang tua' }}</span>
                        @if($s->transportation)
                            <br><span class="label">Transport:</span> <span>{{ $s->transportation }}</span>
                        @endif
                        @if($s->distance_to_school || $s->travel_time_minutes)
                            <br><span class="label">Jarak/Waktu:</span> <span>{{ $s->distance_to_school ?? '-' }} / {{ $s->travel_time_minutes ? $s->travel_time_minutes.' menit' : '-' }}</span>
                        @endif
                    </td>

                    <!-- 4. Data Orang Tua & Wali -->
                    <td>
                        <span class="label">Ayah:</span> <strong>{{ $fatherName }}</strong>
                        @if(!empty($fatherNik))
                            <span class="mono" style="font-size: 6pt; color: #64748b;">({{ $fatherNik }})</span>
                        @endif
                        @if(!empty($fatherJob))
                            <br><span class="label">Kerja:</span> <span>{{ $fatherJob }}</span>
                        @endif
                        @if(!empty($fatherEdu))
                            | <span class="label">Pddk:</span> <span>{{ $fatherEdu }}</span>
                        @endif
                        @if(!empty($fatherPhone))
                            | <span class="mono" style="font-size: 6pt;">{{ $fatherPhone }}</span>
                        @endif
                        <br>
                        <span class="label">Ibu:</span> <strong>{{ $motherName }}</strong>
                        @if(!empty($motherNik))
                            <span class="mono" style="font-size: 6pt; color: #64748b;">({{ $motherNik }})</span>
                        @endif
                        @if(!empty($motherJob))
                            <br><span class="label">Kerja:</span> <span>{{ $motherJob }}</span>
                        @endif
                        @if(!empty($motherEdu))
                            | <span class="label">Pddk:</span> <span>{{ $motherEdu }}</span>
                        @endif
                        @if(!empty($motherPhone))
                            | <span class="mono" style="font-size: 6pt;">{{ $motherPhone }}</span>
                        @endif
                        @if(!empty($guardianName))
                            <br><span class="label">Wali:</span> <strong>{{ $guardianName }}</strong>
                            @if(!empty($guardianRelation)) <span>({{ $guardianRelation }})</span> @endif
                            @if(!empty($guardianPhone)) <span class="mono" style="font-size: 6pt;">{{ $guardianPhone }}</span> @endif
                        @endif
                        <br>
                        <span class="label">WA Kontak:</span> <strong style="color: #059669; font-family: monospace;">{{ $s->clean_parent_phone ?? $s->parent_phone ?? '-' }}</strong>
                        @if(!empty($s->parent_email))
                            <br><span class="label">Email:</span> <span style="font-size: 6pt; color: #64748b;">{{ $s->parent_email }}</span>
                        @endif
                    </td>

                    <!-- 5. Fisik & Kesehatan -->
                    <td>
                        <span class="label">Anak Ke-:</span> <strong>{{ $s->child_order ?? '-' }}</strong> dari <strong>{{ $s->siblings_count ?? '-' }}</strong> sdr
                        <br>
                        <span class="label">Gol. Darah:</span> <strong>{{ $s->blood_type ?? '-' }}</strong>
                        @if($s->special_needs && strtolower($s->special_needs) !== 'tidak ada')
                            <br><span class="label">ABK:</span> <span style="color: #dc2626; font-weight: bold;">{{ $s->special_needs }}</span>
                        @endif
                        <br>
                        <span class="label">TB / BB:</span> <span>{{ $s->height ? $s->height.' cm' : '-' }} / {{ $s->weight ? $s->weight.' kg' : '-' }}</span>
                        <br>
                        <span class="label">LK / LiLa:</span> <span>{{ $s->head_circumference ? $s->head_circumference.' cm' : '-' }} / {{ $s->arm_circumference ? $s->arm_circumference.' cm' : '-' }}</span>
                        @if(!empty($healthNotes))
                            <br><span class="label">Kesehatan:</span> <span style="color: #b45309; font-size: 6pt;">{{ $healthNotes }}</span>
                        @endif
                        @if(!empty($habitsStr))
                            <br><span class="label">Kebiasaan:</span> <span style="font-size: 6pt; color: #475569;">{{ $habitsStr }}</span>
                        @endif
                    </td>

                    <!-- 6. Status & Akun -->
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ strtoupper($status) }}</span>
                        <br>
                        <span class="label">PIN Wali:</span> <strong class="mono" style="color: #4f46e5;">{{ $s->pin_access ?? '-' }}</strong>
                        @if(!empty($s->notes))
                            <br><span class="label">Catatan:</span> <span style="font-size: 6pt; color: #64748b;">{{ $s->notes }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 16px; color: #94a3b8;">
                        Tidak ada data murid yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-summary">
        <table style="border: none; margin: 0; padding: 0;">
            <tr style="background: none;">
                <td style="border: none; padding: 0; font-size: 6.5pt; color: #64748b;">
                    Dicetak otomatis oleh Sistem Administrasi & Manajemen Akademik SAANS PAUD Islam Anak Saleh Malang. Menampilkan seluruh data atribut komprehensif murid.
                </td>
                <td style="border: none; padding: 0; text-align: right; font-size: 6.5pt; color: #64748b;">
                    Halaman Data Lengkap Murid (A4 Landscape)
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
