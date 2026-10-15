<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Kelas - {{ $classroom->name }} - {{ $monthName }} {{ $year }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm 1.2cm 1.2cm 1.2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.35;
            font-size: 8.5pt;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .header {
            position: relative;
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
            min-height: 50px;
        }
        .header-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        .header h1 {
            font-size: 12.5pt;
            margin: 0 0 2px 0;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 10pt;
            margin: 0 0 2px 0;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
        }
        .header p {
            font-size: 7.5pt;
            margin: 0;
            color: #64748b;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
            border: none;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 110px;
            display: inline-block;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            color: #0f172a;
        }
        .center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
            font-size: 8.5pt;
            page-break-inside: avoid;
        }
        .signature-table td {
            border: none;
            vertical-align: top;
            text-align: center;
            width: 50%;
        }
        .signature-space {
            height: 55px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .att-badge {
            margin-top: 4px;
            font-size: 7.5pt;
            color: #475569;
        }
    </style>
</head>
<body>

    <!-- KOP SEKOLAH -->
    <div class="header">
        @php
            $logoPath = null;
            if (setting('app_logo')) {
                $candidate = public_path('storage/' . setting('app_logo'));
                if (file_exists($candidate)) {
                    $logoPath = $candidate;
                }
            }
        @endphp
        @if($logoPath)
            <img src="{{ $logoPath }}" alt="Logo" class="header-logo">
        @endif
        <div style="{{ $logoPath ? 'padding: 0 55px;' : '' }}">
            <h1>AGENDA / JURNAL PEMBELAJARAN KELAS</h1>
            <h2>{{ strtoupper($appName ?? setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'))) }}</h2>
            <p>{{ setting('app_address', setting('school_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur')) }} | Telp/WA: {{ setting('app_phone', setting('school_phone', '+62 812-3456-7890')) }}</p>
        </div>
    </div>

    <!-- META INFORMATION -->
    <table class="meta-table">
        <tr>
            <td style="width: 55%;">
                <span class="label">Rombel / Kelompok</span>: <strong>{{ $classroom->name }}</strong> ({{ $classroom->sub_unit }})<br>
                <span class="label">Wali Kelas</span>: {{ $classroom->homeroomTeacher?->name ?? 'Belum Ditugaskan' }}
            </td>
            <td style="width: 45%; text-align: right;">
                <span class="label" style="text-align: left;">Tahun Ajaran</span>: <strong>{{ $taName }}</strong><br>
                <span class="label" style="text-align: left;">Periode Bulan</span>: <strong>{{ $monthName }} {{ $year }}</strong>
            </td>
        </tr>
    </table>

    <!-- TABEL JURNAL KELAS -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                <th style="width: 75px;">Hari, Tanggal</th>
                <th style="width: 65px;">Waktu</th>
                <th style="width: 95px;">Guru Pendidik</th>
                <th style="width: 105px;">Tema / Topik</th>
                <th style="text-align: left;">Uraian Kegiatan & Kondisi Kelas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $j)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center font-mono">
                        {{ \Carbon\Carbon::parse($j->date)->translatedFormat('l, d/m/Y') }}
                    </td>
                    <td class="center font-mono">
                        {{ $j->formatted_time }}
                    </td>
                    <td>
                        {{ $j->teacher?->name ?? ($classroom->homeroomTeacher?->name ?? '-') }}
                    </td>
                    <td>
                        {{ $j->topic ?: '-' }}
                    </td>
                    <td style="white-space: pre-line;">
                        {{ $j->class_condition }}
                        @php
                            $att = $j->attendance_summary;
                        @endphp
                        @if($att)
                            <div class="att-badge">
                                <em>Presensi: H: {{ $att['h'] }}, S: {{ $att['s'] }}, I: {{ $att['i'] }}, A: {{ $att['a'] }}</em>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center" style="padding: 15px; color: #64748b;">
                        Belum ada catatan jurnal kelas pada bulan {{ $monthName }} {{ $year }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
    <table class="signature-table">
        <tr>
            <td>
                <p style="margin: 0;">Mengetahui,</p>
                <p style="margin: 2px 0 0 0; font-weight: bold;">Kepala Sekolah</p>
                <div class="signature-space"></div>
                <p class="signature-name" style="margin: 0;">{{ $principalName }}</p>
                <p style="font-size: 7.5pt; color: #64748b; margin: 2px 0 0 0;">NIP/NIY: {{ $principalNip }}</p>
            </td>
            <td>
                <p style="margin: 0;">{{ $schoolCity }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p style="margin: 2px 0 0 0; font-weight: bold;">Wali Kelas</p>
                <div class="signature-space"></div>
                <p class="signature-name" style="margin: 0;">{{ $classroom->homeroomTeacher?->name ?? '( ........................................ )' }}</p>
                <p style="font-size: 7.5pt; color: #64748b; margin: 2px 0 0 0;">
                    NIP/NIY: {{ $classroom->homeroomTeacher?->nip ?? $classroom->homeroomTeacher?->niy ?? '-' }}
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
