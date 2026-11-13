<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\TeacherDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TeacherDocumentController extends Controller
{
    /**
     * Categories of Teacher Documents.
     */
    private array $categories = ['PROTA', 'PROSEM', 'RPP', 'RKAS', 'LPJ', 'Lainnya'];

    /**
     * Check if authenticated user has admin-level access (Super Admin, Admin PAUD, Kepala Sekolah, Waka).
     */
    private function isUserAdmin(): bool
    {
        return auth()->user() && (
            auth()->user()->isAdmin() || 
            auth()->user()->hasAnyRole(['super_admin', 'admin_sd', 'admin_paud', 'admin_smp', 'kepala_sekolah', 'waka'])
        );
    }

    /**
     * Check if authenticated user is a teacher / tenaga pendidik.
     */
    private function isTeacher(): bool
    {
        return auth()->user()?->isTeacher() ?? false;
    }

    /**
     * Get the authenticated user's associated Employee ID if any.
     */
    private function getMyEmployeeId(): ?int
    {
        return auth()->user()?->employee_id 
            ?? auth()->user()?->employee?->id 
            ?? Employee::where('email', auth()->user()?->email)->value('id');
    }

    /**
     * Get list of active teachers and staff who contribute to teacher documents (including Nuki Maya Sulistiyowati).
     */
    private function getActiveTeachers()
    {
        $teacherTypeId = EmployeeType::where('code', 'teacher')->value('id');
        $teachers = Employee::where('status', 'Active')
            ->where(function ($sq) use ($teacherTypeId) {
                if ($teacherTypeId) {
                    $sq->where('employee_type_id', $teacherTypeId);
                }
                $sq->orWhere('position', 'like', '%guru%')
                   ->orWhere('position', 'like', '%wali%')
                   ->orWhere('position', 'like', '%pendidik%')
                   ->orWhereHas('teacherDocuments')
                   ->orWhere('id', 22);
            })
            ->orderBy('name', 'asc')
            ->get();

        if ($teachers->isEmpty()) {
            $teachers = Employee::where('status', 'Active')->orderBy('name', 'asc')->get();
        }

        return $teachers;
    }

    /**
     * Display a listing of Teacher Documents (Tab 1) or Completeness Matrix (Tab 2).
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'files'); // 'files' or 'matrix'

        // Check if user is Admin, Principal, or Teacher
        $isAdmin = $this->isUserAdmin();
        $isTeacher = $this->isTeacher();

        // Hanya Administrator / Kepala Sekolah atau Guru yang berhak mengakses menu ini
        if (!$isAdmin && !$isTeacher) {
            abort(403, 'Akses ditolak: Menu Berkas Guru hanya dapat diakses oleh Guru / Tenaga Pendidik dan Administrator.');
        }

        $myEmployeeId = $this->getMyEmployeeId();
        $userId = auth()->id();

        // Academic Years
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        if (!$selectedYearId) {
            $selectedYearId = $activeAcademicYear?->id;
        }

        // Active Teachers
        $teachers = $this->getActiveTeachers();

        // Filter variables: Guru hanya bisa melihat berkas miliknya sendiri
        $selectedTeacherId = $isAdmin ? $request->get('teacher_id', 'all') : ($myEmployeeId ?: -1);
        $selectedCategory = $request->get('category', 'all');
        $selectedSemester = $request->get('semester', 'all');
        $selectedStatus = $request->get('status', 'all');
        $search = $request->get('search');

        // Query Documents
        $query = TeacherDocument::with(['teacher', 'academicYear', 'uploadedBy', 'reviewedBy']);

        if (!$isAdmin) {
            // Guru HANYA bisa melihat berkas yang mereka buat sendiri
            $query->where(function ($q) use ($userId, $myEmployeeId) {
                $q->where('uploaded_by', $userId);
                if ($myEmployeeId) {
                    $q->orWhere('teacher_id', $myEmployeeId);
                }
            });
        } elseif ($selectedTeacherId && $selectedTeacherId !== 'all') {
            $query->where('teacher_id', $selectedTeacherId);
        }

        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
        }

        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }

        if ($selectedSemester && $selectedSemester !== 'all') {
            $query->where('semester', $selectedSemester);
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('teacher', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = $request->get('per_page', 15);
        $perPageCount = ($perPage === 'all' || $perPage == 99999 || $perPage === '99999') ? 99999 : (int) $perPage;
        if ($perPageCount <= 0) {
            $perPageCount = 15;
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($perPageCount)
            ->withQueryString();

        // Overall Stats for selected academic year
        $allYearQuery = TeacherDocument::when($selectedYearId && $selectedYearId !== 'all', function ($q) use ($selectedYearId) {
            $q->where('academic_year_id', $selectedYearId);
        });

        if (!$isAdmin) {
            $allYearQuery->where(function ($q) use ($userId, $myEmployeeId) {
                $q->where('uploaded_by', $userId);
                if ($myEmployeeId) {
                    $q->orWhere('teacher_id', $myEmployeeId);
                }
            });
        }

        $allYearDocs = $allYearQuery->get();

        $stats = [
            'total' => $allYearDocs->count(),
            'pending' => $allYearDocs->where('status', 'Menunggu Review')->count(),
            'approved' => $allYearDocs->where('status', 'Disetujui')->count(),
            'needs_revision' => $allYearDocs->where('status', 'Perlu Revisi')->count(),
            'teachers_count' => $allYearDocs->pluck('teacher_id')->unique()->count(),
        ];

        // Jika Guru (bukan admin), hanya tampilkan dirinya sendiri pada opsi guru / matriks
        if (!$isAdmin) {
            $teachers = $teachers->where('id', $myEmployeeId);
        }

        // Completeness Matrix Data for Tab 2
        $matrixData = [];
        foreach ($teachers as $teacher) {
            $teacherDocs = $allYearDocs->where('teacher_id', $teacher->id);
            $categoriesStatus = [];

            foreach ($this->categories as $cat) {
                $docsInCat = $teacherDocs->where('category', $cat);
                $categoriesStatus[$cat] = [
                    'count' => $docsInCat->count(),
                    'approved' => $docsInCat->where('status', 'Disetujui')->count(),
                    'pending' => $docsInCat->where('status', 'Menunggu Review')->count(),
                    'needs_revision' => $docsInCat->where('status', 'Perlu Revisi')->count(),
                    'latest' => $docsInCat->sortByDesc('created_at')->first(),
                ];
            }

            $matrixData[] = [
                'teacher' => $teacher,
                'total_uploaded' => $teacherDocs->count(),
                'categories' => $categoriesStatus,
            ];
        }

        $categories = $this->categories;
        $semesters = ['Tahunan', 'Ganjil', 'Genap'];

        return view('admin.teacher-documents.index', compact(
            'documents',
            'matrixData',
            'activeTab',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'teachers',
            'selectedTeacherId',
            'selectedCategory',
            'selectedSemester',
            'selectedStatus',
            'search',
            'stats',
            'categories',
            'semesters',
            'isAdmin',
            'myEmployeeId',
            'perPage'
        ));
    }

    /**
     * Store newly uploaded Teacher Document (1 berkas record can contain multiple attached documents).
     */
    public function store(Request $request)
    {
        $isAdmin = $this->isUserAdmin();
        $isTeacher = $this->isTeacher();

        if (!$isAdmin && !$isTeacher) {
            abort(403, 'Akses ditolak: Hanya Guru atau Administrator yang dapat mengunggah berkas.');
        }

        $myEmployeeId = $this->getMyEmployeeId();

        $hasMultiDocs = $request->has('documents') && is_array($request->input('documents'));

        $rules = [
            'teacher_id' => $isAdmin ? 'required|exists:employees,id' : 'nullable|exists:employees,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester' => 'required|in:Tahunan,Ganjil,Genap',
            'category' => 'required|in:RKAS,LPJ,PROTA,PROSEM,RPP,Lainnya',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];

        if ($hasMultiDocs) {
            $rules['documents'] = 'required|array|min:1';
            $rules['documents.*.title'] = 'nullable|string|max:255';
            $rules['documents.*.file'] = 'nullable|file|max:25600|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png';
        } else {
            $rules['file'] = 'nullable|file|max:25600|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png';
        }

        $validated = $request->validate($rules);

        $teacherId = $isAdmin ? $validated['teacher_id'] : $myEmployeeId;
        if (!$teacherId) {
            return redirect()->back()->with('error', 'Akun Anda belum terhubung dengan data pegawai/guru.');
        }

        $storedFiles = [];
        $firstTitle = '';

        if ($hasMultiDocs) {
            foreach ($request->input('documents', []) as $index => $docData) {
                $file = $request->file("documents.{$index}.file");
                if (!$file) {
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension());
                $size = $file->getSize();
                $path = $file->store('teacher-documents', 'public');

                $rawTitle = $docData['title'] ?? null;
                $itemTitle = !empty(trim($rawTitle ?? '')) 
                    ? trim($rawTitle) 
                    : pathinfo($originalName, PATHINFO_FILENAME);

                if (empty($firstTitle)) {
                    $firstTitle = $itemTitle;
                }

                $storedFiles[] = [
                    'title' => $itemTitle,
                    'file_path' => $path,
                    'file_name' => $originalName,
                    'file_size' => $size,
                    'file_extension' => $ext,
                ];
            }
        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            $size = $file->getSize();
            $path = $file->store('teacher-documents', 'public');
            $itemTitle = !empty(trim($validated['title'] ?? '')) 
                ? trim($validated['title']) 
                : pathinfo($originalName, PATHINFO_FILENAME);
            $firstTitle = $itemTitle;

            $storedFiles[] = [
                'title' => $itemTitle,
                'file_path' => $path,
                'file_name' => $originalName,
                'file_size' => $size,
                'file_extension' => $ext,
            ];
        }

        if (empty($storedFiles)) {
            return redirect()->back()->with('error', 'Harap lampirkan minimal 1 file dokumen yang valid.');
        }

        // Main Berkas title
        $mainTitle = !empty(trim($request->input('title', ''))) 
            ? trim($request->input('title')) 
            : $firstTitle;

        $firstFile = $storedFiles[0];

        // SIMPAN HANYA 1 DATA BERKAS (bukan buat baris baru untuk setiap dokumen)
        TeacherDocument::create([
            'teacher_id' => $teacherId,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'semester' => $validated['semester'],
            'category' => $validated['category'],
            'title' => $mainTitle,
            'files' => $storedFiles,
            'file_path' => $firstFile['file_path'],
            'file_name' => $firstFile['file_name'],
            'file_size' => $firstFile['file_size'],
            'file_extension' => $firstFile['file_extension'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'Menunggu Review',
            'uploaded_by' => auth()->id(),
        ]);

        $fileCount = count($storedFiles);
        $msg = $fileCount > 1 
            ? "1 berkas \"{$mainTitle}\" dengan {$fileCount} dokumen berhasil diunggah."
            : "Berkas \"{$mainTitle}\" berhasil diunggah.";

        return redirect()->route('teacher-documents.index', [
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'category' => $validated['category'],
        ])->with('success', $msg);
    }

    /**
     * Update existing document metadata, manage documents (add, edit title/file, remove).
     */
    public function update(Request $request, $id)
    {
        $doc = TeacherDocument::findOrFail($id);
        $isAdmin = $this->isUserAdmin();
        $myEmployeeId = $this->getMyEmployeeId();

        // Guru hanya boleh mengedit berkas miliknya sendiri
        if (!$isAdmin && $doc->uploaded_by != auth()->id() && $doc->teacher_id != $myEmployeeId) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengubah berkas yang Anda buat sendiri.');
        }

        $rules = [
            'teacher_id' => $isAdmin ? 'required|exists:employees,id' : 'nullable|exists:employees,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester' => 'required|in:Tahunan,Ganjil,Genap',
            'category' => 'required|in:RKAS,LPJ,PROTA,PROSEM,RPP,Lainnya',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ];

        $hasMultiDocs = $request->has('documents') && is_array($request->input('documents'));
        if ($hasMultiDocs) {
            $rules['documents'] = 'required|array|min:1';
            $rules['documents.*.title'] = 'nullable|string|max:255';
            $rules['documents.*.file'] = 'nullable|file|max:25600|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png';
            $rules['documents.*.existing_path'] = 'nullable|string';
        } else {
            $rules['file'] = 'nullable|file|max:25600|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png';
        }

        $validated = $request->validate($rules);

        // Previous stored paths in this document
        $oldPaths = array_filter(array_column($doc->files_list, 'file_path'));

        $newStoredFiles = [];
        $keptPaths = [];
        $hasNewUpload = false;

        if ($hasMultiDocs) {
            foreach ($request->input('documents', []) as $index => $docData) {
                $hasFile = $request->hasFile("documents.{$index}.file");
                $existingPath = $docData['existing_path'] ?? null;
                $rawTitle = $docData['title'] ?? null;

                if ($hasFile) {
                    // New or replaced file uploaded
                    $file = $request->file("documents.{$index}.file");
                    $originalName = $file->getClientOriginalName();
                    $ext = strtolower($file->getClientOriginalExtension());
                    $size = $file->getSize();
                    $path = $file->store('teacher-documents', 'public');
                    $hasNewUpload = true;

                    $itemTitle = !empty(trim($rawTitle ?? '')) 
                        ? trim($rawTitle) 
                        : pathinfo($originalName, PATHINFO_FILENAME);

                    $newStoredFiles[] = [
                        'title' => $itemTitle,
                        'file_path' => $path,
                        'file_name' => $originalName,
                        'file_size' => $size,
                        'file_extension' => $ext,
                    ];
                } elseif (!empty($existingPath)) {
                    // Retain existing file, possibly with updated title
                    $itemTitle = !empty(trim($rawTitle ?? '')) 
                        ? trim($rawTitle) 
                        : ($docData['existing_name'] ?? basename($existingPath));

                    $newStoredFiles[] = [
                        'title' => $itemTitle,
                        'file_path' => $existingPath,
                        'file_name' => $docData['existing_name'] ?? basename($existingPath),
                        'file_size' => (int) ($docData['existing_size'] ?? 0),
                        'file_extension' => $docData['existing_ext'] ?? pathinfo($existingPath, PATHINFO_EXTENSION),
                    ];

                    $keptPaths[] = $existingPath;
                }
            }
        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $newPath = $file->store('teacher-documents', 'public');
            $originalName = $file->getClientOriginalName();
            $size = $file->getSize();
            $ext = strtolower($file->getClientOriginalExtension());
            $hasNewUpload = true;

            $newStoredFiles[] = [
                'title' => $validated['title'],
                'file_path' => $newPath,
                'file_name' => $originalName,
                'file_size' => $size,
                'file_extension' => $ext,
            ];
        } else {
            $newStoredFiles = $doc->files_list;
            $keptPaths = $oldPaths;
        }

        if (empty($newStoredFiles)) {
            return redirect()->back()->with('error', 'Berkas harus memiliki minimal 1 file dokumen.');
        }

        // Delete removed old files from disk
        foreach ($oldPaths as $oldPath) {
            if (!in_array($oldPath, $keptPaths)) {
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $teacherId = $isAdmin ? $validated['teacher_id'] : $doc->teacher_id;
        $firstFile = $newStoredFiles[0];

        $updateData = [
            'teacher_id' => $teacherId,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'semester' => $validated['semester'],
            'category' => $validated['category'],
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'files' => $newStoredFiles,
            'file_path' => $firstFile['file_path'],
            'file_name' => $firstFile['file_name'],
            'file_size' => $firstFile['file_size'],
            'file_extension' => $firstFile['file_extension'],
        ];

        // Reset review status ke 'Menunggu Review' jika berkas sebelumnya berstatus 'Perlu Revisi'
        // atau jika terdapat unggahan / perubahan file dokumen baru.
        $wasNeedsRevision = ($doc->status === 'Perlu Revisi');
        if ($wasNeedsRevision || $hasNewUpload) {
            $updateData['status'] = 'Menunggu Review';
        }

        $doc->update($updateData);

        $msg = $wasNeedsRevision
            ? "Revisi berkas \"{$doc->title}\" berhasil disimpan dan status dikembalikan ke Menunggu Review."
            : "Data berkas \"{$doc->title}\" berhasil diperbarui.";

        return redirect()->route('teacher-documents.index', [
            'academic_year_id' => $doc->academic_year_id,
            'category' => $doc->category,
        ])->with('success', $msg);
    }

    /**
     * Update Document Review Status (Approved / Needs Revision).
     */
    public function updateStatus(Request $request, $id)
    {
        $isAdmin = $this->isUserAdmin();
        if (!$isAdmin) {
            abort(403, 'Akses ditolak: Hanya Administrator atau Kepala Sekolah yang dapat melakukan supervisi berkas.');
        }

        $doc = TeacherDocument::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:Menunggu Review,Disetujui,Perlu Revisi',
            'feedback' => 'nullable|string',
        ]);

        $doc->update([
            'status' => $validated['status'],
            'feedback' => $validated['feedback'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', "Status berkas \"{$doc->title}\" berhasil diubah menjadi {$validated['status']}.");
    }

    /**
     * Download the document file (or a specific file by index).
     */
    public function download(Request $request, $id)
    {
        $doc = TeacherDocument::findOrFail($id);
        $isAdmin = $this->isUserAdmin();
        $myEmployeeId = $this->getMyEmployeeId();

        // Guru hanya boleh mengunduh berkas miliknya sendiri
        if (!$isAdmin && $doc->uploaded_by != auth()->id() && $doc->teacher_id != $myEmployeeId) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengunduh berkas yang Anda buat sendiri.');
        }

        $fileIndex = $request->query('file');
        $filesList = $doc->files_list;

        if ($fileIndex !== null && is_numeric($fileIndex) && isset($filesList[(int)$fileIndex])) {
            $target = $filesList[(int)$fileIndex];
            $path = $target['file_path'] ?? '';
            $name = $target['file_name'] ?? basename($path);
        } else {
            $target = $filesList[0] ?? null;
            $path = $target ? ($target['file_path'] ?? '') : $doc->file_path;
            $name = $target ? ($target['file_name'] ?? basename($path)) : ($doc->file_name ?? basename($path));
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File berkas tidak ditemukan di server penyimpanan.');
        }

        if ($request->filled('download_token')) {
            if (!headers_sent()) {
                @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
            }
        }

        $response = Storage::disk('public')->download($path, $name);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Preview or stream file inline for in-browser viewing.
     */
    public function preview(Request $request, $id)
    {
        $doc = TeacherDocument::findOrFail($id);
        $isAdmin = $this->isUserAdmin();
        $myEmployeeId = $this->getMyEmployeeId();

        // Guru hanya boleh melihat berkas miliknya sendiri
        if (!$isAdmin && $doc->uploaded_by != auth()->id() && $doc->teacher_id != $myEmployeeId) {
            abort(403, 'Akses ditolak: Anda hanya dapat melihat berkas yang Anda buat sendiri.');
        }

        $fileIndex = $request->query('file');
        $filesList = $doc->files_list;

        if ($fileIndex !== null && is_numeric($fileIndex) && isset($filesList[(int)$fileIndex])) {
            $target = $filesList[(int)$fileIndex];
            $path = $target['file_path'] ?? '';
            $name = $target['file_name'] ?? basename($path);
        } else {
            $target = $filesList[0] ?? null;
            $path = $target ? ($target['file_path'] ?? '') : $doc->file_path;
            $name = $target ? ($target['file_name'] ?? basename($path)) : ($doc->file_name ?? basename($path));
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File berkas tidak ditemukan di server penyimpanan.');
        }

        return Storage::disk('public')->response($path, $name);
    }

    /**
     * Download Excel template for importing teacher documents.
     */
    public function downloadTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Berkas Guru');
        $sheet->setShowGridLines(true);

        // Header Titles
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT BERKAS DOKUMEN GURU');
        $sheet->setCellValue('A2', 'PAUD TERPADU ANAK SALEH MALANG');
        $sheet->setCellValue('A3', 'Petunjuk: Kolom NIP / NIY, Nama Guru, Jenis Berkas, dan Keterangan wajib diisi. Jenis Berkas: RKAS, PROTA, PROSEM, RPP, LPJ, atau Lainnya. File berkas dapat dikosongkan dan diunggah kemudian melalui menu edit.');

        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setName('Arial')->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF581C87'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setName('Arial')->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->setName('Arial')->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

        // Table Header
        $headers = [
            'A5' => 'No',
            'B5' => 'NIP / NIY Guru',
            'C5' => 'Nama Guru',
            'D5' => 'Jenis Berkas (RKAS/PROTA/PROSEM/RPP/LPJ/Lainnya)',
            'E5' => 'Keterangan / Judul Berkas',
            'F5' => 'Semester (Tahunan/Ganjil/Genap)',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7C3AED']], // Purple-600
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF94A3B8']]]
        ];
        $sheet->getStyle('A5:F5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Sample Data
        $sampleData = [
            [1, '0796161379', 'Linda Romadhona, S.Pd', 'RKAS', 'Pengajuan Anggaran KB A 26/27', 'Tahunan'],
            [2, '0796001308', 'Novita Rokhmawati, S.Pd, S.Pd', 'RPP', 'Minggu Ke-5 Semester Gasal (Sub Topik Ekpresi Hebatku)', 'Ganjil'],
            [3, '0796001308', 'Novita Rokhmawati, S.Pd, S.Pd', 'PROTA', 'Program Tahunan TK B Tapel 2026/2027', 'Tahunan'],
            [4, '0796981304', 'Endriyanti Kumalasari, S.P, S.Pd', 'RKAS', 'Program dan RKAS UKS TA. 2026/2027', 'Tahunan'],
            [5, '0796141358', 'Lilis Rachmawati, S.Psi', 'Lainnya', 'Proposal Precious Moment 26/27', 'Tahunan'],
        ];

        $rowIdx = 6;
        foreach ($sampleData as $row) {
            $sheet->setCellValue('A' . $rowIdx, $row[0]);
            $sheet->setCellValueExplicit('B' . $rowIdx, (string)$row[1], DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $rowIdx, $row[2]);
            $sheet->setCellValue('D' . $rowIdx, $row[3]);
            $sheet->setCellValue('E' . $rowIdx, $row[4]);
            $sheet->setCellValue('F' . $rowIdx, $row[5]);

            $sheet->getStyle('A' . $rowIdx . ':F' . $rowIdx)->applyFromArray([
                'font' => ['size' => 9.5, 'name' => 'Arial'],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);

            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rowIdx++;
        }

        // Auto column widths
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Template_Import_Berkas_Guru.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import Teacher Documents from Excel file.
     */
    public function importExcel(Request $request)
    {
        @ini_set('max_execution_time', 300);
        @set_time_limit(300);

        if (!$this->isUserAdmin()) {
            abort(403, 'Hanya Administrator yang memiliki akses untuk mengimpor berkas guru.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:15360',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'default_semester' => 'nullable|in:Tahunan,Ganjil,Genap',
            'default_status' => 'nullable|in:Disetujui,Menunggu Review,Perlu Revisi',
            'import_month' => 'nullable|integer|between:1,12',
            'import_year' => 'nullable|integer',
        ], [
            'file.required' => 'Silakan pilih berkas Excel (.xlsx / .xls) untuk diimpor.',
            'file.mimes' => 'Format berkas harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran berkas tidak boleh lebih dari 15 MB.',
        ]);

        try {
            $uploadedFile = $request->file('file');
            $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();

            if ($highestRow < 2) {
                return back()->withErrors(['file' => 'Berkas Excel kosong atau tidak memiliki baris data.']);
            }

            // Academic Year & Semester
            $academicYears = AcademicYear::all();
            $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
            $targetAcademicYearId = $request->academic_year_id ?: ($activeAcademicYear?->id ?? null);
            $defaultSemester = $request->default_semester ?: 'Tahunan';
            $defaultStatus = $request->default_status ?: 'Disetujui';

            // User requested: created_at in October (month 10) so it sits below newer November data
            $importMonth = (int) ($request->import_month ?: 10);
            $importYear = (int) ($request->import_year ?: 2026);

            // Pre-load all employees and build lookup maps
            $employees = Employee::all();
            $empByNiy = [];
            $empByNik = [];
            $empByName = [];

            $normalizeName = function ($str) {
                $str = preg_replace('/,?\s*(S\.Pd|S\.Psi|S\.Ag|S\.P|M\.Pd|M\.Psi|M\.Ag|S\.Kom|S\.E)\.?/i', '', $str);
                $str = preg_replace('/[^a-zA-Z0-9\s]/', '', $str);
                return strtolower(trim(preg_replace('/\s+/', ' ', $str)));
            };

            foreach ($employees as $emp) {
                if (!empty($emp->niy)) {
                    $empByNiy[trim((string)$emp->niy)] = $emp;
                    $empByNiy[ltrim(trim((string)$emp->niy), '0')] = $emp;
                }
                if (!empty($emp->nik)) {
                    $empByNik[trim((string)$emp->nik)] = $emp;
                }
                $norm = $normalizeName($emp->name);
                $empByName[$norm] = $emp;
            }

            // Detect Header Row and Column Mapping
            $colMap = [
                'nip' => null,
                'nama_guru' => null,
                'keterangan' => null,
                'jenis_berkas' => null,
                'semester' => null,
            ];

            $headerRowIndex = 1;
            for ($r = 1; $r <= min(10, $highestRow); $r++) {
                $rowLetters = range('A', $highestCol);
                $rowMap = [
                    'nip' => null,
                    'nama_guru' => null,
                    'keterangan' => null,
                    'jenis_berkas' => null,
                    'semester' => null,
                ];

                foreach ($rowLetters as $col) {
                    $val = strtolower(trim((string)$sheet->getCell($col . $r)->getValue()));
                    if (strlen($val) > 50) continue;

                    if ($val === 'nip' || $val === 'niy' || str_contains($val, 'nip') || str_contains($val, 'niy')) {
                        $rowMap['nip'] = $col;
                    } elseif (str_contains($val, 'nama') || str_contains($val, 'guru') || str_contains($val, 'pendidik')) {
                        $rowMap['nama_guru'] = $col;
                    } elseif (str_contains($val, 'jenis') || str_contains($val, 'kategori') || str_contains($val, 'category')) {
                        $rowMap['jenis_berkas'] = $col;
                    } elseif (str_contains($val, 'keterangan') || str_contains($val, 'judul') || str_contains($val, 'title') || str_contains($val, 'deskripsi') || str_contains($val, 'uraian')) {
                        $rowMap['keterangan'] = $col;
                    } elseif (str_contains($val, 'semester') || str_contains($val, 'smt')) {
                        $rowMap['semester'] = $col;
                    }
                }

                if ($rowMap['jenis_berkas'] !== null && ($rowMap['keterangan'] !== null || $rowMap['nama_guru'] !== null || $rowMap['nip'] !== null)) {
                    $colMap = $rowMap;
                    $headerRowIndex = $r;
                    break;
                }
            }

            // Fallback if headers not detected
            if ($colMap['jenis_berkas'] === null && $colMap['keterangan'] === null) {
                // Default positional map for BERKAS-GURU.xlsx (A: nip, B: nama_guru, C: keterangan, D: jenis_berkas)
                $colMap = [
                    'nip' => 'A',
                    'nama_guru' => 'B',
                    'keterangan' => 'C',
                    'jenis_berkas' => 'D',
                    'semester' => null,
                ];
                $headerRowIndex = 1;
            }

            $importedCount = 0;
            $skippedCount = 0;
            $warnings = [];

            // Pre-load existing documents to prevent duplicate exact same title for same teacher
            $existingDocs = TeacherDocument::where('academic_year_id', $targetAcademicYearId)->get();
            $existingKeys = [];
            foreach ($existingDocs as $ed) {
                $existingKeys[$ed->teacher_id . '_' . strtoupper(trim($ed->category)) . '_' . strtolower(trim($ed->title))] = true;
            }

            $userId = auth()->id();
            $baseTimestamp = Carbon::create($importYear, $importMonth, 15, 8, 30, 0);

            for ($row = $headerRowIndex + 1; $row <= $highestRow; $row++) {
                $nipVal = $colMap['nip'] ? trim((string)$sheet->getCell($colMap['nip'] . $row)->getValue()) : '';
                $nameVal = $colMap['nama_guru'] ? trim((string)$sheet->getCell($colMap['nama_guru'] . $row)->getValue()) : '';
                $descVal = $colMap['keterangan'] ? trim((string)$sheet->getCell($colMap['keterangan'] . $row)->getValue()) : '';
                $categoryVal = $colMap['jenis_berkas'] ? trim((string)$sheet->getCell($colMap['jenis_berkas'] . $row)->getValue()) : '';
                $semVal = $colMap['semester'] ? trim((string)$sheet->getCell($colMap['semester'] . $row)->getValue()) : '';

                // Skip completely empty rows
                if (empty($nipVal) && empty($nameVal) && empty($descVal) && empty($categoryVal)) {
                    continue;
                }

                // Match Teacher
                $cleanNip = ltrim($nipVal, '0');
                $teacher = $empByNiy[$nipVal] ?? ($empByNiy[$cleanNip] ?? ($empByNik[$nipVal] ?? null));

                if (!$teacher && !empty($nameVal)) {
                    $norm = $normalizeName($nameVal);
                    $teacher = $empByName[$norm] ?? null;

                    if (!$teacher) {
                        foreach ($empByName as $eNorm => $eObj) {
                            if (str_contains($eNorm, $norm) || str_contains($norm, $eNorm)) {
                                $teacher = $eObj;
                                break;
                            }
                        }
                    }
                }

                if (!$teacher) {
                    $skippedCount++;
                    $warnings[] = "Baris {$row}: Guru [{$nameVal} / {$nipVal}] tidak ditemukan di database.";
                    continue;
                }

                // Normalize Category
                $upperCat = strtoupper(trim($categoryVal));
                $validCategories = ['RPP', 'PROTA', 'PROSEM', 'RKAS', 'LPJ', 'LAINNYA'];
                $category = in_array($upperCat, $validCategories) ? ($upperCat === 'LAINNYA' ? 'Lainnya' : $upperCat) : 'Lainnya';

                // Determine Title
                $title = trim($descVal);
                if (empty($title)) {
                    $title = $category . ' - ' . $teacher->name;
                }

                // Determine Semester
                $semNormalized = $defaultSemester;
                if (!empty($semVal)) {
                    $lowerSem = strtolower($semVal);
                    if (str_contains($lowerSem, 'ganjil') || str_contains($lowerSem, 'gasal') || str_contains($lowerSem, '1')) {
                        $semNormalized = 'Ganjil';
                    } elseif (str_contains($lowerSem, 'genap') || str_contains($lowerSem, '2')) {
                        $semNormalized = 'Genap';
                    } else {
                        $semNormalized = 'Tahunan';
                    }
                } elseif (str_contains(strtolower($title), 'gasal') || str_contains(strtolower($title), 'ganjil')) {
                    $semNormalized = 'Ganjil';
                } elseif (str_contains(strtolower($title), 'genap')) {
                    $semNormalized = 'Genap';
                }

                // Check duplicate
                $dupKey = $teacher->id . '_' . strtoupper($category) . '_' . strtolower($title);
                if (isset($existingKeys[$dupKey])) {
                    $skippedCount++;
                    continue;
                }

                // Timestamp in October 2026 (staggered by 2 mins to preserve order)
                $createdAt = (clone $baseTimestamp)->addMinutes($importedCount * 2);

                $doc = new TeacherDocument();
                $doc->teacher_id = $teacher->id;
                $doc->academic_year_id = $targetAcademicYearId;
                $doc->semester = $semNormalized;
                $doc->category = $category;
                $doc->title = $title;
                $doc->notes = $descVal ?: null;
                $doc->status = $defaultStatus;
                $doc->uploaded_by = $userId;
                $doc->files = null;
                $doc->file_path = null;
                $doc->file_name = null;
                $doc->file_size = null;
                $doc->file_extension = null;
                $doc->created_at = $createdAt;
                $doc->updated_at = $createdAt;
                $doc->save();

                $existingKeys[$dupKey] = true;
                $importedCount++;
            }

            $message = "Berhasil mengimpor {$importedCount} data berkas guru (dengan tanggal arsip {$importMonth}/{$importYear}).";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} baris dilewati karena duplikat atau guru tidak cocok).";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal memproses berkas Excel: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete document and all its attached physical files.
     */
    public function destroy($id)
    {
        $doc = TeacherDocument::findOrFail($id);
        $isAdmin = $this->isUserAdmin();
        $myEmployeeId = $this->getMyEmployeeId();

        // Guru hanya boleh menghapus berkas miliknya sendiri
        if (!$isAdmin && $doc->uploaded_by != auth()->id() && $doc->teacher_id != $myEmployeeId) {
            abort(403, 'Akses ditolak: Anda hanya dapat menghapus berkas yang Anda buat sendiri.');
        }

        // Delete all attached files in storage
        foreach ($doc->files_list as $f) {
            if (!empty($f['file_path']) && Storage::disk('public')->exists($f['file_path'])) {
                Storage::disk('public')->delete($f['file_path']);
            }
        }

        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->delete();

        return redirect()->back()->with('success', 'Catatan dan file berkas guru berhasil dihapus.');
    }

    /**
     * Export Completeness Matrix to Excel.
     */
    public function exportMatrixExcel(Request $request)
    {
        if (!$this->isUserAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator atau Kepala Sekolah yang dapat mengekspor matriks.');
        }

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);

        $selectedYear = AcademicYear::find($selectedYearId);
        $yearName = $selectedYear ? $selectedYear->name : 'Aktif';

        $teachers = $this->getActiveTeachers();

        $allYearDocs = TeacherDocument::when($selectedYearId, function ($q) use ($selectedYearId) {
            $q->where('academic_year_id', $selectedYearId);
        })->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Matriks Berkas Guru');

        // Header Title
        $sheet->setCellValue('A1', 'MATRIKS KELENGKAPAN BERKAS & ADMINISTRASI GURU');
        $sheet->setCellValue('A2', strtoupper(setting('app_name', 'PAUD ANAK SALEH MALANG')));
        $sheet->setCellValue('A3', "Tahun Ajaran: {$yearName} | Tanggal Cetak: " . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9.5)->getColor()->setARGB('FF475569');

        // Headers
        $headers = ['No', 'Nama Guru / Pendidik', 'NIP / NIY', 'Jabatan', 'PROTA', 'PROSEM', 'RPP', 'RKAS', 'LPJ', 'Lainnya', 'Total Berkas'];
        $startRow = 5;

        foreach ($headers as $colIdx => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue($colLetter . $startRow, $h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5'); // Indigo 600
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        $currentRow = $startRow + 1;
        $no = 1;

        foreach ($teachers as $t) {
            $teacherDocs = $allYearDocs->where('teacher_id', $t->id);
            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $t->name);
            $sheet->setCellValueExplicit("C{$currentRow}", $t->niy ?: ($t->nik ?: '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$currentRow}", $t->position ?: 'Guru');

            $colIndex = 5;
            foreach ($this->categories as $cat) {
                $count = $teacherDocs->where('category', $cat)->count();
                $colLetter = Coordinate::stringFromColumnIndex($colIndex++);
                $statusText = $count > 0 ? "Ada ({$count})" : "-";
                $sheet->setCellValue("{$colLetter}{$currentRow}", $statusText);
                $sheet->getStyle("{$colLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Total
            $totCol = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$totCol}{$currentRow}", $teacherDocs->count());
            $sheet->getStyle("{$totCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$totCol}{$currentRow}")->getFont()->setBold(true);

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Borders
        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));

        // Kolom A (No): Lebar tetap kompak (width: 6) agar tidak melar akibat teks judul di baris 1-3
        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(6);

        foreach (range(2, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $cleanYear = str_replace(['/', '\\'], '-', $yearName);
        $filename = "Matriks_Berkas_Guru_{$cleanYear}.xlsx";

        if ($request->filled('download_token')) {
            if (!headers_sent()) {
                @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
            }
        }

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
     * Printable view of Completeness Matrix.
     */
    public function printMatrix(Request $request)
    {
        if (!$this->isUserAdmin()) {
            abort(403, 'Akses ditolak: Hanya Administrator atau Kepala Sekolah yang dapat mencetak matriks.');
        }

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);

        $selectedYear = AcademicYear::find($selectedYearId);
        $yearName = $selectedYear ? $selectedYear->name : 'Aktif';

        $teachers = $this->getActiveTeachers();

        $allYearDocs = TeacherDocument::when($selectedYearId, function ($q) use ($selectedYearId) {
            $q->where('academic_year_id', $selectedYearId);
        })->get();

        $matrixData = [];
        foreach ($teachers as $teacher) {
            $teacherDocs = $allYearDocs->where('teacher_id', $teacher->id);
            $categoriesStatus = [];

            foreach ($this->categories as $cat) {
                $docsInCat = $teacherDocs->where('category', $cat);
                $categoriesStatus[$cat] = [
                    'count' => $docsInCat->count(),
                    'approved' => $docsInCat->where('status', 'Disetujui')->count(),
                    'pending' => $docsInCat->where('status', 'Menunggu Review')->count(),
                    'needs_revision' => $docsInCat->where('status', 'Perlu Revisi')->count(),
                ];
            }

            $matrixData[] = [
                'teacher' => $teacher,
                'total_uploaded' => $teacherDocs->count(),
                'categories' => $categoriesStatus,
            ];
        }

        $categories = $this->categories;

        return view('admin.teacher-documents.print-matrix', compact(
            'matrixData',
            'categories',
            'yearName',
            'selectedYear'
        ));
    }
}
