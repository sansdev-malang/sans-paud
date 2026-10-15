<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap DDTK & SDIDTK - {{ $classroom->name }} - {{ $periodLabel }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.2cm 1.2cm 1.2cm 1.2cm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            line-height: 1.35;
            font-size: 8.5pt;
            margin: 0;
            padding: 15px;
            background: #fff;
        }
        .header {
            position: relative;
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
            min-height: 55px;
        }
        .header-logo {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 55px;
            height: 55px;
            object-fit: contain;
        }
        .header h1 {
            font-size: 13pt;
            margin: 0 0 3px 0;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 10.5pt;
            margin: 0 0 3px 0;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
        }
        .header p {
            font-size: 8pt;
            margin: 0;
            color: #475569;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 8.5pt;
        }
        .meta-info div p {
            margin: 2px 0;
        }
        .meta-info span.label {
            font-weight: 600;
            color: #475569;
            display: inline-block;
            width: 100px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            color: #0f172a;
        }
        th.sub-th {
            background-color: #f8fafc;
            font-size: 7.5pt;
        }
        td.center {
            text-align: center;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
            font-size: 8.5pt;
        }
        .signature-box {
            width: 220px;
            text-align: center;
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .no-print {
            margin-bottom: 12px;
            text-align: right;
        }
        .btn-print {
            padding: 6px 14px;
            background: #0f172a;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 8.5pt;
            cursor: pointer;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Cetak Dokumen / Simpan PDF</button>
        <button class="btn-print" style="background: #475569; margin-left: 8px;" onclick="window.close()">Tutup Jendela</button>
    </div>

    <!-- KOP SEKOLAH -->
    <div class="header">
        @if(setting('app_logo'))
            <img src="{{ asset('storage/' . setting('app_logo')) }}" alt="Logo" class="header-logo">
        @endif
        <div class="header-text" style="{{ setting('app_logo') ? 'padding: 0 65px;' : '' }}">
            <h1>REKAPITULASI PEMERIKSAAN DDTK & SDIDTK MURID</h1>
            <h2>{{ strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'))) }}</h2>
            <p>{{ setting('app_address', setting('school_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur')) }}</p>
            <p style="margin-top: 2px;">
                @if(setting('app_phone') || setting('school_phone'))
                    Telp/WA: {{ setting('app_phone', setting('school_phone')) }}
                @endif
                @if((setting('app_phone') || setting('school_phone')) && setting('app_email'))
                    &bull;
                @endif
                @if(setting('app_email'))
                    Email: {{ setting('app_email') }}
                @endif
            </p>
        </div>
    </div>

    <!-- META INFORMATION -->
    <div class="meta-info">
        <div>
            <p><span class="label">Rombel/Kelompok</span>: <strong>{{ $classroom->name }}</strong> ({{ $classroom->sub_unit }})</p>
            <p><span class="label">Wali Kelas</span>: {{ $classroom->homeroomTeacher?->name ?? 'Belum Ditugaskan' }}</p>
        </div>
        <div style="text-align: right;">
            <p><span class="label" style="text-align: left;">Periode Ukur</span>: <strong>{{ $periodLabel }}</strong></p>
            <p><span class="label" style="text-align: left;">Tanggal Cetak</span>: {{ date('d/m/Y H:i') }} WIB</p>
        </div>
    </div>

    <!-- TABEL REKAPITULASI KOMPREHENSIF -->
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 24px;">No</th>
                <th rowspan="2" style="width: 65px;">NIS</th>
                <th rowspan="2" style="text-align: left; min-width: 130px;">Nama Murid</th>
                <th rowspan="2" style="width: 25px;">L/P</th>
                <th colspan="4" style="background-color: #e0f2fe; color: #0369a1;">Pertumbuhan (DDTK)</th>
                <th colspan="2" style="background-color: #ede9fe; color: #5b21b6;">Visus Mata</th>
                <th rowspan="2" style="width: 65px;">KPSP</th>
                <th rowspan="2" style="width: 75px;">Gigi</th>
                <th rowspan="2" style="width: 50px;">GDA</th>
                <th colspan="2" style="background-color: #ffe4e6; color: #be123c;">Gejala</th>
                <th rowspan="2" style="min-width: 100px;">Catatan</th>
            </tr>
            <tr>
                <th class="sub-th" style="width: 45px;">TB (cm)</th>
                <th class="sub-th" style="width: 45px;">BB (kg)</th>
                <th class="sub-th" style="width: 45px;">L.KA</th>
                <th class="sub-th" style="width: 45px;">L.LE</th>
                <th class="sub-th" style="width: 45px;">Kanan</th>
                <th class="sub-th" style="width: 45px;">Kiri</th>
                <th class="sub-th" style="width: 40px;">Kusta</th>
                <th class="sub-th" style="width: 45px;">Frambusia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $st)
                @php
                    $rec = $records[$st->id] ?? null;
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center" style="font-family: monospace;">{{ $st->nis ?: '-' }}</td>
                    <td><strong>{{ $st->full_name }}</strong></td>
                    <td class="center">{{ $st->gender === 'L' || $st->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                    
                    <!-- DDTK -->
                    <td class="center" style="font-weight: bold; color: #0284c7;">{{ $rec?->height ?: '-' }}</td>
                    <td class="center" style="font-weight: bold; color: #d97706;">{{ $rec?->weight ?: '-' }}</td>
                    <td class="center">{{ $rec?->head_circumference ?: '-' }}</td>
                    <td class="center">{{ $rec?->arm_circumference ?: '-' }}</td>

                    <!-- SDIDTK -->
                    <td class="center">{{ $rec?->visus_right ?: '-' }}</td>
                    <td class="center">{{ $rec?->visus_left ?: '-' }}</td>
                    <td class="center">{{ $rec?->kpsp_result ?: '-' }}</td>
                    <td class="center">{{ $rec?->dental_health ?: '-' }}</td>
                    <td class="center" style="font-family: monospace;">{{ $rec?->blood_sugar !== null ? $rec->blood_sugar : '-' }}</td>
                    <td class="center" style="{{ $rec?->has_leprosy ? 'color: red; font-weight: bold;' : '' }}">
                        {{ $rec ? ($rec->has_leprosy ? 'YA' : 'TIDAK') : '-' }}
                    </td>
                    <td class="center" style="{{ $rec?->has_frambesia ? 'color: red; font-weight: bold;' : '' }}">
                        {{ $rec ? ($rec->has_frambesia ? 'YA' : 'TIDAK') : '-' }}
                    </td>
                    <td>{{ $rec?->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="center" style="padding: 15px; color: #64748b;">
                        Tidak ada data murid pada rombel ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p><strong>Kepala Sekolah</strong></p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ setting('headmaster_name', setting('school_principal_name', '( ........................................ )')) }}</p>
            <p style="font-size: 7.5pt; color: #64748b; margin-top: 2px;">
                NIP/NIY: {{ setting('headmaster_nip', setting('school_principal_nip', '-')) }}
            </p>
        </div>

        <div class="signature-box">
            <p>{{ setting('school_city', 'Malang') }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p><strong>Wali Kelas / Petugas UKS</strong></p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $classroom->homeroomTeacher?->name ?? '( ........................................ )' }}</p>
            <p style="font-size: 7.5pt; color: #64748b; margin-top: 2px;">
                NIP/NIY: {{ $classroom->homeroomTeacher?->nip ?? $classroom->homeroomTeacher?->niy ?? $classroom->homeroomTeacher?->nuptk ?? '-' }}
            </p>
        </div>
    </div>

</body>
</html>
