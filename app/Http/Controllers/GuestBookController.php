<?php

namespace App\Http\Controllers;

use App\Models\GuestBook;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GuestBookController extends Controller
{
    /**
     * Display the Admin Guest Book Dashboard.
     */
    public function index(Request $request)
    {
        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        $selectedYear = (int) $request->get('year', 2026);
        if ($selectedYear < 2026 || $selectedYear > 2030) {
            $selectedYear = 2026;
        }
        $selectedCategory = $request->get('category', 'all');
        $search = $request->get('search');

        // Query
        $query = GuestBook::whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth);

        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('host_recipient', 'like', "%{$search}%");
            });
        }

        $guests = $query->orderBy('date', 'desc')->orderBy('time', 'desc')->get();

        // Monthly Stats (all categories in selected month/year)
        $monthQuery = GuestBook::whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
        $allMonthGuests = (clone $monthQuery)->get();

        $stats = [
            'total' => $allMonthGuests->count(),
            'spmb' => $allMonthGuests->where('category', 'SPMB')->count(),
            'dinas' => $allMonthGuests->where('category', 'Dinas')->count(),
            'puskesmas' => $allMonthGuests->filter(fn($g) => in_array($g->category, ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes']))->count(),
            'umum' => $allMonthGuests->where('category', 'Umum')->count(),
        ];

        // Month Names
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // Rentang Tahun: 2026 sampai 2030
        $yearsList = range(2026, 2030);

        $categories = ['Umum', 'Dinas', 'Puskesmas/Dinkes', 'SPMB'];

        return view('admin.guest-books.index', compact(
            'guests',
            'selectedMonth',
            'selectedYear',
            'selectedCategory',
            'search',
            'stats',
            'monthNames',
            'yearsList',
            'categories'
        ));
    }

    /**
     * Store a new Guest Book entry (Admin).
     */
    public function store(Request $request)
    {
        $validated = $this->validateGuestBook($request);

        $validated['recorded_by'] = auth()->id();
        GuestBook::create($validated);

        return redirect()->route('guest-books.index', [
            'month' => Carbon::parse($validated['date'])->month,
            'year' => Carbon::parse($validated['date'])->year,
            'category' => $validated['category'],
        ])->with('success', "Buku tamu atas nama {$validated['name']} berhasil dicatat.");
    }

    /**
     * Update an existing Guest Book entry.
     */
    public function update(Request $request, $id)
    {
        $guest = GuestBook::findOrFail($id);
        $validated = $this->validateGuestBook($request);

        $guest->update($validated);

        return redirect()->route('guest-books.index', [
            'month' => Carbon::parse($validated['date'])->month,
            'year' => Carbon::parse($validated['date'])->year,
        ])->with('success', "Data buku tamu {$guest->name} berhasil diperbarui.");
    }

    /**
     * Delete a Guest Book entry.
     */
    public function destroy($id)
    {
        $guest = GuestBook::findOrFail($id);
        $month = Carbon::parse($guest->date)->month;
        $year = Carbon::parse($guest->date)->year;

        $guest->delete();

        return redirect()->route('guest-books.index', [
            'month' => $month,
            'year' => $year,
        ])->with('success', 'Catatan buku tamu berhasil dihapus.');
    }

    /**
     * Public Guest Book Kiosk Form (accessible at /buku-tamu).
     */
    public function publicForm()
    {
        $categories = ['Umum', 'Dinas', 'Puskesmas/Dinkes', 'SPMB'];
        return view('guest-book-public', compact('categories'));
    }

    /**
     * Public Guest Book Submit.
     */
    public function publicSubmit(Request $request)
    {
        $validated = $this->validateGuestBook($request);
        GuestBook::create($validated);

        return redirect()->route('buku-tamu.public')
            ->with('success_public', "Terima kasih Bapak/Ibu {$validated['name']}! Kunjungan Anda telah berhasil dicatat di buku tamu kami.");
    }

    /**
     * Export Guest Book to Excel.
     */
    public function exportExcel(Request $request)
    {
        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        $selectedYear = (int) $request->get('year', 2026);
        if ($selectedYear < 2026 || $selectedYear > 2030) {
            $selectedYear = 2026;
        }
        $selectedCategory = $request->get('category', 'all');

        $query = GuestBook::whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }
        $guests = $query->orderBy('date', 'asc')->orderBy('time', 'asc')->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$selectedMonth] ?? $selectedMonth;

        $principal = \App\Models\Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $headmasterName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $headmasterNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');
        $appName = setting('unit_name', setting('app_name', 'SAANS PAUD Malang'));
        $appAddress = setting('app_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur');
        $appPhone = setting('app_phone', '+62 812-3456-7890');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Buku Tamu ' . substr($monthName, 0, 3));

        // Header Title
        $sheet->setCellValue('A1', 'BUKU REGISTER KUNJUNGAN TAMU & KEDINASAN');
        $sheet->setCellValue('A2', strtoupper($appName));
        $sheet->setCellValue('A3', "{$appAddress} | Telp: {$appPhone}");
        $catLabel = $selectedCategory !== 'all' ? "Kategori: {$selectedCategory} | " : "";
        $sheet->setCellValue('A4', "{$catLabel}Periode: {$monthName} {$selectedYear} | Dicetak: " . date('d/m/Y H:i') . ' WIB');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0F766E');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setARGB('FF475569');
        $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF64748B');

        // Table Headers
        $headers = [
            'No', 'Tanggal', 'Waktu', 'Nama Tamu', 'No. HP', 'Kategori',
            'Institusi / Lembaga', 'Jabatan', 'Pekerjaan', 'Kota / Kab',
            'Provinsi', 'Alamat Lengkap', 'Keperluan / Tujuan', 'Penerima Tamu', 'Kesan & Pesan'
        ];
        $startRow = 6;

        foreach ($headers as $colIndex => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E'); // Teal 700
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        $currentRow = $startRow + 1;
        $no = 1;

        foreach ($guests as $g) {
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", Carbon::parse($g->date)->format('d/m/Y'));
            $sheet->setCellValue("C{$currentRow}", $g->formatted_time);
            $sheet->setCellValue("D{$currentRow}", $g->name);
            $sheet->setCellValueExplicit("E{$currentRow}", $g->phone ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$currentRow}", $g->category);
            $sheet->setCellValue("G{$currentRow}", $g->institution ?: '-');
            $sheet->setCellValue("H{$currentRow}", $g->position ?: '-');
            $sheet->setCellValue("I{$currentRow}", $g->occupation ?: '-');
            $sheet->setCellValue("J{$currentRow}", $g->city ?: '-');
            $sheet->setCellValue("K{$currentRow}", $g->province ?: '-');
            $sheet->setCellValue("L{$currentRow}", $g->address ?: '-');
            $sheet->setCellValue("M{$currentRow}", $g->purpose);
            $sheet->setCellValue("N{$currentRow}", $g->host_recipient ?: '-');
            $sheet->setCellValue("O{$currentRow}", $g->feedback ?: '-');

            $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Borders
        if ($currentRow > $startRow + 1) {
            $sheet->getStyle("A{$startRow}:{$lastCol}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));
        }

        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        // Dynamic Signatures in Excel
        $signRow = $currentRow + 2;
        $sheet->setCellValue("C{$signRow}", "Petugas Penerima Tamu / Piket,");
        $sheet->setCellValue("L{$signRow}", "{$schoolCity}, " . Carbon::now()->translatedFormat('d F Y') . "\nMengetahui, Kepala Sekolah");
        $sheet->getStyle("C{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("L{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("C" . ($signRow + 4), "( ................................................ )");
        $sheet->getStyle("C" . ($signRow + 4))->getFont()->setBold(true);
        $sheet->getStyle("C" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("C" . ($signRow + 5), "NIP/NIY: ................................................");
        $sheet->getStyle("C" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("C" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue("L" . ($signRow + 4), $headmasterName);
        $sheet->getStyle("L" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("L" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("L" . ($signRow + 5), "NIP: " . $headmasterNip);
        $sheet->getStyle("L" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("L" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($request->filled('download_token')) {
            if (!headers_sent()) {
                @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "Buku_Tamu_{$monthName}_{$selectedYear}.xlsx";

        $response = response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Direct download PDF for Guest Book.
     */
    public function downloadPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        $selectedYear = (int) $request->get('year', 2026);
        if ($selectedYear < 2026 || $selectedYear > 2030) {
            $selectedYear = 2026;
        }
        $selectedCategory = $request->get('category', 'all');

        $query = GuestBook::whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }
        $guests = $query->orderBy('date', 'asc')->orderBy('time', 'asc')->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$selectedMonth] ?? $selectedMonth;

        $principal = \App\Models\Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $headmasterName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $headmasterNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');
        $appName = setting('unit_name', setting('app_name', 'SAANS PAUD Malang'));
        $appAddress = setting('app_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur');
        $appPhone = setting('app_phone', '+62 812-3456-7890');

        $fileName = "Buku_Tamu_{$monthName}_{$selectedYear}.pdf";

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.guest-books.pdf', [
            'guests' => $guests,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedCategory' => $selectedCategory,
            'monthName' => $monthName,
            'schoolCity' => $schoolCity,
            'headmasterName' => $headmasterName,
            'headmasterNip' => $headmasterNip,
            'appName' => $appName,
            'appAddress' => $appAddress,
            'appPhone' => $appPhone,
        ])->setPaper('a4', 'landscape');

        $response = $pdf->download($fileName);
        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }
        return $response;
    }

    /**
     * Printable view of Guest Book.
     */
    public function print(Request $request)
    {
        $selectedMonth = (int) $request->get('month', Carbon::now()->month);
        $selectedYear = (int) $request->get('year', 2026);
        if ($selectedYear < 2026 || $selectedYear > 2030) {
            $selectedYear = 2026;
        }
        $selectedCategory = $request->get('category', 'all');

        $query = GuestBook::whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }
        $guests = $query->orderBy('date', 'asc')->orderBy('time', 'asc')->get();

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthNames[$selectedMonth] ?? $selectedMonth;

        $principal = \App\Models\Employee::where(function($q) {
            $q->where('position', 'like', '%Kepala Sekolah%')
              ->orWhere('position', 'like', '%Kepsek%');
        })->first();
        $headmasterName = $principal?->name ?? setting('headmaster_name', setting('school_principal_name', 'Dra. Hj. Nurul Hidayati, M.Pd'));
        $headmasterNip = $principal?->nip ?? setting('headmaster_nip', setting('school_principal_nip', '19750815 200212 2 003'));
        $schoolCity = setting('school_city', 'Malang');
        $appName = setting('unit_name', setting('app_name', 'SAANS PAUD Malang'));
        $appAddress = setting('app_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur');
        $appPhone = setting('app_phone', '+62 812-3456-7890');

        return view('admin.guest-books.print', compact(
            'guests',
            'selectedMonth',
            'selectedYear',
            'selectedCategory',
            'monthName',
            'schoolCity',
            'headmasterName',
            'headmasterNip',
            'appName',
            'appAddress',
            'appPhone'
        ));
    }

    /**
     * Validate common guestbook input fields.
     */
    private function validateGuestBook(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'category' => 'required|in:Umum,Dinas,Puskesmas/Dinkes,SPMB',
            'institution' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'purpose' => 'required|string',
            'host_recipient' => 'nullable|string|max:255',
            'feedback' => 'nullable|string',
            'date' => 'required|date',
            'time' => 'nullable',
        ]);
    }
}
