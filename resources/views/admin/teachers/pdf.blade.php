<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Data Guru & Tenaga Pendidik</title>
    <style>
        @page {
            margin: 10mm 10mm 12mm 10mm;
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
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .school-name {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0;
        }
        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 2px 0 0 0;
            letter-spacing: 0.5px;
        }
        .meta-strip {
            display: table;
            width: 100%;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px dashed #cbd5e1;
            font-size: 7.5pt;
            color: #475569;
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
            color: #334155;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            letter-spacing: 0.3px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
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
            border-radius: 3px;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-active {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-leave {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-inactive {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .badge-classroom {
            background-color: #e0e7ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
            margin-bottom: 2px;
        }

        /* Signature block */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .footer-note {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- KOP & HEADER -->
    <div class="header-container">
        <div class="school-name">{{ setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG')) }}</div>
        <div class="title">Daftar Data Guru & Tenaga Pendidik</div>
        
        <div class="meta-strip">
            <div class="meta-cell meta-left">
                <span>Filter Jenjang: <span class="meta-tag">{{ $activeSubUnitLabel }}</span></span>
                &nbsp;|&nbsp;
                <span>Kelompok: <span class="meta-tag">{{ $activeClassroomLabel }}</span></span>
                &nbsp;|&nbsp;
                <span>Status: <span class="meta-tag">{{ $filterStatus ? ($filterStatus == 'Active' ? 'Aktif' : ($filterStatus == 'Leave' ? 'Cuti' : 'Nonaktif')) : 'Semua Status' }}</span></span>
            </div>
            <div class="meta-cell meta-right">
                <span>Total Pendidik: <span class="meta-tag">{{ $teachers->count() }} Orang</span></span>
                &nbsp;|&nbsp;
                <span>Dicetak: <span class="meta-tag">{{ date('d/m/Y H:i') }} WIB</span></span>
            </div>
        </div>
    </div>

    <!-- TABEL DATA GURU -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="22%">Guru / Pendidik</th>
                <th width="17%">Identitas Resmi</th>
                <th width="22%">Penugasan & Kelompok</th>
                <th width="18%">Akademik & Kepegawaian</th>
                <th width="18%">Kontak & Administrasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $index => $t)
                @php
                    $fullName = ($t->front_title ? $t->front_title . ' ' : '') . $t->name . ($t->back_title ? ', ' . $t->back_title : '');
                    $genderText = in_array($t->gender, ['Female', 'P', 'Perempuan']) ? 'Perempuan' : 'Laki-laki';
                    
                    $status = $t->status ?? 'Active';
                    $badgeClass = 'badge-active';
                    $statusLabel = 'Aktif';
                    if ($status === 'Leave') {
                        $badgeClass = 'badge-leave';
                        $statusLabel = 'Cuti';
                    } elseif ($status === 'Inactive') {
                        $badgeClass = 'badge-inactive';
                        $statusLabel = 'Nonaktif';
                    }

                    $birthDate = (!empty($t->birth_date) && $t->birth_date !== '0000-00-00' && strtotime($t->birth_date)) ? date('d/m/Y', strtotime($t->birth_date)) : '-';
                    $tmtDate = (!empty($t->task_start_date) && $t->task_start_date !== '0000-00-00' && strtotime($t->task_start_date)) ? date('d/m/Y', strtotime($t->task_start_date)) : '-';
                    
                    $assignedClassrooms = $t->classrooms;
                    $totalMurid = $assignedClassrooms->sum(fn($c) => $c->students->count());
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    
                    <!-- Pendidik -->
                    <td>
                        <span class="val" style="font-size: 8.5pt;">{{ $fullName }}</span><br>
                        <span class="label">Gender:</span> {{ $genderText }}<br>
                        <span class="label">Lahir:</span> {{ $t->birth_place ?? '-' }}, {{ $birthDate }}
                    </td>

                    <!-- Identitas -->
                    <td>
                        <span class="label">NIY / NIP:</span> <span class="val">{{ $t->niy ?? '-' }}</span><br>
                        <span class="label">NUPTK:</span> {{ $t->nuptk ?? '-' }}<br>
                        <span class="label">NIK:</span> {{ $t->nik ?? '-' }}<br>
                        @if(!empty($t->zkteco_uid))
                            <span class="label">PIN Absen:</span> <span style="font-family: monospace; font-size: 7.5pt; font-weight: bold; color: #4f46e5;">{{ $t->zkteco_uid }}</span>
                        @endif
                    </td>

                    <!-- Penugasan -->
                    <td>
                        <span class="label">Jabatan:</span> <strong style="color: #1e293b;">{{ $t->position ?? 'Guru Kelas' }}</strong>
                        @if(!empty($t->additional_position) && $t->additional_position !== '-')
                            <br><span class="label">Tambahan:</span> {{ $t->additional_position }}
                        @endif
                        <br>
                        <span class="label">Kelompok Diampu:</span><br>
                        @if($assignedClassrooms->count() > 0)
                            @foreach($assignedClassrooms as $cls)
                                <span class="badge badge-classroom">[{{ $cls->sub_unit }}] {{ $cls->name }} ({{ $cls->students->count() }})</span>
                            @endforeach
                            <br><span class="label">Total Murid Asuhan:</span> <strong>{{ $totalMurid }} Anak</strong>
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Belum ada kelompok</span>
                        @endif
                    </td>

                    <!-- Akademik & Kepegawaian -->
                    <td>
                        <span class="label">Pendidikan:</span> <span class="val">{{ $t->last_education ?? '-' }}</span><br>
                        <span class="label">Jurusan:</span> {{ $t->major ?? '-' }}<br>
                        <span class="label">Status Kerja:</span> {{ $t->employment_status ?? '-' }}<br>
                        <span class="label">Golongan:</span> {{ $t->pangkat_golongan ?? '-' }}
                    </td>

                    <!-- Kontak & Administrasi -->
                    <td>
                        <span class="label">No. HP/WA:</span> <strong style="color: #047857; font-family: monospace;">{{ $t->phone ?? '-' }}</strong><br>
                        <span class="label">Email:</span> <span style="font-size: 7pt;">{{ $t->email ?? '-' }}</span><br>
                        <span class="label">TMT Tugas:</span> {{ $tmtDate }}<br>
                        <span class="label">Status:</span> <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 18px; color: #94a3b8;">
                        Tidak ada data guru yang sesuai dengan kriteria filter saat ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SIGNATURE BLOCK -->
    <table class="signature-table">
        <tr>
            <td width="65%"></td>
            <td width="35%" style="text-align: center;">
                <p style="margin: 0; font-size: 8pt; color: #475569;">{{ $schoolCity }}, {{ date('d F Y') }}</p>
                <p style="margin: 2px 0 0 0; font-size: 8pt; font-weight: bold; color: #1e293b;">Kepala Sekolah</p>
                <div style="height: 50px;"></div>
                <p style="margin: 0; font-size: 8.5pt; font-weight: bold; text-decoration: underline; color: #0f172a;">{{ $headmasterName }}</p>
                <p style="margin: 2px 0 0 0; font-size: 7.5pt; color: #64748b;">NIP. {{ $headmasterNip }}</p>
            </td>
        </tr>
    </table>

    <!-- FOOTER NOTE -->
    <div class="footer-note">
        Dokumen ini diterbitkan secara otomatis oleh Sistem Informasi & Manajemen Akademik SANS PAUD. Sah tanpa tanda tangan basah jika dilengkapi stempel digital resmi sekolah.
    </div>

</body>
</html>
