<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Pembelajaran Kelas - {{ $classroom->name }} - {{ $monthName }} {{ $year }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            line-height: 1.4;
            font-size: 9.5pt;
            margin: 0;
            padding: 15px;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header h1 {
            font-size: 13.5pt;
            margin: 0 0 3px 0;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 10.5pt;
            margin: 0 0 3px 0;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
        }
        .header p {
            font-size: 8.5pt;
            margin: 0;
            color: #64748b;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 9pt;
        }
        .meta-info div p {
            margin: 2px 0;
        }
        .meta-info span.label {
            font-weight: 600;
            color: #475569;
            display: inline-block;
            width: 110px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8.5pt;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            color: #0f172a;
        }
        td.center {
            text-align: center;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 35px;
            page-break-inside: avoid;
            font-size: 9pt;
        }
        .signature-box {
            width: 220px;
            text-align: center;
        }
        .signature-space {
            height: 65px;
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
            <img src="{{ asset('storage/' . setting('app_logo')) }}" alt="Logo" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 55px; height: 55px; object-fit: contain;">
        @endif
        <div style="{{ setting('app_logo') ? 'padding: 0 65px;' : '' }}">
            <h1>AGENDA / JURNAL PEMBELAJARAN KELAS</h1>
            <h2>{{ strtoupper($appName ?? setting('unit_name', setting('app_name', 'PAUD ANAK SALEH MALANG'))) }}</h2>
            <p>{{ setting('app_address', setting('school_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur')) }} | Telp/WA: {{ setting('app_phone', setting('school_phone', '+62 812-3456-7890')) }}</p>
        </div>
    </div>

    <!-- META INFORMATION -->
    <div class="meta-info">
        <div>
            <p><span class="label">Rombel / Kelompok</span>: <strong>{{ $classroom->name }}</strong> ({{ $classroom->sub_unit }})</p>
            <p><span class="label">Wali Kelas</span>: {{ $classroom->homeroomTeacher?->name ?? 'Belum Ditugaskan' }}</p>
        </div>
        <div style="text-align: right;">
            <p><span class="label" style="text-align: left;">Tahun Ajaran</span>: <strong>{{ $taName ?? '-' }}</strong></p>
            <p><span class="label" style="text-align: left;">Periode Bulan</span>: <strong>{{ $monthName }} {{ $year }}</strong></p>
            <p><span class="label" style="text-align: left;">Tanggal Cetak</span>: {{ date('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- TABEL JURNAL KELAS -->
    <table>
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th style="width: 85px;">Hari, Tanggal</th>
                <th style="width: 75px;">Waktu KBM</th>
                <th style="width: 100px;">Guru Pendidik</th>
                <th style="width: 110px;">Tema / Topik</th>
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
                            <div style="margin-top: 4px; font-size: 7.5pt; color: #475569;">
                                <em>Presensi: Hadir: {{ $att['h'] }}, Sakit: {{ $att['s'] }}, Izin: {{ $att['i'] }}, Alpa: {{ $att['a'] }}</em>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center" style="padding: 20px; color: #64748b;">
                        Belum ada catatan jurnal kelas pada bulan {{ $monthName }} {{ $year }}.
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
            <p class="signature-name">{{ $principalName }}</p>
            <p style="font-size: 8pt; color: #64748b; margin-top: 2px;">NIP/NIY: {{ $principalNip }}</p>
        </div>

        <div class="signature-box">
            <p>{{ $schoolCity }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p><strong>Wali Kelas</strong></p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $classroom->homeroomTeacher?->name ?? '( ........................................ )' }}</p>
            <p style="font-size: 8pt; color: #64748b; margin-top: 2px;">
                NIP/NIY: {{ $classroom->homeroomTeacher?->nip ?? $classroom->homeroomTeacher?->niy ?? '-' }}
            </p>
        </div>
    </div>

</body>
</html>
