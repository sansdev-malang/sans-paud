<?php

namespace App\Http\Controllers;

use App\Models\GuestBook;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            if (in_array($selectedCategory, ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes'])) {
                $query->whereIn('category', ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes']);
            } else {
                $query->where('category', $selectedCategory);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('host_recipient', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $perPageCount = ($perPage === 'all' || $perPage == 99999 || $perPage === '99999') ? 99999 : (int) $perPage;
        if ($perPageCount <= 0) {
            $perPageCount = 15;
        }

        $guests = $query->orderBy('date', 'desc')->orderBy('time', 'desc')->paginate($perPageCount)->withQueryString();

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
            'categories',
            'perPage'
        ));
    }

    /**
     * Store a new Guest Book entry (Admin).
     */
    public function store(Request $request)
    {
        $validated = $this->validateGuestBook($request);

        try {
            $validated['recorded_by'] = auth()->id();
            GuestBook::create($validated);

            $targetCategory = $request->get('current_category', $validated['category']);
            if (empty($targetCategory)) {
                $targetCategory = $validated['category'];
            }

            return redirect()->route('guest-books.index', [
                'month' => Carbon::parse($validated['date'])->month,
                'year' => Carbon::parse($validated['date'])->year,
                'category' => $targetCategory,
                'per_page' => $request->get('per_page', 15),
            ])->with('success', "Buku tamu atas nama {$validated['name']} berhasil dicatat.");
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan sistem saat menyimpan data buku tamu: ' . $e->getMessage()]);
        }
    }

    /**
     * Update an existing Guest Book entry.
     */
    public function update(Request $request, $id)
    {
        $guest = GuestBook::findOrFail($id);
        $validated = $this->validateGuestBook($request);

        try {
            $guest->update($validated);

            $targetCategory = $request->get('current_category', $validated['category']);
            if (empty($targetCategory)) {
                $targetCategory = $validated['category'];
            }

            return redirect()->route('guest-books.index', [
                'month' => Carbon::parse($validated['date'])->month,
                'year' => Carbon::parse($validated['date'])->year,
                'category' => $targetCategory,
                'per_page' => $request->get('per_page', 15),
            ])->with('success', "Data buku tamu {$guest->name} berhasil diperbarui.");
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan sistem saat memperbarui data buku tamu: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a Guest Book entry.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $guest = GuestBook::findOrFail($id);
            $month = Carbon::parse($guest->date)->month;
            $year = Carbon::parse($guest->date)->year;
            $cat = $request->get('current_category', $request->get('category', $guest->category));

            $guest->delete();

            return redirect()->route('guest-books.index', [
                'month' => $month,
                'year' => $year,
                'category' => $cat,
                'per_page' => $request->get('per_page', 15),
            ])->with('success', 'Catatan buku tamu berhasil dihapus.');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('error', 'Gagal menghapus data buku tamu: ' . $e->getMessage());
        }
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

        try {
            GuestBook::create($validated);

            return redirect()->route('buku-tamu.public')
                ->with('success_public', "Terima kasih Bapak/Ibu {$validated['name']}! Kunjungan Anda telah berhasil dicatat di buku tamu kami.");
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kendala saat mencatat kunjungan Anda. Silakan periksa kembali dan kirim ulang.']);
        }
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
            if (in_array($selectedCategory, ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes'])) {
                $query->whereIn('category', ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes']);
            } else {
                $query->where('category', $selectedCategory);
            }
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
            'No', 'Tanggal', 'Waktu', 'Nama Tamu / Ortu', 'No. HP', 'Kategori',
            'Institusi / Lembaga', 'Jabatan', 'Pekerjaan', 'Kota / Kab',
            'Provinsi', 'Alamat Lengkap', 'Keperluan / Kegiatan',
            'Nama Calon Murid (SPMB)', 'Tgl Lahir Murid', 'Riwayat Terapi', 'Diagnosa Terapi',
            'Penerima Tamu', 'Kesan & Pesan'
        ];
        $startRow = 6;

        foreach ($headers as $colIndex => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));

        // Merge Title rows across table width to prevent bloating Column A
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->getStyle("A1:A4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

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
            $sheet->setCellValue("N{$currentRow}", $g->student_name ?: '-');
            $sheet->setCellValue("O{$currentRow}", $g->student_birth_date ? Carbon::parse($g->student_birth_date)->format('d/m/Y') : '-');
            $sheet->setCellValue("P{$currentRow}", $g->therapy_history ?: '-');
            $sheet->setCellValue("Q{$currentRow}", $g->therapy_diagnosis ?: '-');
            $sheet->setCellValue("R{$currentRow}", $g->host_recipient ?: '-');
            $sheet->setCellValue("S{$currentRow}", $g->feedback ?: '-');

            $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$currentRow}:P{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Borders
        if ($currentRow > $startRow + 1) {
            $sheet->getStyle("A{$startRow}:{$lastCol}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));
        }

        // AutoSize columns
        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        // Fix column A ('No') to compact neat width
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(6);

        // Dynamic Signatures in Excel (merged across multiple columns to avoid column stretching)
        $signRow = $currentRow + 2;
        $sheet->mergeCells("B{$signRow}:E{$signRow}");
        $sheet->setCellValue("B{$signRow}", "Petugas Penerima Tamu / Piket,");
        $sheet->getStyle("B{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("N{$signRow}:S{$signRow}");
        $sheet->setCellValue("N{$signRow}", "{$schoolCity}, " . Carbon::now()->translatedFormat('d F Y') . "\nMengetahui, Kepala Sekolah");
        $sheet->getStyle("N{$signRow}")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("B" . ($signRow + 4) . ":E" . ($signRow + 4));
        $sheet->setCellValue("B" . ($signRow + 4), "( ................................................ )");
        $sheet->getStyle("B" . ($signRow + 4))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("B" . ($signRow + 5) . ":E" . ($signRow + 5));
        $sheet->setCellValue("B" . ($signRow + 5), "NIP/NIY: ................................................");
        $sheet->getStyle("B" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("B" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("N" . ($signRow + 4) . ":S" . ($signRow + 4));
        $sheet->setCellValue("N" . ($signRow + 4), $headmasterName);
        $sheet->getStyle("N" . ($signRow + 4))->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle("N" . ($signRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("N" . ($signRow + 5) . ":S" . ($signRow + 5));
        $sheet->setCellValue("N" . ($signRow + 5), "NIP: " . $headmasterNip);
        $sheet->getStyle("N" . ($signRow + 5))->getFont()->setSize(9);
        $sheet->getStyle("N" . ($signRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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
            if (in_array($selectedCategory, ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes'])) {
                $query->whereIn('category', ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes']);
            } else {
                $query->where('category', $selectedCategory);
            }
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
            if (in_array($selectedCategory, ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes'])) {
                $query->whereIn('category', ['Puskesmas/Dinkes', 'Puskesmas', 'Dinkes']);
            } else {
                $query->where('category', $selectedCategory);
            }
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
        $category = $request->input('category', 'Umum');

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'category' => 'required|in:Umum,Dinas,Puskesmas/Dinkes,SPMB',
            'institution' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'purpose' => $category === 'SPMB' ? 'nullable|string' : 'required|string',
            'host_recipient' => 'nullable|string|max:255',
            'feedback' => 'nullable|string',
            'date' => 'required|date',
            'time' => 'nullable|string|max:10',

            // Fields khusus SPMB
            'student_name' => $category === 'SPMB' ? 'required|string|max:255' : 'nullable|string|max:255',
            'student_birth_date' => 'nullable|date',
            'therapy_history' => 'nullable|string|in:Ya,Tidak',
            'therapy_diagnosis' => ($category === 'SPMB' && $request->input('therapy_history') === 'Ya') ? 'required|string|max:255' : 'nullable|string|max:255',
        ];

        $messages = [
            'name.required' => match($category) {
                'SPMB' => 'Nama Orang Tua / Wali wajib diisi.',
                'Dinas' => 'Nama Pejabat / Tamu wajib diisi.',
                'Puskesmas/Dinkes' => 'Nama Petugas Kesehatan wajib diisi.',
                default => 'Nama lengkap tamu wajib diisi.',
            },
            'phone.required' => 'Nomor Handphone / WhatsApp wajib diisi.',
            'category.required' => 'Kategori kunjungan wajib dipilih.',
            'category.in' => 'Kategori kunjungan yang dipilih tidak valid.',
            'date.required' => 'Tanggal kunjungan wajib diisi.',
            'date.date' => 'Format tanggal kunjungan tidak valid.',
            'student_name.required' => 'Nama calon murid wajib diisi untuk kategori SPMB.',
            'student_birth_date.date' => 'Format tanggal lahir calon murid tidak valid.',
            'therapy_diagnosis.required' => 'Diagnosa terapi wajib diisi jika anak pernah menjalani terapi.',
            'purpose.required' => match($category) {
                'Puskesmas/Dinkes' => 'Kegiatan pelayanan kesehatan wajib diisi.',
                'Dinas' => 'Keperluan / agenda kedinasan wajib diisi.',
                default => 'Keperluan kunjungan wajib diisi.',
            },
        ];

        $validated = $request->validate($rules, $messages);

        // Fallback waktu jika dikosongkan
        if (empty($validated['time'])) {
            $validated['time'] = Carbon::now()->format('H:i');
        }

        // Jika SPMB dan purpose kosong, isi otomatis agar konsisten
        if ($category === 'SPMB' && empty($validated['purpose'])) {
            $childInfo = !empty($validated['student_name']) ? " (Anak: {$validated['student_name']})" : "";
            $validated['purpose'] = "Konsultasi SPMB / Calon Murid{$childInfo}";
        }

        // Data hygiene berdasarkan kategori yang dipilih
        if ($category !== 'SPMB') {
            $validated['student_name'] = null;
            $validated['student_birth_date'] = null;
            $validated['therapy_history'] = null;
            $validated['therapy_diagnosis'] = null;
        } else {
            if (empty($validated['therapy_history'])) {
                $validated['therapy_history'] = 'Tidak';
            }
            if ($validated['therapy_history'] !== 'Ya') {
                $validated['therapy_diagnosis'] = null;
            }
        }

        return $validated;
    }

    /**
     * Download Excel Template for Guest Book import.
     */
    public function downloadTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Buku Tamu');

        $appName = strtoupper(setting('unit_name', setting('app_name', 'PAUD ANAK SALEH')));
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA BUKU TAMU');
        $sheet->setCellValue('A2', $appName);
        $sheet->setCellValue('A3', 'Petunjuk: Isi data tamu sesuai kolom di bawah. Kolom bertanda (*) Wajib diisi. Format tanggal: YYYY-MM-DD (Contoh: ' . Carbon::today()->format('Y-m-d') . ').');
        $sheet->setCellValue('A4', 'Pilihan Kategori: Umum | Dinas | Puskesmas/Dinkes | SPMB. Khusus SPMB, mohon lengkapi kolom Calon Murid.');

        $headers = [
            'No',
            'Tanggal (YYYY-MM-DD) *',
            'Jam (HH:MM)',
            'Nama Tamu / Orang Tua *',
            'No. WhatsApp / HP *',
            'Kategori (Umum/Dinas/Puskesmas/SPMB) *',
            'Instansi / Lembaga',
            'Jabatan / Pekerjaan',
            'Kota / Kabupaten',
            'Alamat Lengkap',
            'Keperluan / Agenda Kunjungan',
            'Pejabat / Guru Penerima',
            'Nama Calon Murid (Khusus SPMB)',
            'Tgl Lahir Murid (YYYY-MM-DD)',
            'Pernah Terapi (Ya/Tidak)',
            'Diagnosa Terapi',
            'Kesan & Pesan / Saran'
        ];

        $headerRow = 6;
        foreach ($headers as $colIndex => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue("{$colLetter}{$headerRow}", $headerText);
        }

        // Title Styling
        $sheet->mergeCells('A1:Q1');
        $sheet->mergeCells('A2:Q2');
        $sheet->mergeCells('A3:Q3');
        $sheet->mergeCells('A4:Q4');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('059669'));

        // Header Styling
        $headerRange = "A{$headerRow}:Q{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0284C7'], // Sky Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(30);

        // Sample Rows
        $sampleData = [
            [
                1,
                Carbon::today()->format('Y-m-d'),
                '08:30',
                'Budi Santoso, S.Pd',
                '081234567890',
                'Umum',
                'TK Pertiwi 1',
                'Guru',
                'Malang',
                'Jl. Soekarno Hatta No. 12',
                'Studi Banding Kurikulum Merdeka PAUD',
                'Ibu Kepala Sekolah',
                '',
                '',
                'Tidak',
                '',
                'Sambutan sangat ramah dan informatif'
            ],
            [
                2,
                Carbon::today()->format('Y-m-d'),
                '09:15',
                'Siti Rahmawati',
                '085612345678',
                'SPMB',
                '',
                'Wiraswasta',
                'Malang',
                'Perum Griyashanta Blok E/10',
                'Informasi Pendaftaran Murid Baru KB/TK',
                'Panitia SPMB',
                'Muhammad Rayyan',
                '2022-05-14',
                'Tidak',
                '',
                'Penjelasan program sekolah sangat jelas'
            ],
            [
                3,
                Carbon::today()->format('Y-m-d'),
                '10:00',
                'dr. Ahmad Faisal',
                '082198765432',
                'Puskesmas/Dinkes',
                'Puskesmas Mojolangu',
                'Dokter Pembina',
                'Malang',
                'Jl. Candi Panggung No. 4',
                'Pemeriksaan Berkala & Imunisasi Anak',
                'Tim UKS / Guru Kelas',
                '',
                '',
                'Tidak',
                '',
                'Koordinasi berjalan lancar'
            ],
        ];

        $dataStartRow = $headerRow + 1;
        $currentRow = $dataStartRow;
        foreach ($sampleData as $row) {
            foreach ($row as $colIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->setCellValueExplicit("{$colLetter}{$currentRow}", $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $sheet->getStyle("A{$currentRow}:Q{$currentRow}")->applyFromArray([
                'font' => ['size' => 10],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow++;
        }

        // Set column widths
        $columnWidths = [
            'A' => 6,   // No
            'B' => 18,  // Tanggal
            'C' => 12,  // Jam
            'D' => 26,  // Nama
            'E' => 18,  // No HP
            'F' => 18,  // Kategori
            'G' => 24,  // Instansi
            'H' => 20,  // Jabatan
            'I' => 16,  // Kota
            'J' => 28,  // Alamat
            'K' => 32,  // Keperluan
            'L' => 22,  // Penerima
            'M' => 24,  // Nama Murid
            'N' => 18,  // Tgl Lahir
            'O' => 14,  // Terapi
            'P' => 20,  // Diagnosa
            'Q' => 30,  // Catatan/Feedback
        ];

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $filename = "Template_Import_Buku_Tamu_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Import Guest Book from Excel file.
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau .csv.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        // Find header row
        $headerRowIdx = null;
        $colMap = [];

        foreach ($rows as $rIdx => $row) {
            $normalized = [];
            foreach ($row as $colKey => $cellVal) {
                $cleaned = strtolower(trim((string)$cellVal));
                $cleaned = preg_replace('/[^a-z0-9]/', '', $cleaned);
                $normalized[$colKey] = $cleaned;
            }

            $hasName = false;
            foreach ($normalized as $cKey => $val) {
                if (str_contains($val, 'nama') || str_contains($val, 'tamu') || $val === 'name') {
                    $hasName = true;
                    break;
                }
            }

            if ($hasName) {
                $headerRowIdx = $rIdx;
                foreach ($normalized as $cKey => $val) {
                    if (str_contains($val, 'tgl') || str_contains($val, 'tanggal') || str_contains($val, 'date')) {
                        if (str_contains($val, 'lahir') || str_contains($val, 'birth')) {
                            $colMap['student_birth_date'] = $cKey;
                        } else {
                            $colMap['date'] = $cKey;
                        }
                    } elseif (str_contains($val, 'jam') || str_contains($val, 'waktu') || str_contains($val, 'time')) {
                        $colMap['time'] = $cKey;
                    } elseif (str_contains($val, 'murid') || str_contains($val, 'calon') || str_contains($val, 'anak')) {
                        $colMap['student_name'] = $cKey;
                    } elseif (str_contains($val, 'nama') || str_contains($val, 'tamu')) {
                        if (!isset($colMap['name'])) $colMap['name'] = $cKey;
                    } elseif (str_contains($val, 'hp') || str_contains($val, 'wa') || str_contains($val, 'telepon') || str_contains($val, 'telp') || str_contains($val, 'phone')) {
                        $colMap['phone'] = $cKey;
                    } elseif (str_contains($val, 'kategori') || str_contains($val, 'category')) {
                        $colMap['category'] = $cKey;
                    } elseif (str_contains($val, 'instansi') || str_contains($val, 'lembaga') || str_contains($val, 'perusahaan') || str_contains($val, 'institution')) {
                        $colMap['institution'] = $cKey;
                    } elseif (str_contains($val, 'jabatan') || str_contains($val, 'pekerjaan') || str_contains($val, 'position') || str_contains($val, 'profesi')) {
                        $colMap['position'] = $cKey;
                    } elseif (str_contains($val, 'kota') || str_contains($val, 'kab') || str_contains($val, 'city')) {
                        $colMap['city'] = $cKey;
                    } elseif (str_contains($val, 'alamat') || str_contains($val, 'address')) {
                        $colMap['address'] = $cKey;
                    } elseif (str_contains($val, 'keperluan') || str_contains($val, 'agenda') || str_contains($val, 'tujuan') || str_contains($val, 'purpose')) {
                        $colMap['purpose'] = $cKey;
                    } elseif (str_contains($val, 'penerima') || str_contains($val, 'host') || str_contains($val, 'pejabat')) {
                        $colMap['host_recipient'] = $cKey;
                    } elseif (str_contains($val, 'diagnosa') || str_contains($val, 'diagnosis')) {
                        $colMap['therapy_diagnosis'] = $cKey;
                    } elseif (str_contains($val, 'terapi') || str_contains($val, 'therapy')) {
                        $colMap['therapy_history'] = $cKey;
                    } elseif (str_contains($val, 'kesan') || str_contains($val, 'pesan') || str_contains($val, 'saran') || str_contains($val, 'feedback') || str_contains($val, 'catatan')) {
                        $colMap['feedback'] = $cKey;
                    }
                }
                break;
            }
        }

        if (!$headerRowIdx || !isset($colMap['name'])) {
            return back()->with('error', 'Format header file Excel tidak dikenali. Pastikan terdapat kolom "Nama Tamu" dan kolom lainnya sesuai template.');
        }

        $userId = Auth::id() ?? 1;
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rIdx => $row) {
                if ($rIdx <= $headerRowIdx) continue;

                $name = trim((string)($row[$colMap['name']] ?? ''));
                if (empty($name)) {
                    continue; // Skip empty row
                }

                $rawDate = trim((string)($row[$colMap['date'] ?? ''] ?? ''));
                $parsedDate = Carbon::today()->format('Y-m-d');
                if (!empty($rawDate)) {
                    if (is_numeric($rawDate)) {
                        try {
                            $parsedDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                        } catch (\Throwable $e) {}
                    } else {
                        try {
                            $parsedDate = Carbon::parse($rawDate)->format('Y-m-d');
                        } catch (\Throwable $e) {}
                    }
                }

                $rawTime = trim((string)($row[$colMap['time'] ?? ''] ?? ''));
                $parsedTime = '08:00';
                if (!empty($rawTime)) {
                    if (is_numeric($rawTime)) {
                        try {
                            $parsedTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawTime)->format('H:i');
                        } catch (\Throwable $e) {}
                    } else {
                        try {
                            $parsedTime = Carbon::parse($rawTime)->format('H:i');
                        } catch (\Throwable $e) {
                            $parsedTime = substr($rawTime, 0, 5);
                        }
                    }
                }

                $phone = trim((string)($row[$colMap['phone'] ?? ''] ?? ''));
                if (empty($phone)) $phone = '-';

                $rawCat = trim((string)($row[$colMap['category'] ?? ''] ?? ''));
                $catNorm = strtolower($rawCat);
                if (str_contains($catNorm, 'spmb') || str_contains($catNorm, 'daftar')) {
                    $category = 'SPMB';
                } elseif (str_contains($catNorm, 'dinas')) {
                    $category = 'Dinas';
                } elseif (str_contains($catNorm, 'puskesmas') || str_contains($catNorm, 'dinkes') || str_contains($catNorm, 'kesehatan')) {
                    $category = 'Puskesmas/Dinkes';
                } else {
                    $category = 'Umum';
                }

                $institution = trim((string)($row[$colMap['institution'] ?? ''] ?? '')) ?: null;
                $position = trim((string)($row[$colMap['position'] ?? ''] ?? '')) ?: null;
                $city = trim((string)($row[$colMap['city'] ?? ''] ?? '')) ?: null;
                $address = trim((string)($row[$colMap['address'] ?? ''] ?? '')) ?: null;
                $host = trim((string)($row[$colMap['host_recipient'] ?? ''] ?? '')) ?: null;
                $feedback = trim((string)($row[$colMap['feedback'] ?? ''] ?? '')) ?: null;

                $studentName = trim((string)($row[$colMap['student_name'] ?? ''] ?? '')) ?: null;

                $rawBirth = trim((string)($row[$colMap['student_birth_date'] ?? ''] ?? ''));
                $birthDate = null;
                if (!empty($rawBirth)) {
                    if (is_numeric($rawBirth)) {
                        try {
                            $birthDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawBirth)->format('Y-m-d');
                        } catch (\Throwable $e) {}
                    } else {
                        try {
                            $birthDate = Carbon::parse($rawBirth)->format('Y-m-d');
                        } catch (\Throwable $e) {}
                    }
                }

                $rawTerapi = strtolower(trim((string)($row[$colMap['therapy_history'] ?? ''] ?? '')));
                $therapyHistory = (str_contains($rawTerapi, 'ya') || str_contains($rawTerapi, 'pernah') || $rawTerapi === 'y') ? 'Ya' : 'Tidak';
                $therapyDiagnosis = trim((string)($row[$colMap['therapy_diagnosis'] ?? ''] ?? '')) ?: null;

                $purpose = trim((string)($row[$colMap['purpose'] ?? ''] ?? ''));
                if (empty($purpose)) {
                    if ($category === 'SPMB') {
                        $purpose = 'Konsultasi SPMB / Calon Murid' . ($studentName ? " ({$studentName})" : '');
                    } elseif ($category === 'Puskesmas/Dinkes') {
                        $purpose = 'Pelayanan Kesehatan Anak / UKS';
                    } elseif ($category === 'Dinas') {
                        $purpose = 'Kunjungan Dinas Pendidikan';
                    } else {
                        $purpose = 'Kunjungan Tamu';
                    }
                }

                GuestBook::create([
                    'date' => $parsedDate,
                    'time' => $parsedTime,
                    'name' => $name,
                    'phone' => $phone,
                    'category' => $category,
                    'institution' => $institution,
                    'position' => $position,
                    'city' => $city,
                    'address' => $address,
                    'purpose' => $purpose,
                    'host_recipient' => $host,
                    'student_name' => $studentName,
                    'student_birth_date' => $birthDate,
                    'therapy_history' => $therapyHistory,
                    'therapy_diagnosis' => ($therapyHistory === 'Ya' ? $therapyDiagnosis : null),
                    'feedback' => $feedback,
                    'recorded_by' => $userId,
                ]);

                $successCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses file import: ' . $e->getMessage());
        }

        $message = "Berhasil mengimpor {$successCount} data tamu ke dalam Buku Tamu.";
        return redirect()->route('guest-books.index', [
            'month' => Carbon::parse($parsedDate ?? now())->month,
            'year' => Carbon::parse($parsedDate ?? now())->year,
        ])->with('success', $message);
    }
}
