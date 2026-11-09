<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Distribusi Murid & Ketenagaan Kelompok Belajar - {{ $selectedYear ? $selectedYear->name : '' }}</title>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #0f172a;
            padding: 24px;
            font-size: 11px;
            line-height: 1.4;
        }

        .no-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #4f46e5;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        .print-page {
            background: #ffffff;
            width: 100%;
            max-width: 1050px;
            margin: 0 auto;
            padding: 32px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        /* HEADER / KOP SURAT */
        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 3px double #0f172a;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .header-logo {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
            text-align: center;
        }

        .header-text h1 {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #0f172a;
        }

        .header-text h2 {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }

        .header-text p {
            font-size: 10px;
            color: #64748b;
        }

        /* DOCUMENT TITLE */
        .doc-title {
            text-align: center;
            margin-bottom: 16px;
        }

        .doc-title h3 {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .doc-title p {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }

        /* TABLE STYLING */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 16px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }

        th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }

        .level-header {
            background-color: #f8fafc;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 10px;
            color: #1e293b;
        }

        .subtotal-row {
            background-color: #e2e8f0;
            font-weight: 700;
            color: #0f172a;
        }

        .grand-total-row {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
        }
        .grand-total-row td {
            border-color: #0f172a;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

        /* SIGNATURES */
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 250px;
            text-align: center;
        }

        .signature-box .date {
            margin-bottom: 8px;
            font-size: 10.5px;
        }

        .signature-box .role {
            font-size: 10.5px;
            font-weight: 600;
            margin-bottom: 55px;
        }

        .signature-box .name {
            font-size: 11px;
            font-weight: 700;
            text-decoration: underline;
        }

        .signature-box .nip {
            font-size: 9.5px;
            color: #475569;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-page {
                border: none;
                box-shadow: none;
                padding: 10px;
                max-width: 100%;
            }
            th, td {
                border-color: #94a3b8 !important;
            }
            .grand-total-row {
                background-color: #334155 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .subtotal-row {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .level-header {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- NO PRINT CONTROLS -->
    <div class="no-print">
        <div>
            <span style="font-weight: 700; font-size: 13px;">Pratinjau Cetak Rekapitulasi Rombel & Kesiswaan</span>
            <span style="color: #64748b; font-size: 11px; margin-left: 8px;">(Format resmi untuk Pelaporan & Arsip PAUD Anak Saleh)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                Tutup
            </button>
        </div>
    </div>

    @php
        $logoBase64 = null;
        $logoSetting = function_exists('setting') ? setting('app_logo') : null;
        $candidatePaths = [];
        if ($logoSetting) {
            $candidatePaths[] = storage_path('app/public/' . $logoSetting);
            $candidatePaths[] = public_path('storage/' . $logoSetting);
        }
        $candidatePaths[] = public_path('icons/icon-192x192.png');
        $candidatePaths[] = public_path('images/logo.png');

        foreach ($candidatePaths as $p) {
            if (file_exists($p) && is_file($p)) {
                $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                $mime = match($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
                break;
            }
        }
    @endphp

    <!-- PRINT SHEET -->
    <div class="print-page">
        <!-- KOP SURAT -->
        <div class="header">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" class="header-logo">
            @else
                <div class="header-logo" style="display: flex; align-items: center; justify-content: center; background: #4f46e5; color: white; border-radius: 8px; font-weight: 800; font-size: 20px;">
                    PAUD
                </div>
            @endif
            <div class="header-text">
                <h1>{{ strtoupper(function_exists('setting') ? setting('unit_name', 'PAUD Terpadu Anak Saleh') : 'PAUD Terpadu Anak Saleh') }}</h1>
                <h2>YAYASAN PENDIDIKAN ANAK SALEH MALANG</h2>
                <p>{{ function_exists('setting') ? setting('school_address', 'Jl. Arumba No. 31, Tunggulwulung, Kec. Lowokwaru, Kota Malang, Jawa Timur 65143') : 'Jl. Arumba No. 31, Tunggulwulung, Kec. Lowokwaru, Kota Malang, Jawa Timur 65143' }}</p>
                <p>Telp: {{ function_exists('setting') ? setting('school_phone', '(0341) 480280') : '(0341) 480280' }} | Email: {{ function_exists('setting') ? setting('school_email', 'info@paudanaksaleh.sch.id') : 'info@paudanaksaleh.sch.id' }}</p>
            </div>
        </div>

        <!-- DOCUMENT TITLE -->
        <div class="doc-title">
            <h3>REKAPITULASI DISTRIBUSI MURID & KETENAGAAN KELOMPOK BELAJAR</h3>
            <p>TAHUN AJARAN: {{ $selectedYear ? strtoupper($selectedYear->name) : 'SEMUA PERIODE' }} {{ !empty($selectedJenjang) ? ' | JENJANG: ' . strtoupper($selectedJenjang->name) : '' }}</p>
        </div>

        <!-- TABLE -->
        <table>
            <thead>
                <tr>
                    <th style="width: 25px;">No</th>
                    <th style="width: 150px;">Kelompok Belajar</th>
                    <th style="width: 55px;">L</th>
                    <th style="width: 55px;">P</th>
                    <th style="width: 65px;">Jml Murid</th>
                    <th style="width: 70px;">Inklusi (PDBK)</th>
                    <th>Wali Kelas</th>
                    <th style="width: 120px;">Kontak</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($levelReports as $lvl)
                    @if(count($lvl['classrooms']) > 0)
                        <!-- LEVEL HEADER -->
                        <tr class="level-header">
                            <td colspan="8" style="padding-left: 8px;">
                                {{ strtoupper($lvl['level']->name) }} ({{ $lvl['level']->jenjang ? $lvl['level']->jenjang->name : '' }})
                            </td>
                        </tr>

                        <!-- CLASSROOMS -->
                        @foreach($lvl['classrooms'] as $cr)
                            <tr>
                                <td class="text-center font-mono">{{ $no++ }}</td>
                                <td class="font-bold">{{ $cr['name'] }}</td>
                                <td class="text-center font-mono">{{ $cr['male'] }}</td>
                                <td class="text-center font-mono">{{ $cr['female'] }}</td>
                                <td class="text-center font-mono font-bold">{{ $cr['total'] }}</td>
                                <td class="text-center font-mono {{ $cr['pdbk'] > 0 ? 'font-bold' : '' }}">
                                    {{ $cr['pdbk'] > 0 ? $cr['pdbk'] : '-' }}
                                </td>
                                <td>{{ $cr['homeroom_teacher'] ?: '-' }}</td>
                                <td class="text-center font-mono">{{ $cr['homeroom_teacher_phone'] ?: '-' }}</td>
                            </tr>
                        @endforeach

                        <!-- SUBTOTAL -->
                        <tr class="subtotal-row">
                            <td colspan="2" class="text-right" style="font-size: 9.5px;">
                                Subtotal {{ $lvl['level']->name }}:
                            </td>
                            <td class="text-center font-mono">{{ $lvl['subtotal_male'] }}</td>
                            <td class="text-center font-mono">{{ $lvl['subtotal_female'] }}</td>
                            <td class="text-center font-mono font-bold">{{ $lvl['subtotal_students'] }}</td>
                            <td class="text-center font-mono">{{ $lvl['subtotal_pdbk'] }}</td>
                            <td colspan="2" style="font-size: 9px; font-weight: normal; font-style: italic;">
                                Total {{ count($lvl['classrooms']) }} kelompok belajar
                            </td>
                        </tr>
                    @endif
                @endforeach

                <!-- MURID AKTIF BELUM MEMILIKI KELOMPOK (JIKA ADA) -->
                @if(!empty($unassignedStudents) && $unassignedTotal > 0)
                    <tr class="level-header" style="background-color: #fef3c7; color: #92400e;">
                        <td colspan="8" style="padding-left: 8px;">
                            MURID BELUM MEMILIKI KELOMPOK BELAJAR (PERLU ALOKASI KELOMPOK)
                        </td>
                    </tr>

                    @foreach($unassignedStudents as $us)
                        @php
                            $isUsMale = in_array(strtoupper(trim($us->gender ?? '')), ['L', 'LAKI-LAKI', 'MALE']);
                            $isUsFemale = in_array(strtoupper(trim($us->gender ?? '')), ['P', 'PEREMPUAN', 'FEMALE']);
                            $isUsPdbk = (!empty($us->special_needs) && !in_array(strtolower(trim($us->special_needs)), ['tidak', 'tidak ada', 'none', '-']))
                                        || (!empty($us->special_needs_type) && !in_array(strtolower(trim($us->special_needs_type)), ['tidak', 'tidak ada', 'none', '-']));
                        @endphp
                        <tr style="background-color: #fffbeb;">
                            <td class="text-center font-mono">{{ $no++ }}</td>
                            <td class="font-bold">{{ $us->full_name }} (NIS: {{ $us->nis ?: '-' }}) [Tanpa Kelompok]</td>
                            <td class="text-center font-mono">{{ $isUsMale ? 1 : 0 }}</td>
                            <td class="text-center font-mono">{{ $isUsFemale ? 1 : 0 }}</td>
                            <td class="text-center font-mono font-bold">1</td>
                            <td class="text-center font-mono {{ $isUsPdbk ? 'font-bold' : '' }}">
                                {{ $isUsPdbk ? 1 : 0 }}
                            </td>
                            <td style="color: #64748b; font-style: italic;">Belum dialokasikan</td>
                            <td class="text-center">-</td>
                        </tr>
                    @endforeach

                    <!-- SUBTOTAL UNASSIGNED -->
                    <tr class="subtotal-row" style="background-color: #fde68a;">
                        <td colspan="2" class="text-right" style="font-size: 9.5px; color: #78350f;">
                            Subtotal Belum Ada Kelompok:
                        </td>
                        <td class="text-center font-mono">{{ $unassignedMale }}</td>
                        <td class="text-center font-mono">{{ $unassignedFemale }}</td>
                        <td class="text-center font-mono font-bold">{{ $unassignedTotal }}</td>
                        <td class="text-center font-mono">{{ $unassignedPdbk }}</td>
                        <td colspan="2" style="font-size: 9px; font-weight: normal; font-style: italic; color: #78350f;">
                            Murid aktif menunggu alokasi kelompok belajar
                        </td>
                    </tr>
                @endif

                <!-- GRAND TOTAL -->
                <tr class="grand-total-row">
                    <td colspan="2" class="text-right" style="padding: 7px 10px;">
                        JUMLAH MURID KESELURUHAN:
                    </td>
                    <td class="text-center font-mono">{{ $grandTotalMale }}</td>
                    <td class="text-center font-mono">{{ $grandTotalFemale }}</td>
                    <td class="text-center font-mono font-bold" style="font-size: 12px;">{{ $grandTotalStudents }}</td>
                    <td class="text-center font-mono">{{ $grandTotalPdbk }}</td>
                    <td colspan="2" style="padding: 7px 10px; font-size: 9.5px; font-weight: normal;">
                        Rasio: {{ $malePercent }}% Laki-laki / {{ $femalePercent }}% Perempuan ({{ $grandTotalClassrooms }} Kelompok)
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="signature-box">
                <div class="role">Mengetahui,<br>Kepala PAUD Anak Saleh</div>
                <div class="name">{{ $headmasterName ?? 'Dra. Hj. Mike Supraptiwi, S.Psi, M.Pd.' }}</div>
                <div class="nip">{{ !empty($headmasterNiy) ? 'NIY. ' . $headmasterNiy : 'NIY. -' }}</div>
            </div>

            <div class="signature-box">
                <div class="date">Malang, {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}</div>
                <div class="role">Petugas Administrasi Akademik</div>
                <div class="name">{{ auth()->user()->name ?? 'Petugas Akademik' }}</div>
                <div class="nip">Staff Kurikulum & Rombel</div>
            </div>
        </div>
    </div>
</body>
</html>
