<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Rombel & Kesiswaan - {{ setting('app_name', 'PAUD Anak Saleh') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            line-height: 1.4;
            font-size: 11pt;
            margin: 0;
            padding: 20px;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 15pt;
            margin: 0 0 4px 0;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 12pt;
            margin: 0 0 4px 0;
            font-weight: 600;
            color: #334155;
        }
        .header p {
            font-size: 9pt;
            margin: 0;
            color: #64748b;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 9.5pt;
        }
        .meta-info div span {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9.5pt;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-row {
            background-color: #f8fafc;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            width: 220px;
            font-size: 10pt;
        }
        .signature-space {
            height: 70px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- PRINT BUTTON (HIDDEN ON PRINT) -->
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px;">
            🖨️ Cetak Dokumen / Simpan PDF
        </button>
    </div>

    <!-- KOP SURAT / HEADER -->
    <div class="header">
        <h1>REKAPITULASI ROMBONGAN BELAJAR & KESISWAAN</h1>
        <h2>{{ setting('app_name', 'KB - TK - DAYCARE - TPQ ANAK SALEH MALANG') }}</h2>
        <p>Alamat: Jl. Candi Mendut No. 9B, Mojolangu, Lowokwaru, Kota Malang | Telp: (0341) 492025</p>
    </div>

    <!-- META INFO -->
    <div class="meta-info">
        <div>
            Tahun Ajaran: <span>{{ $selectedYear ? $selectedYear->name : '-' }} ({{ $selectedYear->semester ?? 'Ganjil' }})</span>
        </div>
        <div>
            Tanggal Cetak: <span>{{ date('d F Y') }}</span>
        </div>
    </div>

    <!-- TABEL REKAPITULASI KELOMPOK -->
    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th>Sub Unit</th>
                <th>Jenjang</th>
                <th>Nama Kelompok (Rombel)</th>
                <th>Wali Kelas / Pendidik</th>
                <th style="width: 50px;">Kapasitas</th>
                <th style="width: 45px;">L</th>
                <th style="width: 45px;">P</th>
                <th style="width: 55px;">Total</th>
                <th style="width: 55px;">Sisa</th>
                <th style="width: 55px;">% Terisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($classroomStats as $index => $c)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $c['sub_unit'] }}</td>
                    <td>{{ $c['class_level_name'] }}</td>
                    <td class="font-bold">{{ $c['name'] }}</td>
                    <td>{{ $c['teacher_name'] }}</td>
                    <td class="text-center">{{ $c['capacity'] }}</td>
                    <td class="text-center">{{ $c['male_count'] }}</td>
                    <td class="text-center">{{ $c['female_count'] }}</td>
                    <td class="text-center font-bold">{{ $c['total_count'] }}</td>
                    <td class="text-center">{{ $c['remaining_capacity'] }}</td>
                    <td class="text-center">{{ $c['occupancy_rate'] }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-center font-bold">TOTAL KESELURUHAN</td>
                <td class="text-center">{{ $stats['total_capacity'] }}</td>
                <td class="text-center">{{ $stats['male'] }}</td>
                <td class="text-center">{{ $stats['female'] }}</td>
                <td class="text-center font-bold">{{ $stats['total_active'] }}</td>
                <td class="text-center">{{ max(0, $stats['total_capacity'] - $stats['total_active']) }}</td>
                <td class="text-center">{{ $stats['occupancy_pct'] }}%</td>
            </tr>
        </tfoot>
    </table>

    <!-- RINGKASAN SUB-UNIT & STATISTIK -->
    <table style="width: 60%; margin-bottom: 25px;">
        <thead>
            <tr>
                <th colspan="2">Ringkasan Distribusi Layanan Sub-Unit</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Playgroup (KB)</td>
                <td class="text-center font-bold">{{ $stats['pg'] }} Murid ({{ count($groupedBySubUnit['PG']) }} Rombel)</td>
            </tr>
            <tr>
                <td>Taman Kanak-Kanak (TK)</td>
                <td class="text-center font-bold">{{ $stats['tk'] }} Murid ({{ count($groupedBySubUnit['TK']) }} Rombel)</td>
            </tr>
            <tr>
                <td>Daycare / TPA</td>
                <td class="text-center font-bold">{{ $stats['daycare'] }} Anak ({{ count($groupedBySubUnit['DAYCARE']) }} Ruang)</td>
            </tr>
            <tr>
                <td>Santri TPQ Terpadu</td>
                <td class="text-center font-bold">{{ $stats['tpq'] }} Santri</td>
            </tr>
        </tbody>
    </table>

    <!-- TANDA TANGAN / PENGESAHAN -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p><strong>Kepala Sekolah PAUD</strong></p>
            <div class="signature-space"></div>
            <p><strong>( ................................................ )</strong></p>
            <p style="font-size: 8.5pt; color: #64748b;">NIP/NIY: ....................................</p>
        </div>

        <div class="signature-box">
            <p>Malang, {{ date('d F Y') }}</p>
            <p><strong>Waka Kesiswaan & Akademik</strong></p>
            <div class="signature-space"></div>
            <p><strong>( ................................................ )</strong></p>
            <p style="font-size: 8.5pt; color: #64748b;">NIP/NIY: ....................................</p>
        </div>
    </div>

</body>
</html>
