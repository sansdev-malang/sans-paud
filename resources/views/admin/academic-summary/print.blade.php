<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Akademik PAUD Anak Saleh</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            color: #1e293b;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 4px 0;
            font-size: 14px;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #4f46e5;
            color: white;
            font-weight: bold;
            text-align: center;
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
        tfoot tr {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        @media print {
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1>Data Akademik PAUD Anak Saleh Malang</h1>
        <h2>Rekapitulasi Rombel, Jumlah Murid & Wali Kelas</h2>
        <p>Tahun Pelajaran: {{ $selectedYear ? $selectedYear->name : 'Semua Periode' }} | Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Tahun Pelajaran</th>
                <th>Jenjang</th>
                <th>Kelas</th>
                <th>Rombel</th>
                <th style="width: 100px;">Jumlah Murid</th>
                <th>Wali Kelas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($academicRows as $index => $r)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $r['academic_year_name'] }}</td>
                    <td class="text-center">{{ $r['jenjang_name'] }}</td>
                    <td class="text-center">{{ $r['class_level_name'] }}</td>
                    <td>{{ $r['classroom_name'] }}</td>
                    <td class="text-center font-bold">{{ $r['student_count'] }} Murid</td>
                    <td>{{ $r['homeroom_teacher_name'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold">TOTAL KESELURUHAN:</td>
                <td class="text-center font-bold">{{ $stats['total_students'] }} Murid</td>
                <td class="font-bold">{{ $stats['total_assigned_teachers'] }} Wali Kelas</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
