<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $mainTitle ?? 'Daftar Data Lengkap Murid' }} - PAUD Islam Anak Saleh</title>
    <style>
        @page {
            margin: 7mm 7mm 8mm 7mm;
            size: a4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6.8pt;
            color: #1e293b;
            line-height: 1.35;
        }

        /* HEADER KOP */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }
        .kop-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .kop-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .kop-sub {
            font-size: 8pt;
            font-weight: bold;
            color: #4338ca;
            margin: 1px 0 0 0;
        }
        .kop-desc {
            font-size: 6.5pt;
            color: #64748b;
            margin: 1px 0 0 0;
        }

        /* META BANNER */
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 7px;
            font-size: 6.5pt;
        }
        .meta-box table {
            width: 100%;
            border: none;
            margin: 0;
        }
        .meta-box td {
            border: none;
            padding: 1px 4px;
            vertical-align: middle;
        }
        .meta-tag {
            color: #4338ca;
            font-weight: bold;
        }

        /* DATA TABLE */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 4px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            vertical-align: top;
            word-wrap: break-word;
            overflow: hidden;
        }
        table.data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 6.5pt;
            letter-spacing: 0.3px;
            text-align: center;
            padding: 5px 3px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.data-table tr {
            page-break-inside: avoid;
        }

        /* TEXT HELPERS */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .name-title {
            font-size: 7.8pt;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
        }
        .lbl {
            color: #64748b;
            font-size: 6.2pt;
            font-weight: normal;
        }
        .val {
            color: #0f172a;
            font-weight: bold;
        }
        .subval {
            color: #334155;
        }
        .mono {
            font-family: "Courier New", Courier, monospace;
            font-size: 6.2pt;
        }
        .section-header {
            font-weight: bold;
            color: #1e293b;
            font-size: 6.5pt;
            border-bottom: 1px dashed #cbd5e1;
            margin-top: 3px;
            padding-bottom: 1px;
            margin-bottom: 2px;
        }

        /* COMPACT BADGES */
        .pill {
            display: inline-block;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 5.8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .pill-gender-l { background-color: #e0e7ff; color: #3730a3; }
        .pill-gender-p { background-color: #ffe4e6; color: #9f1239; }
        .pill-aktif { background-color: #dcfce7; color: #166534; }
        .pill-lulus { background-color: #dbeafe; color: #1e40af; }
        .pill-mutasi { background-color: #fef3c7; color: #92400e; }
        .pill-other { background-color: #f1f5f9; color: #475569; }
        .pill-tag { background-color: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }

        /* FOOTER */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #cbd5e1;
            margin-top: 4px;
            padding-top: 2px;
            font-size: 6pt;
            color: #64748b;
        }
        .footer-table td {
            border: none;
            padding: 0;
        }
    </style>
</head>
<body>

    <!-- KOP DOKUMEN -->
    <table class="kop-table">
        <tr>
            @if(!empty($logoSrc))
                <td style="width: 52px; vertical-align: middle; padding-right: 8px;">
                    <img src="{{ $logoSrc }}" style="height: 44px; width: auto; max-width: 52px;" alt="Logo">
                </td>
            @endif
            <td style="vertical-align: middle;">
                <div class="kop-title">{{ $mainTitle ?? 'REKAPITULASI DATA INDUK PESERTA DIDIK (LENGKAP)' }}</div>
                <div class="kop-sub">{{ strtoupper($schoolName ?? 'PAUD TERPADU ANAK SALEH') }}</div>
                <div class="kop-desc">{{ $schoolAddress ?? 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang' }} &bull; Telp: {{ $schoolPhone ?? '-' }} &bull; Email: {{ $schoolEmail ?? '-' }}</div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                <div style="font-size: 6.5pt; color: #64748b;">Tanggal Cetak:</div>
                <div style="font-size: 7.5pt; font-weight: bold; color: #0f172a;">{{ date('d F Y, H:i') }} WIB</div>
                <div style="font-size: 6pt; color: #4338ca; font-weight: bold;">Dokumen Resmi Sekolah</div>
            </td>
        </tr>
    </table>

    <!-- META FILTER & STATS -->
    <div class="meta-box">
        <table>
            <tr>
                <td style="width: 25%;">
                    <span class="lbl">Tahun Ajaran:</span>
                    <span class="meta-tag">{{ $selectedYear ? $selectedYear->name : 'Semua Tahun Ajaran' }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="lbl">Jenjang:</span>
                    <span class="meta-tag">{{ $selectedJenjang ? $selectedJenjang->name : 'Semua Jenjang' }}</span>
                </td>
                <td style="width: 25%;">
                    @if(!empty($filterDetails))
                        @foreach($filterDetails as $k => $v)
                            <span class="lbl">{{ $k }}:</span> <span class="meta-tag">{{ $v }}</span> &bull;
                        @endforeach
                    @else
                        <span class="lbl">Filter:</span> <span class="meta-tag">Semua Data</span>
                    @endif
                </td>
                <td style="width: 25%; text-align: right;">
                    <span class="lbl">Total Murid:</span>
                    <strong style="color: #0f172a; font-size: 7.2pt;">{{ number_format($students->count()) }} Anak</strong>
                    <span style="color: #64748b;">(Putra: {{ $students->where('gender', 'L')->count() }}, Putri: {{ $students->where('gender', 'P')->count() }})</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- TABEL DATA LENGKAP -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 2.5%;">No</th>
                <th style="width: 21%;">Biodata & Identitas Ananda</th>
                <th style="width: 15.5%;">Akademik & Penempatan</th>
                <th style="width: 16%;">Domisili & Transportasi</th>
                <th style="width: 22%;">Data Orang Tua & Wali</th>
                <th style="width: 13.5%;">Fisik, Medis & Kebiasaan</th>
                <th style="width: 9.5%;">Status & Akun</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $s)
                @php
                    $gender = strtoupper($s->gender ?? 'L');
                    $isMale = in_array($gender, ['L', 'LAKI-LAKI', 'MALE']);
                    $birthDateFormatted = $s->birth_date ? date('d/m/Y', strtotime($s->birth_date)) : '-';
                    $enrolledDateFormatted = $s->enrolled_date ? date('d/m/Y', strtotime($s->enrolled_date)) : null;

                    // Jenjang names
                    $jObj = $s->jenjang ?? $s->classroom?->jenjang ?? $s->classLevel?->jenjang;
                    $jCode = $jObj?->code ?? $s->sub_unit;
                    $jName = $jObj?->name ?? ($jCode ? strtoupper($jCode) : '-');

                    $additionalJenjang = [];
                    if ($s->daycare_classroom_id && $jCode !== 'DAYCARE' && $jCode !== 'TPA') {
                        $additionalJenjang[] = 'Daycare (TPA)';
                    }
                    if ($s->is_tpq && $jCode !== 'TPQ') {
                        $additionalJenjang[] = 'TPQ';
                    }

                    // Classroom names
                    $primaryGroup = $s->classroom?->name;
                    if (!$primaryGroup && $s->daycareClassroom) {
                        $primaryGroup = $s->daycareClassroom->name;
                    }
                    $classLevelName = $s->classroom?->classLevel?->name ?? $s->classLevel?->name ?? '-';
                    $homeroomName = $s->classroom?->homeroomTeacher?->name ?? '-';

                    $additionalGroups = [];
                    if ($s->daycareClassroom && $s->daycareClassroom->name !== $primaryGroup) {
                        $additionalGroups[] = $s->daycareClassroom->name;
                    }
                    if ($s->is_tpq || $s->tpq_classroom_id) {
                        $tpqGroupName = $s->tpqClassroom?->name ?? 'TPQ';
                        if ($tpqGroupName !== $primaryGroup && !in_array($tpqGroupName, $additionalGroups)) {
                            $additionalGroups[] = $tpqGroupName;
                        }
                    }

                    $spmbNumber = $s->spmbCandidate?->registration_number ?? ($s->spmb_candidate_id ? '#' . $s->spmb_candidate_id : null);

                    // JSON Data
                    $fd = is_array($s->family_data) ? $s->family_data : [];
                    $hh = is_array($s->health_habits) ? $s->health_habits : [];
                    $dp = is_array($s->development_psychology) ? $s->development_psychology : [];

                    // Father
                    $fatherPrefix = $s->father_title_prefix ?? ($fd['father']['title_prefix'] ?? '');
                    $fatherName = $s->father_name ?? ($fd['father']['name'] ?? '-');
                    $fatherSuffix = $s->father_title_suffix ?? ($fd['father']['title'] ?? '');
                    $fullFather = trim("{$fatherPrefix} {$fatherName} {$fatherSuffix}");
                    $fatherNik = $s->father_nik ?? ($fd['father']['nik'] ?? null);
                    $fatherJob = $s->father_job ?? ($fd['father']['job'] ?? null);
                    $fatherEdu = $s->father_education ?? ($fd['father']['education'] ?? null);
                    $fatherCompany = $s->father_company ?? ($fd['father']['company'] ?? ($fd['father']['workplace'] ?? null));
                    $fatherPhone = $s->father_phone ?? ($fd['father']['phone'] ?? null);

                    // Mother
                    $motherPrefix = $s->mother_title_prefix ?? ($fd['mother']['title_prefix'] ?? '');
                    $motherName = $s->mother_name ?? ($fd['mother']['name'] ?? '-');
                    $motherSuffix = $s->mother_title_suffix ?? ($fd['mother']['title'] ?? '');
                    $fullMother = trim("{$motherPrefix} {$motherName} {$motherSuffix}");
                    $motherNik = $s->mother_nik ?? ($fd['mother']['nik'] ?? null);
                    $motherJob = $s->mother_job ?? ($fd['mother']['job'] ?? null);
                    $motherEdu = $s->mother_education ?? ($fd['mother']['education'] ?? null);
                    $motherCompany = $s->mother_company ?? ($fd['mother']['company'] ?? ($fd['mother']['workplace'] ?? null));
                    $motherPhone = $s->mother_phone ?? ($fd['mother']['phone'] ?? null);

                    // Guardian
                    $guardianName = $s->guardian_name ?? ($fd['guardian']['name'] ?? null);
                    $guardianRelation = $s->guardian_relation ?? ($fd['guardian']['relation'] ?? ($guardianName ? 'Wali' : null));
                    $guardianPhone = $s->guardian_phone ?? ($fd['guardian']['phone'] ?? null);

                    // Main contact
                    $mainPhone = $s->clean_parent_phone ?? $s->parent_phone ?? ($fatherPhone ?: $motherPhone ?: null);
                    $emergencyPhone = $s->emergency_contact_phone ?? null;
                    $emergencyName = $s->emergency_contact_name ?? null;

                    // Physical & Medical
                    $foodAllergy = $hh['medical_history']['food_allergy'] ?? ($hh['allergy']['food'] ?? null);
                    $drinkAllergy = $hh['medical_history']['drink_allergy'] ?? ($hh['allergy']['drink'] ?? null);
                    $allergies = array_filter([$foodAllergy, $drinkAllergy], fn($v) => !empty($v) && $v !== '-' && strtolower($v) !== 'tidak ada');

                    $severeIllness = $hh['medical_history']['severe_diseases'] ?? ($hh['medical']['seizure_history'] ?? null);
                    $frequentIllness = $hh['medical_history']['frequent_diseases'] ?? ($hh['medical']['asthma_history'] ?? null);

                    $status = strtolower($s->status ?? 'aktif');
                    $badgeClass = match($status) {
                        'aktif' => 'pill-aktif',
                        'lulus' => 'pill-lulus',
                        'mutasi' => 'pill-mutasi',
                        default => 'pill-other'
                    };
                @endphp
                <tr>
                    <!-- 1. NO -->
                    <td class="text-center val" style="font-size: 7.2pt;">{{ $index + 1 }}</td>

                    <!-- 2. BIODATA & IDENTITAS ANANDA -->
                    <td>
                        <div class="name-title">{{ $s->full_name }}</div>
                        <div style="margin-top: 1px; margin-bottom: 2px;">
                            <span class="pill {{ $isMale ? 'pill-gender-l' : 'pill-gender-p' }}">{{ $isMale ? 'Putra (L)' : 'Putri (P)' }}</span>
                            @if(!empty($s->nickname))
                                <span style="color: #64748b; font-style: italic;">"{{ $s->nickname }}"</span>
                            @endif
                            <span class="lbl" style="margin-left: 3px;">Usia:</span> <span class="val">{{ $s->age ?? '-' }}</span>
                        </div>
                        <div><span class="lbl">TTL:</span> <span class="subval">{{ $s->birth_place ?? '-' }}, {{ $birthDateFormatted }}</span></div>
                        <div><span class="lbl">Agama:</span> <span class="subval">{{ $s->religion ?? 'Islam' }}</span> &bull; <span class="lbl">Warga:</span> <span class="subval">{{ $s->citizenship ?? 'WNI' }}</span></div>
                        <div style="margin-top: 2px; padding-top: 2px; border-top: 1px dotted #e2e8f0;">
                            <span class="lbl">NIS:</span> <strong class="mono" style="color: #4338ca;">{{ $s->nis ?? '-' }}</strong>
                            @if(!empty($s->nisn)) | <span class="lbl">NISN:</span> <span class="mono">{{ $s->nisn }}</span> @endif
                        </div>
                        <div><span class="lbl">NIK:</span> <span class="mono">{{ $s->nik ?? '-' }}</span></div>
                        @if(!empty($s->no_kk))
                            <div><span class="lbl">No. KK:</span> <span class="mono">{{ $s->no_kk }}</span></div>
                        @endif
                        @if(!empty($s->no_akta))
                            <div><span class="lbl">Akta:</span> <span class="mono">{{ $s->no_akta }}</span></div>
                        @endif
                        @if(!empty($s->previous_school))
                            <div><span class="lbl">Asal Sekolah:</span> <span class="subval">{{ $s->previous_school }}</span></div>
                        @endif
                    </td>

                    <!-- 3. AKADEMIK & PENEMPATAN KELAS -->
                    <td>
                        <div><span class="lbl">Tahun Ajaran:</span> <strong style="color: #4338ca;">{{ $s->academicYear?->name ?? '-' }}</strong></div>
                        <div>
                            <span class="lbl">Jenjang:</span> <strong class="val">{{ $jName }}</strong>
                            @if(!empty($additionalJenjang))
                                <span style="color: #0284c7; font-size: 6pt;">, {{ implode(', ', $additionalJenjang) }}</span>
                            @endif
                        </div>
                        <div><span class="lbl">Tingkat Kelas:</span> <span class="val">{{ $classLevelName }}</span></div>
                        <div style="margin-top: 2px; padding-top: 2px; border-top: 1px dotted #e2e8f0;">
                            <span class="lbl">Kelompok:</span> <strong style="color: #0f172a; font-size: 7.2pt;">{{ $primaryGroup ?? '-' }}</strong>
                            @if(!empty($additionalGroups))
                                <span style="color: #475569; font-size: 6pt;">, {{ implode(', ', $additionalGroups) }}</span>
                            @endif
                        </div>
                        @if($homeroomName !== '-')
                            <div><span class="lbl">Wali Kelas:</span> <span class="subval">{{ $homeroomName }}</span></div>
                        @endif
                        @if($spmbNumber)
                            <div><span class="lbl">No. SPMB:</span> <span class="mono">{{ $spmbNumber }}</span></div>
                        @endif
                        @if($enrolledDateFormatted)
                            <div><span class="lbl">Tgl Masuk:</span> <span class="subval">{{ $enrolledDateFormatted }}</span></div>
                        @endif
                    </td>

                    <!-- 4. DOMISILI & AKSES TRANSPORTASI -->
                    <td>
                        <div><span class="lbl">Alamat:</span> <span class="subval font-bold">{{ $s->address ?? '-' }}</span></div>
                        @if($s->rt || $s->rw || $s->dusun)
                            <div><span class="lbl">RT/RW/Dusun:</span> <span class="subval">RT {{ $s->rt ?? '-' }} / RW {{ $s->rw ?? '-' }} {{ $s->dusun ? '('.$s->dusun.')' : '' }}</span></div>
                        @endif
                        @if($s->kelurahan || $s->kecamatan)
                            <div><span class="lbl">Kel/Kec:</span> <span class="subval">{{ $s->kelurahan ?? '-' }}, {{ $s->kecamatan ?? '-' }}</span></div>
                        @endif
                        <div><span class="lbl">Kota/Kab:</span> <span class="subval">{{ $s->city ?? 'Malang' }} {{ $s->postal_code ? '('.$s->postal_code.')' : '' }}</span></div>
                        <div style="margin-top: 2px; padding-top: 2px; border-top: 1px dotted #e2e8f0;">
                            <span class="lbl">Tempat Tinggal:</span> <span class="subval">{{ $s->living_with ?? 'Bersama orang tua' }}</span>
                        </div>
                        @if($s->transportation)
                            <div><span class="lbl">Transportasi:</span> <span class="subval">{{ $s->transportation }}</span></div>
                        @endif
                        @if($s->distance_to_school || $s->travel_time_minutes)
                            <div><span class="lbl">Jarak / Waktu:</span> <span class="subval">{{ $s->distance_to_school ?? '-' }} / {{ $s->travel_time_minutes ? $s->travel_time_minutes.' mnt' : '-' }}</span></div>
                        @endif
                    </td>

                    <!-- 5. DATA ORANG TUA & KONTAK WALI -->
                    <td>
                        <div>
                            <span class="lbl">Ayah:</span> <strong style="color: #0f172a;">{{ $fullFather ?: '-' }}</strong>
                            @if($fatherNik) <span class="mono" style="color: #64748b; font-size: 5.8pt;">({{ $fatherNik }})</span> @endif
                        </div>
                        @if($fatherJob || $fatherEdu || $fatherCompany)
                            <div style="color: #475569; font-size: 6.2pt;">
                                {{ implode(' &bull; ', array_filter([$fatherJob, $fatherEdu, $fatherCompany])) }}
                            </div>
                        @endif
                        @if($fatherPhone)
                            <div><span class="lbl">Telp Ayah:</span> <span class="mono">{{ $fatherPhone }}</span></div>
                        @endif

                        <div style="margin-top: 2px; padding-top: 2px; border-top: 1px dotted #e2e8f0;">
                            <span class="lbl">Ibu:</span> <strong style="color: #0f172a;">{{ $fullMother ?: '-' }}</strong>
                            @if($motherNik) <span class="mono" style="color: #64748b; font-size: 5.8pt;">({{ $motherNik }})</span> @endif
                        </div>
                        @if($motherJob || $motherEdu || $motherCompany)
                            <div style="color: #475569; font-size: 6.2pt;">
                                {{ implode(' &bull; ', array_filter([$motherJob, $motherEdu, $motherCompany])) }}
                            </div>
                        @endif
                        @if($motherPhone)
                            <div><span class="lbl">Telp Ibu:</span> <span class="mono">{{ $motherPhone }}</span></div>
                        @endif

                        @if($guardianName)
                            <div style="margin-top: 2px; padding-top: 2px; border-top: 1px dotted #e2e8f0;">
                                <span class="lbl">Wali:</span> <strong>{{ $guardianName }}</strong>
                                @if($guardianRelation) <span>({{ $guardianRelation }})</span> @endif
                                @if($guardianPhone) <span class="mono">Telp: {{ $guardianPhone }}</span> @endif
                            </div>
                        @endif

                        <div style="margin-top: 2px; padding-top: 2px; border-top: 1px solid #e2e8f0;">
                            <span class="lbl">WA Utama:</span>
                            <strong style="color: #059669; font-family: monospace; font-size: 7pt;">{{ $mainPhone ?? '-' }}</strong>
                            @if($s->parent_email)
                                <br><span class="lbl">Email:</span> <span style="color: #64748b; font-size: 5.8pt;">{{ $s->parent_email }}</span>
                            @endif
                        </div>
                        @if($emergencyName || $emergencyPhone)
                            <div style="color: #b45309; font-size: 6pt;">
                                <span class="lbl">Darurat:</span> {{ $emergencyName ?? 'Kontak' }} ({{ $emergencyPhone ?? '-' }})
                            </div>
                        @endif
                    </td>

                    <!-- 6. FISIK, MEDIS & KEBIASAAN -->
                    <td>
                        <div>
                            <span class="lbl">Anak Ke-:</span> <strong class="val">{{ $s->child_order ?? '-' }}</strong> dari <span class="val">{{ $s->siblings_count ?? '-' }}</span> sdr
                            &bull; <span class="lbl">Goldar:</span> <strong style="color: #b91c1c;">{{ $s->blood_type ?? '-' }}</strong>
                        </div>
                        <div>
                            <span class="lbl">TB / BB:</span> <span class="val">{{ $s->height ? $s->height.'cm' : '-' }}</span> / <span class="val">{{ $s->weight ? $s->weight.'kg' : '-' }}</span>
                        </div>
                        <div>
                            <span class="lbl">LK / LiLa:</span> <span class="subval">{{ $s->head_circumference ? $s->head_circumference.'cm' : '-' }}</span> / <span class="subval">{{ $s->arm_circumference ? $s->arm_circumference.'cm' : '-' }}</span>
                        </div>
                        @if($s->special_needs && strtolower($s->special_needs) !== 'tidak ada')
                            <div style="margin-top: 1px; color: #b91c1c; font-weight: bold; font-size: 6.2pt;">
                                ABK: {{ $s->special_needs }} {{ $s->special_needs_detail ? '('.$s->special_needs_detail.')' : '' }}
                            </div>
                        @endif
                        @if(!empty($allergies))
                            <div style="margin-top: 1px; color: #b45309; font-size: 6pt;">
                                <span class="lbl">Alergi:</span> {{ implode(', ', $allergies) }}
                            </div>
                        @endif
                        @if(!empty($severeIllness) && $severeIllness !== '-' && strtolower($severeIllness) !== 'tidak ada')
                            <div style="color: #b91c1c; font-size: 6pt;">
                                <span class="lbl">Riw. Sakit:</span> {{ $severeIllness }}
                            </div>
                        @endif
                        @if(!empty($hh['toilet_training']['uses_diapers']) && strtolower($hh['toilet_training']['uses_diapers']) !== 'tidak')
                            <div style="color: #64748b; font-size: 6pt;">
                                <span class="lbl">Diapers:</span> {{ $hh['toilet_training']['uses_diapers'] }}
                            </div>
                        @endif
                    </td>

                    <!-- 7. STATUS & AKUN -->
                    <td class="text-center">
                        <div style="margin-bottom: 3px;">
                            <span class="pill {{ $badgeClass }}">{{ strtoupper($status) }}</span>
                        </div>
                        <div style="margin-top: 2px;">
                            <span class="lbl">PIN Wali:</span>
                            <div class="mono" style="color: #4338ca; font-weight: bold; font-size: 7.5pt;">{{ $s->pin_access ?? '-' }}</div>
                        </div>
                        @if($s->student_aspiration)
                            <div style="margin-top: 3px; font-size: 5.8pt; color: #475569;" class="text-left">
                                <span class="lbl">Cita-cita:</span> {{ $s->student_aspiration }}
                            </div>
                        @endif
                        @if($s->student_hobby)
                            <div style="font-size: 5.8pt; color: #475569;" class="text-left">
                                <span class="lbl">Hobi:</span> {{ $s->student_hobby }}
                            </div>
                        @endif
                        @if(!empty($s->notes) && $s->notes !== '-')
                            <div style="margin-top: 2px; font-size: 5.8pt; color: #64748b; font-style: italic;" class="text-left">
                                {{ Str::limit($s->notes, 60) }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8; font-size: 8pt;">
                        Tidak ada data murid yang ditemukan untuk kriteria filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- PENGESAHAN KEPALA SEKOLAH -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 6px; page-break-inside: avoid;">
        <tr>
            <td style="width: 65%; border: none; vertical-align: top; padding: 0;">
                <div style="font-size: 6.2pt; color: #64748b; line-height: 1.45;">
                    <strong>Keterangan Dokumen Resmi:</strong><br>
                    &bull; Dokumen rekapitulasi data induk peserta didik ini diterbitkan resmi oleh sistem administrasi akademik sekolah.<br>
                    &bull; Data diperbarui secara berkala dan telah diverifikasi sesuai ketentuan kurikulum dan kelembagaan sekolah.
                </div>
            </td>
            <td style="width: 35%; border: none; text-align: center; vertical-align: top; padding: 0;">
                <div style="font-size: 6.8pt; color: #334155;">
                    Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                </div>
                <div style="font-size: 7.2pt; font-weight: bold; color: #0f172a; margin-top: 2px;">
                    Mengetahui,<br>Kepala Sekolah {{ $schoolName ?? 'PAUD Terpadu Anak Saleh' }}
                </div>
                <div style="height: 38px;"></div>
                <div style="font-size: 8pt; font-weight: bold; color: #0f172a; text-decoration: underline;">
                    {{ $principalName ?? 'Dra. Hj. Mike Supraptiwi, S.Psi, M.Pd.' }}
                </div>
                <div style="font-size: 6.8pt; color: #475569; margin-top: 1px;">
                    NIY: {{ $principalNiy ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- FOOTER KETERANGAN -->
    <table class="footer-table">
        <tr>
            <td style="width: 70%;">
                Dicetak otomatis dari Sistem {{ setting('app_name', 'SAANS PAUD') }} &bull; {{ $schoolName ?? 'PAUD Terpadu Anak Saleh' }} &bull; Dokumen resmi sekolah.
            </td>
            <td style="width: 30%; text-align: right;">
                Format A4 Landscape &bull; Sah &amp; Resmi
            </td>
        </tr>
    </table>

    <!-- SCRIPT NOMOR HALAMAN DOMPDF -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $size = 6.5;
            $font = $fontMetrics->getFont("Arial, Helvetica, sans-serif");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pdf->get_width() - $width - 20;
            $y = $pdf->get_height() - 16;
            $pdf->page_text($x, $y, $text, $font, $size, [0.35, 0.4, 0.48]);
        }
    </script>

</body>
</html>
