<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Akademik - PAUD Anak Saleh Malang</title>
    <style>
        @page {
            margin: 12mm 10mm 12mm 10mm;
            size: a4 landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
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
            font-size: 9.5pt;
            color: #475569;
            margin: 2px 0 0 0;
            font-weight: 600;
        }
        .meta-info {
            display: table;
            width: 100%;
            margin-top: 6px;
            font-size: 8pt;
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
        
        /* Summary Box */
        .summary-box {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 6px;
        }
        .summary-card {
            display: table-cell;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 6px 10px;
            text-align: center;
            width: 33.33%;
        }
        .summary-label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .summary-value {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5pt;
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
        .font-bold {
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3px;
            background-color: #e0e7ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }
        tfoot tr {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        
        /* Signatures */
        .signature-table {
            width: 100%;
            border: none;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            width: 50%;
        }
        .sig-block {
            text-align: center;
            width: 200px;
        }
        .sig-space {
            height: 55px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <h1 class="title">Data Akademik PAUD Anak Saleh Malang</h1>
        <h2 class="subtitle">Matriks Rekapitulasi Rombel, Jumlah Murid & Penugasan Wali Kelas</h2>
        <div class="meta-info">
            <div class="meta-cell meta-left">
                Tahun Ajaran: <span class="meta-tag">{{ $selectedYear ? $selectedYear->name : 'Semua Tahun Ajaran' }}</span>
                @if(isset($selectedJenjang) && $selectedJenjang)
                    | Jenjang: <span class="meta-tag">{{ $selectedJenjang->name }}</span>
                @endif
            </div>
            <div class="meta-cell meta-right">
                Dicetak pada: <strong>{{ date('d/m/Y H:i') }} WIB</strong>
            </div>
        </div>
    </div>

    <!-- Mini Summary Cards -->
    <div class="summary-box">
        <div class="summary-card">
            <div class="summary-label">Total Rombel / Kelompok</div>
            <div class="summary-value" style="color: #4f46e5;">{{ number_format($stats['total_classrooms']) }} Rombel</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Murid Terdaftar</div>
            <div class="summary-value" style="color: #059669;">{{ number_format($stats['total_students']) }} Murid</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Wali Kelas Terplot</div>
            <div class="summary-value" style="color: #d97706;">{{ number_format($stats['total_assigned_teachers']) }} / {{ $stats['total_classrooms'] }} Guru</div>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 90px;" class="text-center">Tahun Ajaran</th>
                <th style="width: 120px;" class="text-center">Jenjang</th>
                <th style="width: 110px;" class="text-center">Kelas</th>
                <th>Rombel / Kelompok</th>
                <th style="width: 85px;" class="text-center">Jumlah Murid</th>
                <th>Wali Kelas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($academicRows as $index => $r)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center font-bold">{{ $r['academic_year_name'] }}</td>
                    <td class="text-center">
                        <span class="badge">{{ $r['jenjang_name'] }}</span>
                    </td>
                    <td class="text-center font-bold">{{ $r['class_level_name'] }}</td>
                    <td class="font-bold">{{ $r['classroom_name'] }}</td>
                    <td class="text-center font-bold" style="color: #059669;">{{ $r['student_count'] }} Murid</td>
                    <td>
                        <strong>{{ $r['homeroom_teacher_name'] }}</strong>
                        @if(!empty($r['teacher_phone']))
                            <div style="font-size: 7pt; color: #64748b;">{{ $r['teacher_phone'] }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                        Tidak ada data rombel / kelompok belajar untuk kriteria ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold" style="padding-right: 12px;">TOTAL KESELURUHAN:</td>
                <td class="text-center font-bold" style="color: #4f46e5;">{{ number_format($stats['total_students']) }} Murid</td>
                <td class="font-bold">{{ $stats['total_assigned_teachers'] }} Wali Kelas Terplot</td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td style="text-align: left;">
                <div class="sig-block" style="text-align: left;">
                    <div>Mengetahui,</div>
                    <div style="font-weight: 600;">Kepala PAUD Anak Saleh</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $headmasterName ?? 'Dra. Hj. Mike Supraptiwi, S.Psi, M.Pd.' }}</div>
                    <div style="font-size: 7.5pt; color: #475569;">NIY: {{ $headmasterNiy ?? '-' }}</div>
                </div>
            </td>
            <td style="text-align: right;">
                <div class="sig-block" style="float: right; text-align: left;">
                    <div>Malang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                    <div style="font-weight: 600;">Petugas Administrasi Akademik</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ auth()->user()->name ?? '( ............................................ )' }}</div>
                    <div style="font-size: 7.5pt; color: #475569;">Staff Kurikulum & Rombel</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
