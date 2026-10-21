<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Jenjang;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentClassroomHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentController extends Controller
{
    /**
     * Display a listing of students with filters & pagination.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        // Default to active academic year if not explicitly selected
        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear;
        $selectedYearId = $selectedYear?->id;

        // Master lists for filter dropdowns & modal selects
        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id');
        if (!$selectedJenjangId && $jenjangs->isNotEmpty()) {
            $selectedJenjangId = $jenjangs->first()->id;
        }

        // Helper closure to match students belonging to a Jenjang (supports multi-jenjang: primary, daycare, tpq)
        $applyJenjangCondition = function ($q, $jenjangObj) {
            $jId = is_object($jenjangObj) ? $jenjangObj->id : $jenjangObj;
            $jCode = is_object($jenjangObj) ? strtoupper($jenjangObj->code ?? '') : '';

            $q->where(function ($sub) use ($jId, $jCode) {
                $sub->where('jenjang_id', $jId)
                    ->orWhereHas('classroom', fn($sq) => $sq->where('jenjang_id', $jId))
                    ->orWhereHas('classLevel', fn($sq) => $sq->where('jenjang_id', $jId));

                if ($jCode === 'DAYCARE' || str_contains($jCode, 'DAYCARE') || str_contains($jCode, 'TPA')) {
                    $sub->orWhereNotNull('daycare_classroom_id')
                        ->orWhere('sub_unit', 'DAYCARE');
                } elseif ($jCode === 'TPQ' || str_contains($jCode, 'TPQ')) {
                    $sub->orWhere('is_tpq', true)
                        ->orWhereNotNull('tpq_classroom_id')
                        ->orWhere('sub_unit', 'TPQ');
                }
            });
        };

        // Compute active students count for each Jenjang tab
        foreach ($jenjangs as $j) {
            $countQuery = Student::where('status', 'aktif');
            $applyJenjangCondition($countQuery, $j);
            if ($selectedYearId && $selectedYearId !== 'all') {
                $countQuery->where('academic_year_id', $selectedYearId);
            }
            $j->students_count = $countQuery->count();
        }

        $query = Student::with([
            'jenjang',
            'classroom.classLevel',
            'classroom.jenjang',
            'daycareClassroom.jenjang',
            'tpqClassroom.jenjang',
            'classLevel.jenjang',
            'academicYear',
            'spmbCandidate'
        ]);

        // Jenjang Filter (Active Tab)
        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $currentJenjangObj = $jenjangs->firstWhere('id', $selectedJenjangId);
            if ($currentJenjangObj) {
                $applyJenjangCondition($query, $currentJenjangObj);
            } else {
                $query->where('jenjang_id', $selectedJenjangId);
            }
        }

        // Academic Year Filter
        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
        }

        // Search query
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        // Filter: Kelas (ClassLevel)
        $selectedClassLevelId = $request->get('class_level_id');
        if ($selectedClassLevelId && $selectedClassLevelId !== 'all') {
            $query->where(function ($q) use ($selectedClassLevelId) {
                $q->where('class_level_id', $selectedClassLevelId)
                  ->orWhereHas('classroom', function ($sq) use ($selectedClassLevelId) {
                      $sq->where('class_level_id', $selectedClassLevelId);
                  });
            });
        }

        // Filter: Kelompok (Classroom)
        $selectedClassroomId = $request->get('classroom_id');
        if ($selectedClassroomId && $selectedClassroomId !== 'all') {
            $query->where(function ($q) use ($selectedClassroomId) {
                $q->where('classroom_id', $selectedClassroomId)
                  ->orWhere('daycare_classroom_id', $selectedClassroomId)
                  ->orWhere('tpq_classroom_id', $selectedClassroomId);
            });
        }

        // Filter: Status
        $selectedStatus = $request->get('status', 'all');
        if ($selectedStatus && $selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        // Filter: Gender
        $selectedGender = $request->get('gender', 'all');
        if ($selectedGender && $selectedGender !== 'all') {
            $query->where('gender', $selectedGender);
        }

        // Fast Single Aggregation Query for all Student Stats in the active Jenjang
        $statsAgg = Student::query();
        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $statsAgg->where(function ($q) use ($selectedJenjangId) {
                $q->where('jenjang_id', $selectedJenjangId)
                  ->orWhereHas('classroom', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  })
                  ->orWhereHas('classLevel', function ($sq) use ($selectedJenjangId) {
                      $sq->where('jenjang_id', $selectedJenjangId);
                  });
            });
        }
        if ($selectedYearId && $selectedYearId !== 'all') {
            $statsAgg->where('academic_year_id', $selectedYearId);
        }
        $aggregated = $statsAgg->selectRaw("
            COUNT(*) as total_all,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as total_active,
            SUM(CASE WHEN status = 'aktif' AND gender IN ('L', 'Laki-laki', 'Male') THEN 1 ELSE 0 END) as male,
            SUM(CASE WHEN status = 'aktif' AND gender IN ('P', 'Perempuan', 'Female') THEN 1 ELSE 0 END) as female
        ")->first();

        $rombelQuery = Classroom::where('is_active', true);
        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $rombelQuery->where('jenjang_id', $selectedJenjangId);
        }
        if ($selectedYearId && $selectedYearId !== 'all') {
            $rombelQuery->where('academic_year_id', $selectedYearId);
        }
        if ($selectedClassLevelId && $selectedClassLevelId !== 'all') {
            $rombelQuery->where('class_level_id', $selectedClassLevelId);
        }
        $totalClassrooms = $rombelQuery->count();

        $stats = [
            'total_active' => (int) ($aggregated->total_active ?? 0),
            'total_all' => (int) ($aggregated->total_all ?? 0),
            'male' => (int) ($aggregated->male ?? 0),
            'female' => (int) ($aggregated->female ?? 0),
            'classrooms' => $totalClassrooms,
        ];

        // Filter dropdowns: ClassLevels and Classrooms filtered by the active Jenjang tab & selected Academic Year
        $classLevels = ClassLevel::whereNotNull('jenjang_id')->where('is_active', true)
            ->when($selectedJenjangId && $selectedJenjangId !== 'all', fn($q) => $q->where('jenjang_id', $selectedJenjangId))
            ->when($selectedYearId && $selectedYearId !== 'all', function ($q) use ($selectedYearId) {
                $q->where(function ($sub) use ($selectedYearId) {
                    $sub->whereHas('classrooms', fn($cq) => $cq->where('academic_year_id', $selectedYearId))
                        ->orWhereHas('students', fn($sq) => $sq->where('academic_year_id', $selectedYearId));
                });
            })
            ->orderBy('order')
            ->get();

        $classrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true)
            ->when($selectedJenjangId && $selectedJenjangId !== 'all', fn($q) => $q->where('jenjang_id', $selectedJenjangId))
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($selectedClassLevelId && $selectedClassLevelId !== 'all', fn($q) => $q->where('class_level_id', $selectedClassLevelId))
            ->orderBy('class_level_id')
            ->orderBy('name')
            ->get();

        // Master lists for Modal dropdowns
        $allClassLevels = ClassLevel::with('jenjang')->whereNotNull('jenjang_id')->where('is_active', true)->orderBy('order')->get();
        $allClassrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher'])
            ->where('is_active', true)
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('name')
            ->get();

        $daycareJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'DAYCARE')
              ->orWhere('code', 'TPA')
              ->orWhere('name', 'like', '%Daycare%')
              ->orWhere('name', 'like', '%TPA%');
        })->pluck('id');

        $daycareClassrooms = Classroom::with(['classLevel', 'academicYear', 'jenjang'])
            ->where('is_active', true)
            ->whereIn('jenjang_id', $daycareJenjangIds)
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();

        $tpqJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'TPQ')
              ->orWhere('name', 'like', '%TPQ%');
        })->pluck('id');

        $tpqClassrooms = Classroom::with(['classLevel', 'academicYear', 'jenjang'])
            ->where('is_active', true)
            ->whereIn('jenjang_id', $tpqJenjangIds)
            ->when($selectedYearId && $selectedYearId !== 'all', fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();

        // Pagination with custom per-page selector
        $perPageInput = $request->get('per_page', 20);
        $perPage = ($perPageInput === 'all' || $perPageInput === 'semua' || (int)$perPageInput >= 9999) ? 99999 : (int)$perPageInput;
        if (!in_array($perPage, [10, 20, 25, 50, 100, 99999])) {
            $perPage = 20;
        }

        $students = $query->orderBy('status', 'asc')
            ->orderBy('full_name', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.students.index', compact(
            'students',
            'stats',
            'jenjangs',
            'classLevels',
            'classrooms',
            'allClassLevels',
            'allClassrooms',
            'daycareClassrooms',
            'tpqClassrooms',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedYear',
            'selectedJenjangId',
            'selectedClassLevelId',
            'selectedClassroomId',
            'selectedStatus',
            'selectedGender',
            'perPage'
        ));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(Request $request): View
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $classLevels = ClassLevel::with('jenjang')->where('is_active', true)->orderBy('order')->get();
        $classrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher', 'jenjang'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $daycareJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'DAYCARE')->orWhere('code', 'TPA')->orWhere('name', 'like', '%Daycare%')->orWhere('name', 'like', '%TPA%');
        })->pluck('id');
        $daycareClassrooms = Classroom::where('is_active', true)->whereIn('jenjang_id', $daycareJenjangIds)->orderBy('name')->get();

        $tpqJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'TPQ')->orWhere('name', 'like', '%TPQ%');
        })->pluck('id');
        $tpqClassrooms = Classroom::where('is_active', true)->whereIn('jenjang_id', $tpqJenjangIds)->orderBy('name')->get();

        $student = new Student();
        if ($request->has('spmb_candidate_id')) {
            $candidate = \App\Models\SpmbCandidate::find($request->get('spmb_candidate_id'));
            if ($candidate) {
                $raw = is_array($candidate->raw_payload) ? $candidate->raw_payload : [];
                $bio = $raw['student_bio'] ?? [];
                $addr = $bio['address'] ?? [];
                $parents = $raw['parent_info'] ?? [];
                $father = $parents['father'] ?? [];
                $mother = $parents['mother'] ?? [];
                $guardian = $parents['guardian'] ?? [];
                $emergency = $parents['emergency_contact'] ?? ($raw['emergency_contact'] ?? []);

                $student->fill([
                    'spmb_candidate_id' => $candidate->id,
                    'nisn' => $candidate->nisn ?? ($bio['nisn'] ?? null),
                    'nik' => $candidate->nik ?? ($bio['nik'] ?? null),
                    'full_name' => $candidate->full_name,
                    'nickname' => $candidate->nickname ?? ($bio['nickname'] ?? null),
                    'gender' => $candidate->gender,
                    'birth_place' => $candidate->birth_place,
                    'birth_date' => $candidate->birth_date,
                    'religion' => $candidate->religion ?: ($bio['religion'] ?? 'Islam'),
                    'citizenship' => $bio['citizenship'] ?? 'Indonesia (WNI)',
                    'address' => $addr['street'] ?? ($candidate->address ?? null),
                    'dusun' => $addr['dusun'] ?? null,
                    'rt' => $addr['rt'] ?? null,
                    'rw' => $addr['rw'] ?? null,
                    'kelurahan' => $addr['village'] ?? null,
                    'kecamatan' => $addr['district'] ?? null,
                    'city' => $addr['city'] ?? ($candidate->city ?? 'Kota Malang'),
                    'province' => $addr['province'] ?? ($candidate->province ?? 'Jawa Timur'),
                    'postal_code' => $addr['postal_code'] ?? null,
                    'previous_school' => $candidate->previous_school,
                    'student_photo_url' => $candidate->student_photo_url,
                    'father_name' => $candidate->father_name,
                    'father_phone' => $candidate->father_phone,
                    'father_job' => $candidate->father_job,
                    'father_nik' => $father['nik'] ?? null,
                    'father_education' => $father['education'] ?? null,
                    'father_income' => $father['income'] ?? null,
                    'mother_name' => $candidate->mother_name,
                    'mother_phone' => $candidate->mother_phone,
                    'mother_job' => $candidate->mother_job,
                    'mother_nik' => $mother['nik'] ?? null,
                    'mother_education' => $mother['education'] ?? null,
                    'mother_income' => $mother['income'] ?? null,
                    'guardian_name' => $candidate->guardian_name,
                    'guardian_phone' => $candidate->guardian_phone,
                    'guardian_job' => $guardian['job'] ?? null,
                    'parent_phone' => $candidate->parent_phone,
                    'parent_email' => $candidate->parent_email,
                    'emergency_contact_name' => $emergency['name'] ?? null,
                    'emergency_contact_phone' => $emergency['phone'] ?? null,
                    'documents' => $candidate->documents,
                ]);
            }
        }

        return view('admin.students.create', compact(
            'academicYears',
            'activeAcademicYear',
            'jenjangs',
            'classLevels',
            'classrooms',
            'daycareClassrooms',
            'tpqClassrooms',
            'student'
        ));
    }

    /**
     * Show single student detail page (or JSON for API/AJAX).
     */
    public function show($id, Request $request): View|JsonResponse
    {
        $student = Student::with([
            'jenjang',
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'classroom.jenjang',
            'daycareClassroom.homeroomTeacher',
            'tpqClassroom.homeroomTeacher',
            'classLevel.jenjang',
            'academicYear',
            'spmbCandidate',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'asc');
            }
        ])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'student' => $student,
                'formatted_gender' => $student->formatted_gender,
                'age' => $student->age,
                'whatsapp_url' => $student->whatsapp_url,
                'clean_phone' => $student->clean_parent_phone,
                'histories' => $student->classroomHistories,
            ]);
        }

        return view('admin.students.show', compact('student'));
    }

    /**
     * Download official individual student dossier as PDF (A4 Portrait).
     */
    public function dossierPdf(Request $request, $id)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        if ($request->filled('download_token')) {
            @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $student = Student::with([
            'academicYear',
            'jenjang',
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'daycareClassroom',
            'tpqClassroom',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'asc');
            }
        ])->findOrFail($id);

        $schoolName = setting('unit_name', setting('app_name', 'PAUD TERPADU ANAK SALEH'));
        $schoolAddress = setting('app_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang');
        $schoolPhone = setting('app_phone', setting('school_phone', '+62 812-3456-7890'));
        $schoolEmail = setting('app_email', 'paud.anaksaleh@gmail.com');

        $fileName = 'Dossier-Murid-' . \Illuminate\Support\Str::slug($student->full_name) . '-' . ($student->nis ?? $student->id) . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.students.dossier-pdf', compact(
            'student',
            'schoolName',
            'schoolAddress',
            'schoolPhone',
            'schoolEmail'
        ))->setPaper('a4', 'portrait');

        $response = $pdf->download($fileName);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Display standalone printable official dossier for a student (A4 Portrait HTML).
     */
    public function dossierPrint($id): View
    {
        $student = Student::with([
            'academicYear',
            'jenjang',
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'daycareClassroom',
            'tpqClassroom',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'asc');
            }
        ])->findOrFail($id);

        $schoolName = setting('unit_name', setting('app_name', 'PAUD TERPADU ANAK SALEH'));
        $schoolAddress = setting('app_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang');
        $schoolPhone = setting('app_phone', setting('school_phone', '+62 812-3456-7890'));
        $schoolEmail = setting('app_email', 'paud.anaksaleh@gmail.com');

        return view('admin.students.print-dossier', compact(
            'student',
            'schoolName',
            'schoolAddress',
            'schoolPhone',
            'schoolEmail'
        ));
    }

    /**
     * Download comprehensive individual student dossier as Excel (.xlsx).
     */
    public function dossierExcel(Request $request, $id)
    {
        if ($request->filled('download_token')) {
            @setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $student = Student::with([
            'academicYear',
            'jenjang',
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'daycareClassroom',
            'tpqClassroom',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'asc');
            }
        ])->findOrFail($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dossier ' . mb_substr($student->nickname ?: $student->full_name, 0, 20));

        // Document Banner
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A3:D3');

        $schoolName = setting('unit_name', setting('app_name', 'PAUD TERPADU ANAK SALEH MALANG'));
        $sheet->setCellValue('A1', strtoupper($schoolName));
        $sheet->setCellValue('A2', 'LEMBAR DOSSIER & BUKU INDUK MURID: ' . strtoupper($student->full_name));
        $sheet->setCellValue('A3', 'Dicetak melalui SAANS PAUD pada ' . date('d F Y, H:i') . ' WIB');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E1B4B'));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF4338CA'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(8.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));
        $sheet->getStyle('A1:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $row = 5;

        $addSectionHeader = function ($title, $color = 'FF4F46E5') use ($sheet, &$row) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $title);
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true)->setSize(9.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
            $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        };

        $addDataRow = function ($label1, $val1, $label2 = '', $val2 = '') use ($sheet, &$row) {
            $sheet->setCellValue("A{$row}", $label1);
            $sheet->setCellValueExplicit("B{$row}", (string) ($val1 ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $label2);
            $sheet->setCellValueExplicit("D{$row}", (string) ($val2 ?? '-'), DataType::TYPE_STRING);

            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(8.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            $sheet->getStyle("B{$row}")->getFont()->setSize(8.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));

            if (!empty($label2)) {
                $sheet->getStyle("C{$row}")->getFont()->setBold(true)->setSize(8.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
                $sheet->getStyle("C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                $sheet->getStyle("D{$row}")->getFont()->setSize(8.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0F172A'));
            }

            $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
            $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(19);
            $row++;
        };

        // 1. IDENTITAS POKOK & LEGALITAS
        $addSectionHeader('1. IDENTITAS POKOK & LEGALITAS KEPENDUDUKAN (DAPODIK)', 'FF0284C7');
        $addDataRow('Nama Lengkap Murid', strtoupper($student->full_name), 'Nama Panggilan', $student->nickname ?? '-');
        $addDataRow('Jenis Kelamin', $student->gender == 'L' ? 'Laki-laki' : ($student->gender == 'P' ? 'Perempuan' : $student->gender), 'Nomor Induk Murid (NIS)', $student->nis ?? '-');
        $addDataRow('Nomor Induk Siswa Nasional (NISN)', $student->nisn ?? '-', 'Nomor Induk Kependudukan (NIK)', $student->nik ?? '-');
        $addDataRow('Nomor Kartu Keluarga (KK)', $student->no_kk ?? '-', 'No. Registrasi Akta Lahir', $student->no_akta ?? '-');
        $addDataRow('Tempat Lahir', $student->birth_place ?? '-', 'Tanggal Lahir', $student->birth_date ? $student->birth_date->translatedFormat('d F Y') : '-');
        $addDataRow('Usia Saat Ini', $student->age, 'Agama & Kepercayaan', $student->religion ?? '-');
        $addDataRow('Kewarganegaraan', $student->citizenship ?? 'Indonesia (WNI)', 'Anak Ke- / Jml Saudara', 'Anak ke-' . ($student->child_order ?? '-') . ' dari ' . ($student->siblings_count ?? '-') . ' bersaudara');
        $needsText = str_contains(strtolower($student->special_needs ?? ''), 'ya') ? ('Ya (' . ($student->special_needs_detail ?? $student->special_needs) . ')') : 'Tidak Ada';
        $addDataRow('Kebutuhan Khusus', $needsText, 'PIN Akses Kios Presensi', $student->pin_access ?? '-');

        // 2. PENEMPATAN AKADEMIK & PROGRAM
        $row++;
        $addSectionHeader('2. PENEMPATAN AKADEMIK & PROGRAM LAYANAN', 'FF4F46E5');
        $addDataRow('Tahun Masuk / Ajaran', $student->academicYear?->name ?? '-', 'Status Murid', strtoupper($student->status ?? 'Aktif'));
        $addDataRow('Jenjang Pendidikan Utama', $student->jenjang?->name ?? ($student->sub_unit ?? '-'), 'Kelompok Belajar (Rombel)', $student->classroom?->name ?? '-');
        $addDataRow('Wali Kelas / Pendidik', $student->classroom?->homeroomTeacher?->name ?? '-', 'Tanggal Terdaftar', $student->enrolled_date ? $student->enrolled_date->translatedFormat('d F Y') : '-');
        $progs = [];
        if ($student->daycareClassroom) $progs[] = 'Daycare: ' . $student->daycareClassroom->name;
        if ($student->is_tpq || $student->tpqClassroom) $progs[] = 'TPQ: ' . ($student->tpqClassroom?->name ?? 'Program TPQ');
        $addDataRow('Program Tambahan', count($progs) > 0 ? implode(', ', $progs) : 'Hanya Reguler', 'Asal Sekolah Sebelumnya', $student->previous_school ?? '-');

        // 3. ALAMAT & DOMISILI
        $row++;
        $addSectionHeader('3. ALAMAT TINGGAL & LINGKUNGAN DOMISILI', 'FF0D9488');
        $addDataRow('Alamat Lengkap', $student->address ?? '-', 'RT / RW & Dusun', 'RT ' . ($student->rt ?? '-') . ' / RW ' . ($student->rw ?? '-') . ' & Dusun: ' . ($student->dusun ?? '-'));
        $addDataRow('Kelurahan / Desa', $student->kelurahan ?? '-', 'Kecamatan', $student->kecamatan ?? '-');
        $addDataRow('Kota / Kabupaten', $student->city ?? '-', 'Provinsi & Kode Pos', ($student->province ?? '-') . ' & Kode Pos: ' . ($student->postal_code ?? '-'));
        $addDataRow('Tinggal Bersama', $student->living_with ?? 'Orang Tua', 'Alat Transportasi', $student->transportation ?? '-');
        $addDataRow('Jarak ke Sekolah', $student->distance_to_school ? $student->distance_to_school . ' km' : '-', 'Waktu Tempuh', $student->travel_time_minutes ? $student->travel_time_minutes . ' menit' : '-');

        // 4. DATA ORANG TUA & WALI
        $row++;
        $addSectionHeader('4. DATA ORANG TUA, WALI & KONTAK DARURAT', 'FF059669');
        $addDataRow('Nama Ayah Kandung', $student->father_name ?? '-', 'Nama Ibu Kandung', $student->mother_name ?? '-');
        $addDataRow('NIK Ayah', $student->father_nik ?? '-', 'NIK Ibu', $student->mother_nik ?? '-');
        $addDataRow('Pendidikan Ayah', $student->father_education ?? '-', 'Pendidikan Ibu', $student->mother_education ?? '-');
        $addDataRow('Pekerjaan Ayah', $student->father_job ?? '-', 'Pekerjaan Ibu', $student->mother_job ?? '-');
        $addDataRow('Instansi / Perusahaan Ayah', $student->father_company ?? '-', 'Instansi / Perusahaan Ibu', $student->mother_company ?? '-');
        $addDataRow('Penghasilan Ayah', $student->father_income ?? '-', 'Penghasilan Ibu', $student->mother_income ?? '-');
        $addDataRow('No. HP / WA Ayah', $student->father_phone ?? '-', 'No. HP / WA Ibu', $student->mother_phone ?? '-');
        if ($student->guardian_name) {
            $addDataRow('Nama Wali Murid', $student->guardian_name, 'Hubungan / No. HP Wali', ($student->guardian_relation ?? 'Wali') . ' / ' . ($student->guardian_phone ?? '-'));
        }
        $addDataRow('Kontak Darurat (Nama)', $student->emergency_contact_name ?? '-', 'No. HP Kontak Darurat', $student->emergency_contact_phone ?? '-');

        // 5. FISIK, KESEHATAN & ANTROPOMETRI
        $row++;
        $addSectionHeader('5. FISIK, KESEHATAN & ANTROPOMETRI', 'FFD97706');
        $addDataRow('Tinggi Badan (TB)', $student->height ? $student->height . ' cm' : '-', 'Berat Badan (BB)', $student->weight ? $student->weight . ' kg' : '-');
        $addDataRow('Lingkar Kepala (LK)', $student->head_circumference ? $student->head_circumference . ' cm' : '-', 'Lingkar Lengan Atas (LiLA)', $student->arm_circumference ? $student->arm_circumference . ' cm' : '-');
        $addDataRow('Golongan Darah', $student->blood_type ?? '-', 'Riwayat Penyakit / Alergi', ($student->health_habits['illnesses_accidents'] ?? ($student->health_habits['allergies'] ?? 'Tidak ada riwayat alergi')));

        // 6. TUMBUH KEMBANG & MINAT
        $row++;
        $addSectionHeader('6. TUMBUH KEMBANG & HARAPAN PENDIDIKAN', 'FF7C3AED');
        $addDataRow('Hobi / Minat Murid', $student->student_hobby ?? '-', 'Cita-cita Murid', $student->student_aspiration ?? '-');
        $addDataRow('Harapan Orang Tua', $student->parent_aspiration ?? '-', 'Rencana Sekolah Lanjutan', $student->planned_school_after ?? '-');

        // Column Dimensions
        $sheet->getColumnDimension('A')->setWidth(26);
        $sheet->getColumnDimension('B')->setWidth(38);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(38);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Dossier-Murid-' . \Illuminate\Support\Str::slug($student->full_name) . '-' . ($student->nis ?? $student->id) . '.xlsx';

        $response = response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Show the form for editing the specified student.
     */
    public function edit($id): View
    {
        $student = Student::with([
            'jenjang',
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'daycareClassroom',
            'tpqClassroom',
            'classLevel',
            'academicYear'
        ])->findOrFail($id);

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $classLevels = ClassLevel::with('jenjang')->where('is_active', true)->orderBy('order')->get();
        $classrooms = Classroom::with(['classLevel', 'academicYear', 'homeroomTeacher', 'jenjang'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $daycareJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'DAYCARE')->orWhere('code', 'TPA')->orWhere('name', 'like', '%Daycare%')->orWhere('name', 'like', '%TPA%');
        })->pluck('id');
        $daycareClassrooms = Classroom::where('is_active', true)->whereIn('jenjang_id', $daycareJenjangIds)->orderBy('name')->get();

        $tpqJenjangIds = Jenjang::where(function($q) {
            $q->where('code', 'TPQ')->orWhere('name', 'like', '%TPQ%');
        })->pluck('id');
        $tpqClassrooms = Classroom::where('is_active', true)->whereIn('jenjang_id', $tpqJenjangIds)->orderBy('name')->get();

        return view('admin.students.edit', compact(
            'student',
            'academicYears',
            'activeAcademicYear',
            'jenjangs',
            'classLevels',
            'classrooms',
            'daycareClassrooms',
            'tpqClassrooms'
        ));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // Normalize empty string foreign keys / dates / numbers to null
        $input = $request->all();
        $nullableKeys = [
            'jenjang_id', 'class_level_id', 'classroom_id', 'academic_year_id', 'daycare_classroom_id', 'tpq_classroom_id',
            'nis', 'nisn', 'nik', 'no_kk', 'no_akta', 'citizenship', 'birth_date', 'notes', 'parent_phone', 'parent_email',
            'father_name', 'father_phone', 'father_job', 'mother_name', 'mother_phone', 'mother_job', 'guardian_name', 'guardian_phone',
            'nickname', 'birth_place', 'religion', 'address', 'dusun', 'rt', 'rw', 'kelurahan', 'kecamatan', 'city', 'province', 'postal_code',
            'living_with', 'transportation', 'child_order', 'siblings_count', 'blood_type', 'special_needs', 'special_needs_detail',
            'height', 'weight', 'head_circumference', 'arm_circumference', 'distance_to_school', 'travel_time_minutes',
            'pin_access', 'guardian_title_prefix', 'guardian_title_suffix'
        ];
        foreach ($nullableKeys as $k) {
            if (isset($input[$k]) && ($input[$k] === '' || $input[$k] === 'null')) {
                $input[$k] = null;
            }
        }
        $request->merge($input);

        $validated = $request->validate([
            'nis' => 'nullable|string|max:50|unique:students,nis',
            'nisn' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'no_kk' => 'nullable|string|max:50',
            'no_akta' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|string|in:L,P,Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'dusun' => 'nullable|string|max:100',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'living_with' => 'nullable|string|max:100',
            'transportation' => 'nullable|string|max:100',
            'child_order' => 'nullable|integer|min:1|max:30',
            'siblings_count' => 'nullable|integer|min:0|max:30',
            'blood_type' => 'nullable|string|max:10',
            'special_needs' => 'nullable|string|max:100',
            'special_needs_detail' => 'nullable|string|max:150',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:200',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'arm_circumference' => 'nullable|numeric|min:0|max:100',
            'distance_to_school' => 'nullable|string|max:150',
            'travel_time_minutes' => 'nullable|integer|min:0|max:300',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'sub_unit' => 'nullable|string|in:PG,TK,DAYCARE,TPQ',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'daycare_classroom_id' => 'nullable|exists:classrooms,id',
            'is_tpq' => 'nullable|boolean',
            'tpq_classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'father_job' => 'nullable|string|max:100',
            'father_nik' => 'nullable|string|max:50',
            'father_birth_place' => 'nullable|string|max:100',
            'father_birth_date' => 'nullable|date',
            'father_education' => 'nullable|string|max:100',
            'father_company' => 'nullable|string|max:150',
            'father_income' => 'nullable|string|max:50',
            'father_title_prefix' => 'nullable|string|max:150',
            'father_title_suffix' => 'nullable|string|max:150',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'mother_job' => 'nullable|string|max:100',
            'mother_nik' => 'nullable|string|max:50',
            'mother_birth_place' => 'nullable|string|max:100',
            'mother_birth_date' => 'nullable|date',
            'mother_education' => 'nullable|string|max:100',
            'mother_company' => 'nullable|string|max:150',
            'mother_income' => 'nullable|string|max:50',
            'mother_title_prefix' => 'nullable|string|max:150',
            'mother_title_suffix' => 'nullable|string|max:150',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:50',
            'guardian_nik' => 'nullable|string|max:50',
            'guardian_birth_place' => 'nullable|string|max:100',
            'guardian_birth_date' => 'nullable|date',
            'guardian_education' => 'nullable|string|max:100',
            'guardian_job' => 'nullable|string|max:100',
            'guardian_company' => 'nullable|string|max:150',
            'guardian_income' => 'nullable|string|max:50',
            'guardian_title_prefix' => 'nullable|string|max:150',
            'guardian_title_suffix' => 'nullable|string|max:150',
            'guardian_relation' => 'nullable|string|max:100',
            'is_accompanied_by_guardian' => 'nullable|boolean',
            'parent_phone' => 'nullable|string|max:50',
            'parent_email' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'is_paud_formal_before' => 'nullable|boolean',
            'planned_school_after' => 'nullable|string|max:150',
            'student_aspiration' => 'nullable|string|max:150',
            'student_hobby' => 'nullable|string|max:150',
            'parent_aspiration' => 'nullable|string',
            'pin_access' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'enrolled_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'documents' => 'nullable|array',
            'document_files' => 'nullable|array',
            'document_files.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            'student_photo' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
            'family_data' => 'nullable|array',
            'health_habits' => 'nullable|array',
            'development_psychology' => 'nullable|array',
        ]);

        $validated['nis'] = !empty($validated['nis']) ? trim($validated['nis']) : null;

        // Synchronize parent & guardian fields with family_data if provided
        if (!empty($validated['family_data'])) {
            $fd = $validated['family_data'];
            // Father
            if (!empty($fd['father']['name']) && empty($validated['father_name'])) $validated['father_name'] = $fd['father']['name'];
            if (!empty($fd['father']['phone']) && empty($validated['father_phone'])) $validated['father_phone'] = $fd['father']['phone'];
            if (!empty($fd['father']['job']) && empty($validated['father_job'])) $validated['father_job'] = $fd['father']['job'];
            if (!empty($fd['father']['nik']) && empty($validated['father_nik'])) $validated['father_nik'] = $fd['father']['nik'];
            if (!empty($fd['father']['education']) && empty($validated['father_education'])) $validated['father_education'] = $fd['father']['education'];
            if (!empty($fd['father']['workplace']) && empty($validated['father_company'])) $validated['father_company'] = $fd['father']['workplace'];
            if (!empty($fd['father']['company']) && empty($validated['father_company'])) $validated['father_company'] = $fd['father']['company'];
            if (!empty($fd['father']['income']) && empty($validated['father_income'])) $validated['father_income'] = $fd['father']['income'];
            if (!empty($fd['father']['title']) && empty($validated['father_title_suffix'])) $validated['father_title_suffix'] = $fd['father']['title'];
            // Mother
            if (!empty($fd['mother']['name']) && empty($validated['mother_name'])) $validated['mother_name'] = $fd['mother']['name'];
            if (!empty($fd['mother']['phone']) && empty($validated['mother_phone'])) $validated['mother_phone'] = $fd['mother']['phone'];
            if (!empty($fd['mother']['job']) && empty($validated['mother_job'])) $validated['mother_job'] = $fd['mother']['job'];
            if (!empty($fd['mother']['nik']) && empty($validated['mother_nik'])) $validated['mother_nik'] = $fd['mother']['nik'];
            if (!empty($fd['mother']['education']) && empty($validated['mother_education'])) $validated['mother_education'] = $fd['mother']['education'];
            if (!empty($fd['mother']['workplace']) && empty($validated['mother_company'])) $validated['mother_company'] = $fd['mother']['workplace'];
            if (!empty($fd['mother']['company']) && empty($validated['mother_company'])) $validated['mother_company'] = $fd['mother']['company'];
            if (!empty($fd['mother']['income']) && empty($validated['mother_income'])) $validated['mother_income'] = $fd['mother']['income'];
            if (!empty($fd['mother']['title']) && empty($validated['mother_title_suffix'])) $validated['mother_title_suffix'] = $fd['mother']['title'];
            // Guardian
            if (!empty($fd['guardian']['name']) && empty($validated['guardian_name'])) $validated['guardian_name'] = $fd['guardian']['name'];
            if (!empty($fd['guardian']['phone']) && empty($validated['guardian_phone'])) $validated['guardian_phone'] = $fd['guardian']['phone'];
            if (!empty($fd['guardian']['job']) && empty($validated['guardian_job'])) $validated['guardian_job'] = $fd['guardian']['job'];
            if (!empty($fd['guardian']['nik']) && empty($validated['guardian_nik'])) $validated['guardian_nik'] = $fd['guardian']['nik'];
            if (!empty($fd['guardian']['education']) && empty($validated['guardian_education'])) $validated['guardian_education'] = $fd['guardian']['education'];
            if (!empty($fd['guardian']['workplace']) && empty($validated['guardian_company'])) $validated['guardian_company'] = $fd['guardian']['workplace'];
            if (!empty($fd['guardian']['company']) && empty($validated['guardian_company'])) $validated['guardian_company'] = $fd['guardian']['company'];
            if (!empty($fd['guardian']['income']) && empty($validated['guardian_income'])) $validated['guardian_income'] = $fd['guardian']['income'];
            if (!empty($fd['guardian']['title_prefix']) && empty($validated['guardian_title_prefix'])) $validated['guardian_title_prefix'] = $fd['guardian']['title_prefix'];
            if (!empty($fd['guardian']['title']) && empty($validated['guardian_title_suffix'])) $validated['guardian_title_suffix'] = $fd['guardian']['title'];
            if (!empty($fd['guardian']['relation']) && empty($validated['guardian_relation'])) $validated['guardian_relation'] = $fd['guardian']['relation'];
            if (!empty($fd['environment']['dusun']) && empty($validated['dusun'])) $validated['dusun'] = $fd['environment']['dusun'];
        }

        if (empty($validated['parent_phone'])) {
            $validated['parent_phone'] = $validated['father_phone'] ?? $validated['mother_phone'] ?? $validated['guardian_phone'] ?? null;
        }

        if (empty($validated['academic_year_id'])) {
            $activeAY = AcademicYear::where('is_active', true)->first();
            $validated['academic_year_id'] = $activeAY ? $activeAY->id : null;
        }

        if (empty($validated['enrolled_date'])) {
            $validated['enrolled_date'] = now()->toDateString();
        }

        if (!empty($validated['classroom_id'])) {
            $cls = Classroom::find($validated['classroom_id']);
            if ($cls) {
                if (empty($validated['jenjang_id'])) {
                    $validated['jenjang_id'] = $cls->jenjang_id;
                }
                if (empty($validated['class_level_id'])) {
                    $validated['class_level_id'] = $cls->class_level_id;
                }
                if (empty($validated['sub_unit'])) {
                    $validated['sub_unit'] = $cls->sub_unit ?? 'TK';
                }
                if ($cls->sub_unit === 'DAYCARE' && empty($validated['daycare_classroom_id'])) {
                    $validated['daycare_classroom_id'] = $cls->id;
                }
            }
        } elseif (!empty($validated['daycare_classroom_id'])) {
            $validated['classroom_id'] = $validated['daycare_classroom_id'];
            $dcCls = Classroom::find($validated['daycare_classroom_id']);
            if ($dcCls) {
                $validated['jenjang_id'] = $validated['jenjang_id'] ?? $dcCls->jenjang_id;
                $validated['class_level_id'] = $validated['class_level_id'] ?? $dcCls->class_level_id;
                $validated['sub_unit'] = 'DAYCARE';
            }
        }

        if (!empty($validated['tpq_classroom_id'])) {
            $validated['is_tpq'] = true;
        } else {
            $validated['is_tpq'] = $request->boolean('is_tpq');
        }

        $optimizer = app(\App\Services\FileOptimizerService::class);

        // Process student photo upload if present
        if ($request->hasFile('student_photo')) {
            $validated['student_photo_url'] = $optimizer->optimizeAndStore(
                $request->file('student_photo'),
                'students/photos',
                'photo_' . time()
            );
        }

        // Process document files upload
        $docs = is_array($request->input('documents')) ? $request->input('documents') : [];
        if ($request->hasFile('document_files')) {
            foreach ($request->file('document_files') as $docKey => $file) {
                if ($file && $file->isValid()) {
                    $storedUrl = $optimizer->optimizeAndStore(
                        $file,
                        'students/documents',
                        'new_' . $docKey
                    );
                    $docs[$docKey] = $storedUrl;
                }
            }
        }
        $validated['documents'] = !empty($docs) ? $docs : null;

        $student = Student::create($validated);

        // Sync history
        if ($student->classroom_id) {
            $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
            StudentClassroomHistory::create([
                'student_id' => $student->id,
                'academic_year_id' => $student->academic_year_id,
                'classroom_id' => $student->classroom_id,
                'sub_unit' => $student->sub_unit,
                'classroom_name' => $student->classroom?->name,
                'grade_level' => $student->classroom?->classLevel?->name,
                'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name,
                'status' => 'aktif',
                'start_date' => $student->enrolled_date ?? now()->toDateString(),
                'notes' => 'Pendaftaran Murid Baru',
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Data murid {$student->full_name} berhasil ditambahkan.",
                'student' => $student,
            ]);
        }

        return redirect()->route('students.show', $student->id)
            ->with('success', "Data murid {$student->full_name} berhasil ditambahkan.");
    }

    /**
     * Update student details.
     */
    public function update(Request $request, $id): RedirectResponse|JsonResponse
    {
        $student = Student::findOrFail($id);

        // Normalize empty string foreign keys / dates / numbers to null
        $input = $request->all();
        $nullableKeys = [
            'jenjang_id', 'class_level_id', 'classroom_id', 'academic_year_id', 'daycare_classroom_id', 'tpq_classroom_id',
            'nis', 'nisn', 'nik', 'no_kk', 'no_akta', 'citizenship', 'birth_date', 'notes', 'parent_phone', 'parent_email',
            'father_name', 'father_phone', 'father_job', 'father_nik', 'father_birth_place', 'father_birth_date', 'father_education', 'father_company', 'father_income', 'father_title_prefix', 'father_title_suffix',
            'mother_name', 'mother_phone', 'mother_job', 'mother_nik', 'mother_birth_place', 'mother_birth_date', 'mother_education', 'mother_company', 'mother_income', 'mother_title_prefix', 'mother_title_suffix',
            'guardian_name', 'guardian_phone', 'guardian_nik', 'guardian_birth_place', 'guardian_birth_date', 'guardian_education', 'guardian_job', 'guardian_company', 'guardian_income', 'guardian_relation', 'is_accompanied_by_guardian',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'is_paud_formal_before', 'planned_school_after', 'student_aspiration', 'student_hobby', 'parent_aspiration',
            'nickname', 'birth_place', 'religion', 'address', 'dusun', 'rt', 'rw', 'kelurahan', 'kecamatan', 'city', 'province', 'postal_code',
            'living_with', 'transportation', 'child_order', 'siblings_count', 'blood_type', 'special_needs', 'special_needs_detail',
            'height', 'weight', 'head_circumference', 'arm_circumference', 'distance_to_school', 'travel_time_minutes',
            'pin_access', 'guardian_title_prefix', 'guardian_title_suffix'
        ];
        foreach ($nullableKeys as $k) {
            if (isset($input[$k]) && ($input[$k] === '' || $input[$k] === 'null')) {
                $input[$k] = null;
            }
        }
        $request->merge($input);

        $validated = $request->validate([
            'nis' => 'nullable|string|max:50|unique:students,nis,' . $student->id,
            'nisn' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:50',
            'no_kk' => 'nullable|string|max:50',
            'no_akta' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'gender' => 'required|string|in:L,P,Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'dusun' => 'nullable|string|max:100',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'living_with' => 'nullable|string|max:100',
            'transportation' => 'nullable|string|max:100',
            'child_order' => 'nullable|integer|min:1|max:30',
            'siblings_count' => 'nullable|integer|min:0|max:30',
            'blood_type' => 'nullable|string|max:10',
            'special_needs' => 'nullable|string|max:100',
            'special_needs_detail' => 'nullable|string|max:150',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:200',
            'head_circumference' => 'nullable|numeric|min:0|max:100',
            'arm_circumference' => 'nullable|numeric|min:0|max:100',
            'distance_to_school' => 'nullable|string|max:150',
            'travel_time_minutes' => 'nullable|integer|min:0|max:300',
            'jenjang_id' => 'nullable|exists:jenjangs,id',
            'sub_unit' => 'nullable|string|in:PG,TK,DAYCARE,TPQ',
            'class_level_id' => 'nullable|exists:class_levels,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'daycare_classroom_id' => 'nullable|exists:classrooms,id',
            'is_tpq' => 'nullable|boolean',
            'tpq_classroom_id' => 'nullable|exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:50',
            'father_job' => 'nullable|string|max:100',
            'father_nik' => 'nullable|string|max:50',
            'father_birth_place' => 'nullable|string|max:100',
            'father_birth_date' => 'nullable|date',
            'father_education' => 'nullable|string|max:100',
            'father_company' => 'nullable|string|max:150',
            'father_income' => 'nullable|string|max:50',
            'father_title_prefix' => 'nullable|string|max:150',
            'father_title_suffix' => 'nullable|string|max:150',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:50',
            'mother_job' => 'nullable|string|max:100',
            'mother_nik' => 'nullable|string|max:50',
            'mother_birth_place' => 'nullable|string|max:100',
            'mother_birth_date' => 'nullable|date',
            'mother_education' => 'nullable|string|max:100',
            'mother_company' => 'nullable|string|max:150',
            'mother_income' => 'nullable|string|max:50',
            'mother_title_prefix' => 'nullable|string|max:150',
            'mother_title_suffix' => 'nullable|string|max:150',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:50',
            'guardian_nik' => 'nullable|string|max:50',
            'guardian_birth_place' => 'nullable|string|max:100',
            'guardian_birth_date' => 'nullable|date',
            'guardian_education' => 'nullable|string|max:100',
            'guardian_job' => 'nullable|string|max:100',
            'guardian_company' => 'nullable|string|max:150',
            'guardian_income' => 'nullable|string|max:50',
            'guardian_title_prefix' => 'nullable|string|max:150',
            'guardian_title_suffix' => 'nullable|string|max:150',
            'guardian_relation' => 'nullable|string|max:100',
            'is_accompanied_by_guardian' => 'nullable|boolean',
            'parent_phone' => 'nullable|string|max:50',
            'parent_email' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'is_paud_formal_before' => 'nullable|boolean',
            'planned_school_after' => 'nullable|string|max:150',
            'student_aspiration' => 'nullable|string|max:150',
            'student_hobby' => 'nullable|string|max:150',
            'parent_aspiration' => 'nullable|string',
            'pin_access' => 'nullable|string|max:50',
            'status' => 'required|string|in:aktif,lulus,mutasi,keluar,nonaktif',
            'notes' => 'nullable|string',
            'documents' => 'nullable|array',
            'document_files' => 'nullable|array',
            'document_files.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            'student_photo' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
            'family_data' => 'nullable|array',
            'health_habits' => 'nullable|array',
            'development_psychology' => 'nullable|array',
        ]);

        $validated['nis'] = !empty($validated['nis']) ? trim($validated['nis']) : null;

        // Synchronize parent & guardian fields with family_data if provided
        if (!empty($validated['family_data'])) {
            $fd = $validated['family_data'];
            // Father
            if (!empty($fd['father']['name']) && empty($validated['father_name'])) $validated['father_name'] = $fd['father']['name'];
            if (!empty($fd['father']['phone']) && empty($validated['father_phone'])) $validated['father_phone'] = $fd['father']['phone'];
            if (!empty($fd['father']['job']) && empty($validated['father_job'])) $validated['father_job'] = $fd['father']['job'];
            if (!empty($fd['father']['nik']) && empty($validated['father_nik'])) $validated['father_nik'] = $fd['father']['nik'];
            if (!empty($fd['father']['education']) && empty($validated['father_education'])) $validated['father_education'] = $fd['father']['education'];
            if (!empty($fd['father']['workplace']) && empty($validated['father_company'])) $validated['father_company'] = $fd['father']['workplace'];
            if (!empty($fd['father']['company']) && empty($validated['father_company'])) $validated['father_company'] = $fd['father']['company'];
            if (!empty($fd['father']['income']) && empty($validated['father_income'])) $validated['father_income'] = $fd['father']['income'];
            if (!empty($fd['father']['title']) && empty($validated['father_title_suffix'])) $validated['father_title_suffix'] = $fd['father']['title'];
            // Mother
            if (!empty($fd['mother']['name']) && empty($validated['mother_name'])) $validated['mother_name'] = $fd['mother']['name'];
            if (!empty($fd['mother']['phone']) && empty($validated['mother_phone'])) $validated['mother_phone'] = $fd['mother']['phone'];
            if (!empty($fd['mother']['job']) && empty($validated['mother_job'])) $validated['mother_job'] = $fd['mother']['job'];
            if (!empty($fd['mother']['nik']) && empty($validated['mother_nik'])) $validated['mother_nik'] = $fd['mother']['nik'];
            if (!empty($fd['mother']['education']) && empty($validated['mother_education'])) $validated['mother_education'] = $fd['mother']['education'];
            if (!empty($fd['mother']['workplace']) && empty($validated['mother_company'])) $validated['mother_company'] = $fd['mother']['workplace'];
            if (!empty($fd['mother']['company']) && empty($validated['mother_company'])) $validated['mother_company'] = $fd['mother']['company'];
            if (!empty($fd['mother']['income']) && empty($validated['mother_income'])) $validated['mother_income'] = $fd['mother']['income'];
            if (!empty($fd['mother']['title']) && empty($validated['mother_title_suffix'])) $validated['mother_title_suffix'] = $fd['mother']['title'];
            // Guardian
            if (!empty($fd['guardian']['name']) && empty($validated['guardian_name'])) $validated['guardian_name'] = $fd['guardian']['name'];
            if (!empty($fd['guardian']['phone']) && empty($validated['guardian_phone'])) $validated['guardian_phone'] = $fd['guardian']['phone'];
            if (!empty($fd['guardian']['job']) && empty($validated['guardian_job'])) $validated['guardian_job'] = $fd['guardian']['job'];
            if (!empty($fd['guardian']['nik']) && empty($validated['guardian_nik'])) $validated['guardian_nik'] = $fd['guardian']['nik'];
            if (!empty($fd['guardian']['education']) && empty($validated['guardian_education'])) $validated['guardian_education'] = $fd['guardian']['education'];
            if (!empty($fd['guardian']['workplace']) && empty($validated['guardian_company'])) $validated['guardian_company'] = $fd['guardian']['workplace'];
            if (!empty($fd['guardian']['company']) && empty($validated['guardian_company'])) $validated['guardian_company'] = $fd['guardian']['company'];
            if (!empty($fd['guardian']['income']) && empty($validated['guardian_income'])) $validated['guardian_income'] = $fd['guardian']['income'];
            if (!empty($fd['guardian']['title_prefix']) && empty($validated['guardian_title_prefix'])) $validated['guardian_title_prefix'] = $fd['guardian']['title_prefix'];
            if (!empty($fd['guardian']['title']) && empty($validated['guardian_title_suffix'])) $validated['guardian_title_suffix'] = $fd['guardian']['title'];
            if (!empty($fd['guardian']['relation']) && empty($validated['guardian_relation'])) $validated['guardian_relation'] = $fd['guardian']['relation'];
            if (!empty($fd['environment']['dusun']) && empty($validated['dusun'])) $validated['dusun'] = $fd['environment']['dusun'];
        }

        if (empty($validated['parent_phone'])) {
            $validated['parent_phone'] = $validated['father_phone'] ?? $validated['mother_phone'] ?? $validated['guardian_phone'] ?? $student->parent_phone;
        }

        if (!empty($validated['classroom_id'])) {
            $cls = Classroom::find($validated['classroom_id']);
            if ($cls) {
                if (empty($validated['jenjang_id'])) {
                    $validated['jenjang_id'] = $cls->jenjang_id;
                }
                if (empty($validated['class_level_id'])) {
                    $validated['class_level_id'] = $cls->class_level_id;
                }
                if (empty($validated['sub_unit'])) {
                    $validated['sub_unit'] = $cls->sub_unit ?? 'TK';
                }
                if ($cls->sub_unit === 'DAYCARE') {
                    $validated['daycare_classroom_id'] = $cls->id;
                } elseif (empty($request->input('daycare_classroom_id'))) {
                    $validated['daycare_classroom_id'] = null;
                }
            }
        } elseif (!empty($validated['daycare_classroom_id'])) {
            $validated['classroom_id'] = $validated['daycare_classroom_id'];
            $dcCls = Classroom::find($validated['daycare_classroom_id']);
            if ($dcCls) {
                $validated['jenjang_id'] = $validated['jenjang_id'] ?? $dcCls->jenjang_id;
                $validated['class_level_id'] = $validated['class_level_id'] ?? $dcCls->class_level_id;
                $validated['sub_unit'] = 'DAYCARE';
            }
        } else {
            $validated['daycare_classroom_id'] = null;
        }

        if (!empty($validated['tpq_classroom_id'])) {
            $validated['is_tpq'] = true;
        } else {
            $validated['is_tpq'] = $request->boolean('is_tpq');
            if (!$validated['is_tpq']) {
                $validated['tpq_classroom_id'] = null;
            }
        }

        $optimizer = app(\App\Services\FileOptimizerService::class);

        // Process student photo upload if present
        if ($request->hasFile('student_photo')) {
            if ($student->student_photo_url) {
                $optimizer->deleteIfStoredLocally($student->student_photo_url);
            }
            $validated['student_photo_url'] = $optimizer->optimizeAndStore(
                $request->file('student_photo'),
                'students/photos',
                'photo_' . $student->id . '_' . time()
            );
        }

        // Process document files upload
        $docs = is_array($request->input('documents')) ? $request->input('documents') : [];
        $existingDocs = is_array($student->documents) ? $student->documents : [];

        if ($request->hasFile('document_files')) {
            foreach ($request->file('document_files') as $docKey => $file) {
                if ($file && $file->isValid()) {
                    if (!empty($existingDocs[$docKey])) {
                        $optimizer->deleteIfStoredLocally($existingDocs[$docKey]);
                    }
                    $storedUrl = $optimizer->optimizeAndStore(
                        $file,
                        'students/documents',
                        $student->id . '_' . $docKey
                    );
                    $docs[$docKey] = $storedUrl;
                }
            }
        }
        $finalDocs = array_merge($existingDocs, array_filter($docs, fn($v) => !is_null($v) && $v !== ''));
        $validated['documents'] = !empty($finalDocs) ? $finalDocs : null;

        $student->update($validated);

        // Sync or record history with immutable text snapshots
        if ($student->classroom_id) {
            $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
            $isInactiveOrExit = in_array($student->status, ['mutasi', 'keluar', 'lulus', 'nonaktif']);

            StudentClassroomHistory::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $student->academic_year_id,
                    'classroom_id' => $student->classroom_id,
                ],
                [
                    'sub_unit' => $student->sub_unit ?: ($student->classroom?->sub_unit ?? 'TK'),
                    'classroom_name' => $student->classroom?->name,
                    'grade_level' => $student->classroom?->classLevel?->name,
                    'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name,
                    'status' => $student->status,
                    'start_date' => $student->enrolled_date ?? now()->toDateString(),
                    'end_date' => $isInactiveOrExit ? now()->toDateString() : null,
                    'notes' => 'Pembaruan Data Murid (' . ucfirst($student->status) . ')',
                ]
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Data murid {$student->full_name} berhasil diperbarui.",
                'student' => $student,
            ]);
        }

        return redirect()->route('students.show', $student->id)
            ->with('success', "Data murid {$student->full_name} berhasil diperbarui.");
    }

    /**
     * Delete student.
     */
    public function destroy($id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $name = $student->full_name;

        // If linked to SPMB candidate, unlink it
        if ($student->spmb_candidate_id) {
            $candidate = \App\Models\SpmbCandidate::find($student->spmb_candidate_id);
            if ($candidate) {
                $candidate->is_enrolled = false;
                $candidate->enrolled_at = null;
                $candidate->student_id = null;
                $candidate->save();
            }
        }

        $student->classroomHistories()->delete();
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => "Data murid {$name} berhasil dihapus.",
        ]);
    }

    /**
     * Helper to get filtered students query for export.
     */
    protected function getFilteredStudentsQuery(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedJenjangId = $request->get('jenjang_id');

        $query = Student::with([
            'jenjang',
            'classroom.classLevel',
            'classroom.jenjang',
            'classroom.homeroomTeacher',
            'daycareClassroom',
            'tpqClassroom',
            'classLevel',
            'academicYear',
            'spmbCandidate',
        ]);

        if ($selectedJenjangId && $selectedJenjangId !== 'all') {
            $activeJenjang = Jenjang::find($selectedJenjangId);
            $jCode = strtoupper($activeJenjang?->code ?? '');
            $query->where(function ($q) use ($selectedJenjangId, $jCode) {
                $q->where('jenjang_id', $selectedJenjangId)
                  ->orWhereHas('classroom', fn($sq) => $sq->where('jenjang_id', $selectedJenjangId))
                  ->orWhereHas('classLevel', fn($sq) => $sq->where('jenjang_id', $selectedJenjangId));

                if ($jCode === 'DAYCARE' || str_contains($jCode, 'DAYCARE') || str_contains($jCode, 'TPA')) {
                    $q->orWhereNotNull('daycare_classroom_id')
                      ->orWhere('sub_unit', 'DAYCARE');
                } elseif ($jCode === 'TPQ' || str_contains($jCode, 'TPQ')) {
                    $q->orWhere('is_tpq', true)
                      ->orWhereNotNull('tpq_classroom_id')
                      ->orWhere('sub_unit', 'TPQ');
                }
            });
        }

        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where('academic_year_id', $selectedYearId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        if ($classLevelId = $request->get('class_level_id')) {
            if ($classLevelId !== 'all') {
                $query->where(function ($q) use ($classLevelId) {
                    $q->where('class_level_id', $classLevelId)
                      ->orWhereHas('classroom', function ($sq) use ($classLevelId) {
                          $sq->where('class_level_id', $classLevelId);
                      });
                });
            }
        }

        if ($classroomId = $request->get('classroom_id')) {
            if ($classroomId !== 'all') {
                $query->where(function ($q) use ($classroomId) {
                    $q->where('classroom_id', $classroomId)
                      ->orWhere('daycare_classroom_id', $classroomId)
                      ->orWhere('tpq_classroom_id', $classroomId);
                });
            }
        }

        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($gender = $request->get('gender')) {
            if ($gender !== 'all') {
                $query->where('gender', $gender);
            }
        }

        return $query->orderBy('classroom_id', 'asc')->orderBy('full_name', 'asc');
    }

    /**
     * Resolve document export metadata, dynamic titles, and filenames based on active filters.
     */
    protected function resolveExportMeta(Request $request, $students)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', $activeAcademicYear?->id);
        $selectedYear = $selectedYearId === 'all' ? null : ($academicYears->firstWhere('id', $selectedYearId) ?? $activeAcademicYear);

        $jenjangs = Jenjang::where('is_active', true)->orderBy('order')->get();
        $selectedJenjangId = $request->get('jenjang_id');
        $selectedJenjang = ($selectedJenjangId && $selectedJenjangId !== 'all') ? $jenjangs->firstWhere('id', $selectedJenjangId) : null;

        $selectedClassLevel = null;
        if ($clId = $request->get('class_level_id')) {
            if ($clId !== 'all') {
                $selectedClassLevel = ClassLevel::find($clId);
            }
        }

        $selectedClassroom = null;
        if ($crId = $request->get('classroom_id')) {
            if ($crId !== 'all') {
                $selectedClassroom = Classroom::find($crId);
            }
        }

        $selectedStatus = $request->get('status');
        $selectedGender = $request->get('gender');

        // Dynamic Document Title based on active filters
        $titleParts = ['DAFTAR DATA LENGKAP MURID'];
        $fileContextParts = ['Data_Lengkap_Murid'];

        if ($selectedClassroom) {
            $titleParts[] = 'KELOMPOK ' . strtoupper($selectedClassroom->name);
            $fileContextParts[] = str_replace([' ', '/', '\\'], '_', $selectedClassroom->name);
        } elseif ($selectedClassLevel) {
            $titleParts[] = 'KELAS ' . strtoupper($selectedClassLevel->name);
            $fileContextParts[] = str_replace([' ', '/', '\\'], '_', $selectedClassLevel->name);
        } elseif ($selectedJenjang) {
            $titleParts[] = strtoupper($selectedJenjang->name);
            $fileContextParts[] = str_replace([' ', '/', '\\'], '_', $selectedJenjang->name);
        } else {
            $titleParts[] = 'SEMUA JENJANG';
            $fileContextParts[] = 'Semua_Jenjang';
        }

        if ($selectedGender && $selectedGender !== 'all') {
            $isL = in_array(strtoupper($selectedGender), ['L', 'LAKI-LAKI']);
            $titleParts[] = $isL ? '(PUTRA)' : '(PUTRI)';
            $fileContextParts[] = $isL ? 'Putra' : 'Putri';
        }

        if ($selectedStatus && !in_array($selectedStatus, ['all', 'aktif'])) {
            $titleParts[] = '(' . strtoupper($selectedStatus) . ')';
            $fileContextParts[] = ucfirst($selectedStatus);
        }

        $mainTitle = implode(' - ', $titleParts);

        // Filter Details Array for meta info
        $filterDetails = [];
        if ($selectedClassLevel) {
            $filterDetails['Kelas'] = $selectedClassLevel->name;
        }
        if ($selectedClassroom) {
            $filterDetails['Kelompok'] = $selectedClassroom->name;
        }
        if ($selectedStatus && $selectedStatus !== 'all') {
            $filterDetails['Status'] = ucfirst($selectedStatus);
        }
        if ($selectedGender && $selectedGender !== 'all') {
            $filterDetails['Gender'] = in_array(strtoupper($selectedGender), ['L', 'LAKI-LAKI']) ? 'Putra' : 'Putri';
        }

        $maleCount = $students->where('gender', 'L')->count();
        $femaleCount = $students->where('gender', 'P')->count();
        $totalCount = $students->count();

        // Subtitle text for Excel
        $excelSubtitleParts = [];
        $excelSubtitleParts[] = 'Tahun Ajaran: ' . ($selectedYear ? $selectedYear->name : 'Semua T.A.');
        $excelSubtitleParts[] = 'Jenjang: ' . ($selectedJenjang ? $selectedJenjang->name : 'Semua Jenjang');
        foreach ($filterDetails as $k => $v) {
            $excelSubtitleParts[] = "{$k}: {$v}";
        }
        $excelSubtitleParts[] = "Total: {$totalCount} Murid (L: {$maleCount}, P: {$femaleCount})";
        $excelSubtitleParts[] = "Tanggal Unduh: " . date('d/m/Y H:i') . " WIB";
        $excelSubtitle = implode(' | ', $excelSubtitleParts);

        // School & Leadership Info from Settings & Employees
        $schoolName = setting('unit_name', setting('app_name', 'PAUD Terpadu Anak Saleh'));
        $schoolAddress = setting('app_address', 'Jl. Candi Panggung Indah No. 1-3, Mojolangu, Lowokwaru, Kota Malang, Jawa Timur');
        $schoolPhone = setting('app_phone', '+62 812-3456-7890');
        $schoolEmail = setting('app_email', 'admin@sans.dev');

        $principalUser = User::where('role', 'kepala_sekolah')->first();
        $principalEmp = null;
        if ($principalUser && $principalUser->employee_id) {
            $principalEmp = Employee::find($principalUser->employee_id);
        }
        if (!$principalEmp) {
            $principalEmp = Employee::where(function ($q) {
                $q->where('position', 'like', '%Kepala Sekolah%')
                  ->orWhere('position', 'like', '%Kepsek%');
            })->first();
        }
        $principalName = $principalEmp?->name ?? $principalUser?->name ?? setting('school_principal_name', 'Dra. Hj. Mike Supraptiwi, S.Psi, M.Pd.');
        $principalNiy = $principalEmp?->niy ?? $principalEmp?->nip ?? setting('school_principal_niy', '-');

        // Logo image as base64 for DomPDF embed
        $logoPath = setting('app_logo');
        $logoSrc = null;
        if ($logoPath) {
            $fullLogoPath = public_path('storage/' . $logoPath);
            if (!file_exists($fullLogoPath)) {
                $fullLogoPath = storage_path('app/public/' . $logoPath);
            }
            if (file_exists($fullLogoPath)) {
                $type = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
                $data = @file_get_contents($fullLogoPath);
                if ($data) {
                    $logoSrc = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }
        }

        $filePrefix = implode('_', $fileContextParts) . '_' . date('Ymd_His');

        return [
            'mainTitle' => $mainTitle,
            'institution' => strtoupper($schoolName),
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'schoolEmail' => $schoolEmail,
            'principalName' => $principalName,
            'principalNiy' => $principalNiy,
            'logoSrc' => $logoSrc,
            'excelSubtitle' => $excelSubtitle,
            'selectedYear' => $selectedYear,
            'selectedJenjang' => $selectedJenjang,
            'selectedClassLevel' => $selectedClassLevel,
            'selectedClassroom' => $selectedClassroom,
            'filterDetails' => $filterDetails,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'totalCount' => $totalCount,
            'filePrefix' => $filePrefix,
        ];
    }

    /**
     * Export students data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $students = $this->getFilteredStudentsQuery($request)->get();
        $meta = $this->resolveExportMeta($request, $students);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Murid');

        // CATEGORY HEADER (ROW 4) - 11 Comprehensive Categories across 199 Columns
        $categories = [
            ['range' => 'A4:S4', 'title' => '1. BIODATA & IDENTITAS UTAMA', 'color' => 'FF0284C7'], // Sky 600 (19 cols)
            ['range' => 'T4:AE4', 'title' => '2. AKADEMIK & PENEMPATAN KELAS', 'color' => 'FF4F46E5'], // Indigo 600 (12 cols)
            ['range' => 'AF4:AR4', 'title' => '3. DOMISILI & TRANSPORTASI', 'color' => 'FF0D9488'], // Teal 600 (13 cols)
            ['range' => 'AS4:CH4', 'title' => '4. DATA ORANG TUA & WALI', 'color' => 'FF059669'], // Emerald 600 (42 cols)
            ['range' => 'CI4:DB4', 'title' => '5. LINGKUNGAN KELUARGA & SOSIAL', 'color' => 'FF8B5CF6'], // Violet 600 (20 cols)
            ['range' => 'DC4:DX4', 'title' => '6. ANTROPOMETRI, LAHIR & TUMBUH KEMBANG', 'color' => 'FFEA580C'], // Orange 600 (22 cols)
            ['range' => 'DY4:EY4', 'title' => '7. POLA MAKAN, TOILET & KESEHATAN MEDIS', 'color' => 'FFD97706'], // Amber 600 (27 cols)
            ['range' => 'EZ4:FH4', 'title' => '8. POLA TIDUR & KEBIASAAN', 'color' => 'FF0284C7'], // Sky 600 (9 cols)
            ['range' => 'FI4:FQ4', 'title' => '9. PSIKOLOGI, SIFAT & POLA MAIN', 'color' => 'FFEC4899'], // Pink 600 (9 cols)
            ['range' => 'FR4:FZ4', 'title' => '10. SCREEN TIME, GADGET & TELEVISI', 'color' => 'FF6366F1'], // Indigo 500 (9 cols)
            ['range' => 'GA4:GQ4', 'title' => '11. KOMITMEN, HARAPAN & AKUN', 'color' => 'FF475569'], // Slate 600 (17 cols)
        ];

        // Document Title Banner - Merged across full category width (A1:GQ1, A2:GQ2, A3:GQ3)
        // Merging prevents truncation at the freeze pane boundary (Column C) while keeping clean layout
        $lastCategory = end($categories);
        $lastCol = explode(':', $lastCategory['range'])[1];
        $lastCol = preg_replace('/[0-9]/', '', $lastCol) ?: 'GQ';

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");

        $sheet->setCellValue('A1', $meta['mainTitle']);
        $sheet->setCellValue('A2', $meta['institution']);
        $sheet->setCellValue('A3', $meta['excelSubtitle']);

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E1B4B'));
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true)->setSize(10.5)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF4338CA'));
        $sheet->getStyle("A3:{$lastCol}3")->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

        $sheet->getStyle("A1:{$lastCol}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        foreach ($categories as $cat) {
            $sheet->mergeCells($cat['range']);
            $firstCell = explode(':', $cat['range'])[0];
            $sheet->setCellValue($firstCell, $cat['title']);
            $sheet->getStyle($cat['range'])->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($cat['range'])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($cat['color']);
            $sheet->getStyle($cat['range'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        // Table Headers (Comprehensive - 199 Columns) covering all questionnaires & profile fields
        $headers = [
            // 1. BIODATA & IDENTITAS UTAMA (1-19)
            'No',
            'Nama Lengkap',
            'Nama Panggilan',
            'L/P',
            'NIS',
            'NISN',
            'NIK Ananda',
            'No. KK',
            'No. Akta Kelahiran',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Usia',
            'Agama',
            'Kewarganegaraan',
            'Asal Sekolah Sebelumnya',
            'Pernah PAUD Non-Formal',
            'Rencana Sekolah Lanjutan',
            'Cita-cita Siswa',
            'Hobi Siswa',

            // 2. AKADEMIK & PENEMPATAN KELAS (20-31)
            'Status Murid',
            'Tahun Ajaran',
            'Sub Unit',
            'Jenjang',
            'Tingkat Kelas',
            'Kelompok Belajar',
            'Wali Kelas',
            'Kelompok Daycare',
            'Status TPQ',
            'Kelompok TPQ',
            'Tanggal Masuk / Diterima',
            'No. SPMB',

            // 3. DOMISILI & TRANSPORTASI (32-44)
            'Alamat Jalan',
            'Dusun',
            'RT',
            'RW',
            'Kelurahan / Desa',
            'Kecamatan',
            'Kota / Kab',
            'Provinsi',
            'Kode Pos',
            'Tempat Tinggal',
            'Moda Transportasi',
            'Jarak Sekolah (km)',
            'Waktu Tempuh (menit)',

            // 4. DATA ORANG TUA & WALI (45-86)
            'Gelar Depan Ayah',
            'Nama Ayah',
            'Gelar Belakang Ayah',
            'NIK Ayah',
            'Tempat Lahir Ayah',
            'Tanggal Lahir Ayah',
            'Pendidikan Ayah',
            'Pekerjaan Ayah',
            'Instansi Ayah',
            'Penghasilan Ayah',
            'No. HP Ayah',
            'Status Ayah',
            'Gelar Depan Ibu',
            'Nama Ibu',
            'Gelar Belakang Ibu',
            'NIK Ibu',
            'Tempat Lahir Ibu',
            'Tanggal Lahir Ibu',
            'Pendidikan Ibu',
            'Pekerjaan Ibu',
            'Instansi Ibu',
            'Penghasilan Ibu',
            'No. HP Ibu',
            'Status Ibu',
            'Gelar Depan Wali',
            'Nama Wali',
            'Gelar Belakang Wali',
            'NIK Wali',
            'Hubungan Wali',
            'Tempat Lahir Wali',
            'Tanggal Lahir Wali',
            'Pendidikan Wali',
            'Pekerjaan Wali',
            'Instansi Wali',
            'Penghasilan Wali',
            'No. HP Wali',
            'Didampingi Wali',
            'Nama Kontak Darurat',
            'No. HP Kontak Darurat',
            'Hubungan Kontak Darurat',
            'No. WhatsApp Kontak Utama',
            'Email Orang Tua',

            // 5. LINGKUNGAN KELUARGA & SOSIAL (87-106)
            'Status Rumah Tinggal',
            'Lingkungan Tempat Tinggal',
            'Halaman Bermain di Luar',
            'Keadaan Halaman Luar Rumah',
            'Jumlah Penghuni Dewasa',
            'Jumlah Penghuni Anak-Anak',
            'Hubungan Sehari-hari Paling Erat',
            'Dipercaya Mengantar-Jemput',
            'Hubungan dengan Ayah',
            'Hubungan dengan Bunda',
            'Hubungan dengan Saudara',
            'Hubungan dengan Kakek & Nenek',
            'Hubungan dengan Teman Sebaya',
            'Kesempatan Bergaul Teman Sebaya',
            'Intensitas Komunikasi Ayah',
            'Intensitas Komunikasi Bunda',
            'Iklim Keagamaan Keluarga',
            'Peran Beribadah Bersama Keluarga',
            'Masjid di Sekitar Rumah',
            'TPQ dalam Radius 300m',

            // 6. ANTROPOMETRI, LAHIR & TUMBUH KEMBANG (107-128)
            'Anak Ke-',
            'Jumlah Saudara Kandung',
            'Gol. Darah',
            'Berkebutuhan Khusus',
            'Rincian Kebutuhan Khusus',
            'Tinggi Badan Saat Ini (cm)',
            'Berat Badan Saat Ini (kg)',
            'Lingkar Kepala (cm)',
            'Lingkar Lengan / LiLa (cm)',
            'Berat & Panjang Lahir (kg/cm)',
            'Usia Kandungan Saat Lahir',
            'Kondisi Dalam Kandungan',
            'Cara Persalinan Saat Lahir',
            'Alasan Tindakan Persalinan',
            'Disusui Oleh',
            'Durasi Mendapatkan ASI (bulan)',
            'Usia Mulai Susu Formula (bulan)',
            'Usia Mulai MPASI / Tambahan',
            'Mulai Tumbuh Gigi Usia',
            'Mulai Dapat Berjalan Usia',
            'Mulai Dapat Berbicara Usia',
            'Gangguan Tumbuh Kembang',

            // 7. POLA MAKAN, TOILET & KESEHATAN MEDIS (129-155)
            'Selera Makan Pagi',
            'Selera Makan Siang',
            'Selera Makan Malam',
            'Kebiasaan Camilan / Snack',
            'Waktu Makan Sambil',
            'Berdoa Sebelum & Sesudah Makan',
            'Makanan yang Disukai',
            'Makanan yang Tidak Disukai',
            'Minuman yang Disukai',
            'Minuman yang Tidak Disukai',
            'Masih Memakai Diapers / Popok',
            'Kebiasaan Buang Air',
            'Kencing / Ngompol Saat Tidur',
            'Kebiasaan Gosok Gigi',
            'Frekuensi Gosok Gigi Sehari',
            'Gosok Gigi Dibantu / Mandiri',
            'Riwayat Imunisasi',
            'Penyakit Berat yang Pernah Diderita',
            'Penyakit yang Sering Diderita',
            'Alergi Terhadap Makanan',
            'Reaksi Terhadap Alergi',
            'Penyembuhan Alergi Makanan',
            'Alergi Terhadap Minuman',
            'Alternatif Penyembuhan Alergi',
            'Tempat Periksa Saat Sakit',
            'Alternatif Pengobatan Sakit',
            'Pesan Kesehatan dari Ortu',

            // 8. POLA TIDUR & KEBIASAAN (156-164)
            'Kebiasaan Tidur Malam Pukul',
            'Kebiasaan Bangun Pagi Pukul',
            'Kebiasaan Tidur Siang Pukul',
            'Bangun Tidur Siang Pukul',
            'Bagaimana Ananda Tidur',
            'Memiliki Kamar Tidur Sendiri',
            'Sebelum/Sesudah Tidur Diajari Doa',
            'Mengigau dalam Tidur',
            'Catatan Khusus Saat Tidur',

            // 9. PSIKOLOGI, SIFAT & POLA MAIN (165-173)
            'Karakter atau Sifat Ananda',
            'Kepatuhan Ananda di Rumah',
            'Catatan Perilaku di Rumah',
            'Kebiasaan Bermain',
            'Tempat Bermain Ananda',
            'Bermain Bersama Siapa',
            'Mainan yang Disenangi',
            'Mainan yang Tidak Disenangi',
            'Mainan Berbahaya yang Dihindari',

            // 10. SCREEN TIME, GADGET & TELEVISI (174-182)
            'Fasilitas Game & Sejenisnya',
            'Fasilitas Menonton/Buka YouTube',
            'Reaksi Setelah Membuka YouTube',
            'Frekuensi Menonton Televisi',
            'Acara TV yang Disukai',
            'Acara TV yang Tidak Disukai',
            'Pendamping Saat Menonton TV',
            'Didampingi Ketika Melihat TV',
            'Konsentrasi Ketika Melihat TV',

            // 11. KOMITMEN, HARAPAN & AKUN (183-199)
            'Harapan Orang Tua terhadap Siswa',
            'Kesediaan Menindaklanjuti Asesmen',
            'Bentuk Tindak Lanjut yang Disetujui',
            'Saran-saran Orang Tua ke Sekolah',
            'Upload Scan NISN',
            'Upload Scan Ket. Sekolah Lama',
            'Upload Scan KIA',
            'Upload Scan Rapot Terakhir',
            'Upload Scan Asesmen Tumbuh Kembang',
            'Upload Scan Buku KMS',
            'Berkas Akta Kelahiran',
            'Berkas Kartu Keluarga',
            'Berkas KTP Orang Tua',
            'Pas Foto Murid',
            'PIN Akses Ortu',
            'Tanggal Terdaftar di Sistem',
            'Catatan Khusus dari Sekolah'
        ];

        $startRow = 5;
        foreach ($headers as $colIndex => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . $startRow, $header);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastCol}{$startRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF334155'); // Slate 700
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(26);

        // Populate Data Rows across 199 columns
        $currentRow = $startRow + 1;
        foreach ($students as $index => $s) {
            $genderLabel = in_array(strtoupper((string)$s->gender), ['L', 'LAKI-LAKI', 'MALE']) ? 'L' : 'P';
            $birthDateFormatted = $s->birth_date ? date('Y-m-d', strtotime($s->birth_date)) : '-';
            $jenjangName = $s->jenjang?->name ?? $s->classroom?->jenjang?->name ?? $s->classLevel?->jenjang?->name ?? $s->sub_unit ?? '-';
            $classLevelName = $s->classroom?->classLevel?->name ?? $s->classLevel?->name ?? '-';
            $rombelName = $s->classroom?->name ?? '-';
            $homeroomName = $s->classroom?->homeroomTeacher?->name ?? '-';
            $daycareName = $s->daycareClassroom?->name ?? '-';
            $tpqStatus = ($s->is_tpq || $s->tpq_classroom_id || $s->sub_unit === 'TPQ') ? 'Ya' : 'Tidak';
            $tpqName = $s->tpqClassroom?->name ?? ($tpqStatus === 'Ya' ? 'TPQ' : '-');
            $spmbNumber = $s->spmbCandidate?->registration_number ?? ($s->spmb_candidate_id ? '#' . $s->spmb_candidate_id : '-');
            $enrolledDateFormatted = $s->enrolled_date ? date('Y-m-d', strtotime($s->enrolled_date)) : '-';

            $fd = is_array($s->family_data) ? $s->family_data : [];
            $hh = is_array($s->health_habits) ? $s->health_habits : [];
            $dp = is_array($s->development_psychology) ? $s->development_psychology : [];
            $docs = is_array($s->documents) ? $s->documents : [];

            // Father fields (hybrid: direct column with fallback to JSON)
            $fatherTitlePrefix = $s->father_title_prefix ?? ($fd['father']['title_prefix'] ?? '');
            $fatherName = $s->father_name ?? ($fd['father']['name'] ?? '-');
            $fatherTitleSuffix = $s->father_title_suffix ?? ($fd['father']['title'] ?? '');
            $fatherNik = $s->father_nik ?? ($fd['father']['nik'] ?? '');
            $fatherBirthPlace = $s->father_birth_place ?? ($fd['father']['birth_place'] ?? '-');
            $fatherBirthDateFormatted = $s->father_birth_date ? date('Y-m-d', strtotime($s->father_birth_date)) : (!empty($fd['father']['birth_date']) ? $fd['father']['birth_date'] : '-');
            $fatherEdu = $s->father_education ?? ($fd['father']['education'] ?? '-');
            $fatherJob = $s->father_job ?? ($fd['father']['job'] ?? '-');
            $fatherCompany = $s->father_company ?? ($fd['father']['company'] ?? ($fd['father']['workplace'] ?? '-'));
            $fatherIncome = $s->father_income ?? ($fd['father']['income'] ?? '-');
            $fatherPhone = $s->father_phone ?? ($fd['father']['phone'] ?? '');
            $fatherStatus = $fd['father']['status'] ?? ($fd['father_status'] ?? ($s->father_name ? 'Masih Hidup' : '-'));

            // Mother fields (hybrid: direct column with fallback to JSON)
            $motherTitlePrefix = $s->mother_title_prefix ?? ($fd['mother']['title_prefix'] ?? '');
            $motherName = $s->mother_name ?? ($fd['mother']['name'] ?? '-');
            $motherTitleSuffix = $s->mother_title_suffix ?? ($fd['mother']['title'] ?? '');
            $motherNik = $s->mother_nik ?? ($fd['mother']['nik'] ?? '');
            $motherBirthPlace = $s->mother_birth_place ?? ($fd['mother']['birth_place'] ?? '-');
            $motherBirthDateFormatted = $s->mother_birth_date ? date('Y-m-d', strtotime($s->mother_birth_date)) : (!empty($fd['mother']['birth_date']) ? $fd['mother']['birth_date'] : '-');
            $motherEdu = $s->mother_education ?? ($fd['mother']['education'] ?? '-');
            $motherJob = $s->mother_job ?? ($fd['mother']['job'] ?? '-');
            $motherCompany = $s->mother_company ?? ($fd['mother']['company'] ?? ($fd['mother']['workplace'] ?? '-'));
            $motherIncome = $s->mother_income ?? ($fd['mother']['income'] ?? '-');
            $motherPhone = $s->mother_phone ?? ($fd['mother']['phone'] ?? '');
            $motherStatus = $fd['mother']['status'] ?? ($fd['mother_status'] ?? ($s->mother_name ? 'Masih Hidup' : '-'));

            // Guardian fields (hybrid: direct column with fallback to JSON)
            $guardianTitlePrefix = $s->guardian_title_prefix ?? ($fd['guardian']['title_prefix'] ?? '');
            $guardianName = $s->guardian_name ?? ($fd['guardian']['name'] ?? '-');
            $guardianTitleSuffix = $s->guardian_title_suffix ?? ($fd['guardian']['title'] ?? '');
            $guardianNik = $s->guardian_nik ?? ($fd['guardian']['nik'] ?? '');
            $guardianRelation = $s->guardian_relation ?? ($fd['guardian']['relation'] ?? ($s->guardian_name ? 'Wali' : '-'));
            $guardianBirthPlace = $s->guardian_birth_place ?? ($fd['guardian']['birth_place'] ?? '-');
            $guardianBirthDateFormatted = $s->guardian_birth_date ? date('Y-m-d', strtotime($s->guardian_birth_date)) : (!empty($fd['guardian']['birth_date']) ? $fd['guardian']['birth_date'] : '-');
            $guardianEdu = $s->guardian_education ?? ($fd['guardian']['education'] ?? '-');
            $guardianJob = $s->guardian_job ?? ($fd['guardian']['job'] ?? '-');
            $guardianCompany = $s->guardian_company ?? ($fd['guardian']['company'] ?? ($fd['guardian']['workplace'] ?? '-'));
            $guardianIncome = $s->guardian_income ?? ($fd['guardian']['income'] ?? '-');
            $guardianPhone = $s->guardian_phone ?? ($fd['guardian']['phone'] ?? '');
            $isAccompanied = ($s->is_accompanied_by_guardian || ($fd['guardian']['is_accompanied'] ?? false)) ? 'Ya' : 'Tidak';

            // Emergency & Main Contact
            $emergencyName = $s->emergency_contact_name ?? '-';
            $emergencyPhone = $s->emergency_contact_phone ?? '';
            $emergencyRelation = $s->emergency_contact_relation ?? '-';
            $mainParentPhone = $s->clean_parent_phone ?? $s->parent_phone ?? ($fatherPhone ?: $motherPhone ?: '');
            $parentEmail = $s->parent_email ?? ($fd['parent_email'] ?? '-');

            // Milestones & Measurements
            $birthWeightLength = $hh['birth_history']['birth_weight_length'] ?? '-';
            $gestationalAge = $hh['birth_history']['gestational_age'] ?? '-';
            $prenatalCondition = $hh['birth_history']['prenatal_condition'] ?? '-';
            $deliveryMethod = $hh['birth_history']['delivery_method'] ?? '-';
            $deliveryReason = $hh['birth_history']['delivery_reason'] ?? '-';

            $breastfedBy = $hh['milestones']['breastfed_by'] ?? '-';
            $breastfedDuration = $hh['milestones']['breastfed_duration'] ?? '-';
            $formulaStart = $hh['milestones']['formula_start'] ?? '-';
            $mpasiStart = $hh['milestones']['mpasi_start'] ?? '-';
            $teethAge = $hh['milestones']['teeth_age'] ?? '-';
            $walkingAge = $hh['milestones']['walking_age'] ?? '-';
            $talkingAge = $hh['milestones']['talking_age'] ?? '-';
            $developmentalIssues = $hh['milestones']['developmental_issues'] ?? '-';

            // Meals
            $breakfastAppetite = $hh['meals']['breakfast'] ?? '-';
            $lunchAppetite = $hh['meals']['lunch'] ?? '-';
            $dinnerAppetite = $hh['meals']['dinner'] ?? '-';
            $snackHabit = $hh['meals']['snack_habit'] ?? '-';
            $eatingManner = $hh['meals']['eating_manner'] ?? '-';
            $praysBeforeAfter = $hh['meals']['prays_before_after'] ?? '-';
            $favFood = $hh['meals']['favorite'] ?? '-';
            $dislikedFood = $hh['meals']['avoided'] ?? '-';
            $favDrink = $hh['meals']['favorite_drink'] ?? '-';
            $dislikedDrink = $hh['meals']['avoided_drink'] ?? '-';

            // Toilet
            $usesDiapers = $hh['toilet_training']['uses_diapers'] ?? '-';
            $toiletHabit = $hh['toilet_training']['toilet_habit'] ?? '-';
            $bedwetting = $hh['toilet_training']['bedwetting'] ?? '-';
            $toothbrushHabit = $hh['toilet_training']['toothbrush_habit'] ?? '-';
            $toothbrushFrequency = $hh['toilet_training']['toothbrush_frequency'] ?? '-';
            $toothbrushAssisted = $hh['toilet_training']['toothbrush_assisted'] ?? '-';

            // Medical
            $immunizations = $hh['medical_history']['immunizations'] ?? '-';
            $severeDiseases = $hh['medical_history']['severe_diseases'] ?? '-';
            $frequentDiseases = $hh['medical_history']['frequent_diseases'] ?? '-';
            $foodAllergy = $hh['medical_history']['food_allergy'] ?? '-';
            $allergyReaction = $hh['medical_history']['allergy_reaction'] ?? '-';
            $allergyTreatment = $hh['medical_history']['allergy_treatment'] ?? '-';
            $drinkAllergy = $hh['medical_history']['drink_allergy'] ?? '-';
            $treatmentAlternativeAllergy = $hh['medical_history']['treatment_alternative'] ?? '-';
            $preferredDoctor = $hh['medical_history']['preferred_doctor'] ?? '-';
            $treatmentAlternative = $hh['medical_history']['treatment_alternative'] ?? '-';
            $schoolHealthNotes = $hh['medical_history']['school_health_notes'] ?? '-';

            // Sleeping
            $bedtime = $hh['sleeping']['bedtime'] ?? '-';
            $wakeMorningTime = $hh['sleeping']['wake_morning_time'] ?? '-';
            $napTime = $hh['sleeping']['nap_time'] ?? '-';
            $wakeNapTime = $hh['sleeping']['wake_nap_time'] ?? '-';
            $sleepingWith = $hh['sleeping']['sleeping_with'] ?? '-';
            $hasOwnBedroom = $hh['sleeping']['has_own_bedroom'] ?? '-';
            $sleepPrayers = $hh['sleeping']['sleep_prayers'] ?? '-';
            $sleepTalking = $hh['sleeping']['sleep_talking'] ?? '-';
            $sleepNotes = $hh['sleeping']['sleep_notes'] ?? '-';

            // Traits & Play
            $characterTraits = $dp['traits']['character_traits'] ?? '-';
            $obedience = $dp['traits']['obedience'] ?? '-';
            $behaviorNotes = $dp['traits']['behavior_notes'] ?? '-';

            $playHabits = $dp['play']['play_habits'] ?? '-';
            $playLocation = $dp['play']['play_location'] ?? '-';
            $playmates = $dp['play']['playmates'] ?? '-';
            $favoriteToys = $dp['play']['favorite_toys'] ?? '-';
            $dislikedToys = $dp['play']['disliked_toys'] ?? '-';
            $dangerousToys = $dp['play']['dangerous_toys'] ?? '-';

            // Screen Time
            $gameFacility = $dp['screen_time']['game_facility'] ?? '-';
            $youtubeAccess = $dp['screen_time']['youtube_access'] ?? '-';
            $youtubeReaction = $dp['screen_time']['youtube_reaction'] ?? '-';
            $tvFrequency = $dp['screen_time']['tv_frequency'] ?? '-';
            $favoriteTv = $dp['screen_time']['favorite_tv'] ?? '-';
            $dislikedTv = $dp['screen_time']['disliked_tv'] ?? '-';
            $tvCompanion = $dp['screen_time']['tv_companion'] ?? '-';
            $tvAccompanied = $dp['screen_time']['tv_accompanied'] ?? '-';
            $tvConcentration = $dp['screen_time']['tv_concentration'] ?? '-';

            // Commitments & Docs
            $parentAspiration = $s->parent_aspiration ?? ($dp['commitments']['paud_outcome_expectation'] ?? '-');
            $assessmentAgreement = $dp['commitments']['assessment_agreement'] ?? '-';
            $agreedFollowupTypes = $dp['commitments']['agreed_followup_types'] ?? '-';
            $parentSuggestions = $dp['commitments']['parent_suggestions'] ?? '-';

            $docNisn = !empty($docs['nisn']) ? 'Tersedia' : 'Belum';
            $docPrevSchool = !empty($docs['previous_school']) ? 'Tersedia' : 'Belum';
            $docKia = !empty($docs['kia']) ? 'Tersedia' : 'Belum';
            $docReport = !empty($docs['report']) ? 'Tersedia' : 'Belum';
            $docAssessment = !empty($docs['assessment']) ? 'Tersedia' : 'Belum';
            $docKms = !empty($docs['kms']) ? 'Tersedia' : 'Belum';
            $docBirthCert = !empty($docs['birth_certificate']) || !empty($s->no_akta) ? 'Tersedia' : 'Belum';
            $docFamilyCard = !empty($docs['family_card']) || !empty($s->no_kk) ? 'Tersedia' : 'Belum';
            $docParentId = !empty($docs['parent_id_card']) || !empty($s->father_nik) || !empty($s->mother_nik) ? 'Tersedia' : 'Belum';
            $studentPhoto = !empty($docs['student_photo']) || !empty($s->student_photo_url) ? 'Tersedia' : 'Belum';

            $row = [
                // 1. BIODATA & IDENTITAS UTAMA (19 cols)
                $index + 1,
                $s->full_name,
                $s->nickname ?? '-',
                $genderLabel,
                (string)($s->nis ?? ''),
                (string)($s->nisn ?? ''),
                (string)($s->nik ?? ''),
                (string)($s->no_kk ?? ''),
                (string)($s->no_akta ?? ''),
                $s->birth_place ?? '-',
                $birthDateFormatted,
                $s->age ?? '-',
                $s->religion ?? 'Islam',
                $s->citizenship ?? 'WNI',
                $s->previous_school ?? '-',
                $s->is_paud_formal_before ? 'Ya' : 'Tidak',
                $s->planned_school_after ?? '-',
                $s->student_aspiration ?? '-',
                $s->student_hobby ?? '-',

                // 2. AKADEMIK & PENEMPATAN KELAS (12 cols)
                ucfirst($s->status ?? 'aktif'),
                $s->academicYear?->name ?? '-',
                $s->sub_unit ?? 'TK',
                $jenjangName,
                $classLevelName,
                $rombelName,
                $homeroomName,
                $daycareName,
                $tpqStatus,
                $tpqName,
                $enrolledDateFormatted,
                (string)$spmbNumber,

                // 3. DOMISILI & TRANSPORTASI (13 cols)
                $s->address ?? '-',
                $s->dusun ?? '-',
                $s->rt ?? '-',
                $s->rw ?? '-',
                $s->kelurahan ?? '-',
                $s->kecamatan ?? '-',
                $s->city ?? 'Malang',
                $s->province ?? 'Jawa Timur',
                $s->postal_code ?? '-',
                $s->living_with ?? 'Bersama Orang Tua',
                $s->transportation ?? '-',
                $s->distance_to_school ?? '-',
                $s->travel_time_minutes ?? '-',

                // 4. DATA ORANG TUA & WALI (42 cols)
                $fatherTitlePrefix,
                $fatherName,
                $fatherTitleSuffix,
                (string)$fatherNik,
                $fatherBirthPlace,
                $fatherBirthDateFormatted,
                $fatherEdu,
                $fatherJob,
                $fatherCompany,
                $fatherIncome,
                (string)$fatherPhone,
                $fatherStatus,
                $motherTitlePrefix,
                $motherName,
                $motherTitleSuffix,
                (string)$motherNik,
                $motherBirthPlace,
                $motherBirthDateFormatted,
                $motherEdu,
                $motherJob,
                $motherCompany,
                $motherIncome,
                (string)$motherPhone,
                $motherStatus,
                $guardianTitlePrefix,
                $guardianName,
                $guardianTitleSuffix,
                (string)$guardianNik,
                $guardianRelation,
                $guardianBirthPlace,
                $guardianBirthDateFormatted,
                $guardianEdu,
                $guardianJob,
                $guardianCompany,
                $guardianIncome,
                (string)$guardianPhone,
                $isAccompanied,
                $emergencyName,
                (string)$emergencyPhone,
                $emergencyRelation,
                (string)$mainParentPhone,
                $parentEmail,

                // 5. LINGKUNGAN KELUARGA & SOSIAL (20 cols)
                $fd['environment']['house_ownership'] ?? '-',
                $fd['environment']['housing_environment'] ?? '-',
                $fd['environment']['has_playyard'] ?? '-',
                $fd['environment']['playyard_state'] ?? '-',
                $fd['environment']['adult_residents'] ?? '-',
                $fd['environment']['child_residents'] ?? '-',
                $fd['relationships']['closest_bond'] ?? '-',
                $fd['relationships']['trusted_pickup'] ?? '-',
                $fd['relationships']['rel_father'] ?? '-',
                $fd['relationships']['rel_mother'] ?? '-',
                $fd['relationships']['rel_siblings'] ?? '-',
                $fd['relationships']['rel_grandparents'] ?? '-',
                $fd['relationships']['rel_peers'] ?? '-',
                $fd['relationships']['peer_opportunity'] ?? '-',
                $fd['relationships']['comm_father'] ?? '-',
                $fd['relationships']['comm_mother'] ?? '-',
                $fd['religious_climate']['climate'] ?? '-',
                $fd['religious_climate']['family_worship'] ?? '-',
                $fd['religious_climate']['nearby_mosque'] ?? '-',
                $fd['religious_climate']['nearby_tpq'] ?? '-',

                // 6. ANTROPOMETRI, LAHIR & TUMBUH KEMBANG (22 cols)
                $s->child_order ?? '-',
                $s->siblings_count ?? '-',
                $s->blood_type ?? '-',
                $s->special_needs ?? 'Tidak',
                $s->special_needs_detail ?? '-',
                $s->height ?? '-',
                $s->weight ?? '-',
                $s->head_circumference ?? '-',
                $s->arm_circumference ?? '-',
                $birthWeightLength,
                $gestationalAge,
                $prenatalCondition,
                $deliveryMethod,
                $deliveryReason,
                $breastfedBy,
                $breastfedDuration,
                $formulaStart,
                $mpasiStart,
                $teethAge,
                $walkingAge,
                $talkingAge,
                $developmentalIssues,

                // 7. POLA MAKAN, TOILET & KESEHATAN MEDIS (27 cols)
                $breakfastAppetite,
                $lunchAppetite,
                $dinnerAppetite,
                $snackHabit,
                $eatingManner,
                $praysBeforeAfter,
                $favFood,
                $dislikedFood,
                $favDrink,
                $dislikedDrink,
                $usesDiapers,
                $toiletHabit,
                $bedwetting,
                $toothbrushHabit,
                $toothbrushFrequency,
                $toothbrushAssisted,
                $immunizations,
                $severeDiseases,
                $frequentDiseases,
                $foodAllergy,
                $allergyReaction,
                $allergyTreatment,
                $drinkAllergy,
                $treatmentAlternativeAllergy,
                $preferredDoctor,
                $treatmentAlternative,
                $schoolHealthNotes,

                // 8. POLA TIDUR & KEBIASAAN (9 cols)
                $bedtime,
                $wakeMorningTime,
                $napTime,
                $wakeNapTime,
                $sleepingWith,
                $hasOwnBedroom,
                $sleepPrayers,
                $sleepTalking,
                $sleepNotes,

                // 9. PSIKOLOGI, SIFAT & POLA MAIN (9 cols)
                $characterTraits,
                $obedience,
                $behaviorNotes,
                $playHabits,
                $playLocation,
                $playmates,
                $favoriteToys,
                $dislikedToys,
                $dangerousToys,

                // 10. SCREEN TIME, GADGET & TELEVISI (9 cols)
                $gameFacility,
                $youtubeAccess,
                $youtubeReaction,
                $tvFrequency,
                $favoriteTv,
                $dislikedTv,
                $tvCompanion,
                $tvAccompanied,
                $tvConcentration,

                // 11. KOMITMEN, HARAPAN & AKUN (17 cols)
                $parentAspiration,
                $assessmentAgreement,
                $agreedFollowupTypes,
                $parentSuggestions,
                $docNisn,
                $docPrevSchool,
                $docKia,
                $docReport,
                $docAssessment,
                $docKms,
                $docBirthCert,
                $docFamilyCard,
                $docParentId,
                $studentPhoto,
                (string)($s->pin_access ?? ''),
                $s->created_at ? date('Y-m-d H:i', strtotime($s->created_at)) : '-',
                $s->notes ?? '-'
            ];

            // Helper to ensure all values are scalar strings/numbers
            $toStr = function ($v, $default = '-') {
                if (is_null($v) || $v === '') return $default;
                if (is_bool($v)) return $v ? 'Ya' : 'Tidak';
                if (is_array($v)) {
                    $scalars = [];
                    array_walk_recursive($v, function ($leaf) use (&$scalars) {
                        if (!is_null($leaf) && $leaf !== '') $scalars[] = $leaf;
                    });
                    return !empty($scalars) ? implode(', ', $scalars) : $default;
                }
                return (string)$v;
            };

            // Populate all 199 columns
            foreach ($row as $colIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $stringVal = $toStr($val, '-');
                // Explicit string for numeric identifiers to avoid scientific notation and loss of leading zeroes:
                if (in_array($colIdx + 1, [5, 6, 7, 8, 9, 31, 48, 55, 60, 67, 72, 80, 83, 85, 197])) {
                    $sheet->setCellValueExplicit($colLetter . $currentRow, $stringVal === '-' ? '' : $stringVal, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($colLetter . $currentRow, $stringVal);
                }
            }

            // Alignment
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$currentRow}:N{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("P{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("T{$currentRow}:AB{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AD{$currentRow}:AE{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AH{$currentRow}:AI{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AN{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AQ{$currentRow}:AR{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AV{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("AX{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BC{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BH{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BJ{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BO{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BT{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("BW{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("CB{$currentRow}:CC{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("CE{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("CG{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("DC{$currentRow}:DF{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("DH{$currentRow}:DL{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("EM{$currentRow}:EN{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("EZ{$currentRow}:FC{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("GE{$currentRow}:GP{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Zebra striping
            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF8FAFC');
            }

            $currentRow++;
        }

        // Apply borders
        $dataEndRow = max($currentRow - 1, $startRow);
        $sheet->getStyle("A4:{$lastCol}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE2E8F0'));

        // Official Signature Endorsement Block (Kepala Sekolah) in Excel
        $signRow = $dataEndRow + 3;
        $sheet->setCellValue("B{$signRow}", "Malang, " . \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $sheet->setCellValue("B" . ($signRow + 1), "Mengetahui,");
        $sheet->setCellValue("B" . ($signRow + 2), "Kepala Sekolah " . $meta['schoolName']);
        $sheet->setCellValue("B" . ($signRow + 5), $meta['principalName']);
        $sheet->setCellValue("B" . ($signRow + 6), "NIY: " . $meta['principalNiy']);
        $sheet->getStyle("B{$signRow}:B" . ($signRow + 6))->getFont()->setSize(9);
        $sheet->getStyle("B" . ($signRow + 2))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($signRow + 5))->getFont()->setBold(true)->setUnderline(true);

        // Freeze pane on column C: Freezes Column A (No) and Column B (Nama Lengkap) & Rows 1-5
        // Allows smooth horizontal scrolling across the remaining columns while keeping student name visible!
        $sheet->freezePane('C6');

        // Column A: FIXED WIDTH (No) -> compact and prevented from auto-sizing against the document banner
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(7);

        // Columns B through GQ: Auto-fit column widths
        foreach (range(2, count($headers)) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = $meta['filePrefix'] . '.xlsx';

        if ($request->filled('download_token')) {
            setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
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
     * Export students data to PDF.
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $students = $this->getFilteredStudentsQuery($request)->get();
        $meta = $this->resolveExportMeta($request, $students);
        $fileName = $meta['filePrefix'] . '.pdf';

        if ($request->filled('download_token')) {
            setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.students.pdf', [
            'students' => $students,
            'selectedYear' => $meta['selectedYear'],
            'selectedJenjang' => $meta['selectedJenjang'],
            'filterDetails' => $meta['filterDetails'],
            'mainTitle' => $meta['mainTitle'],
            'meta' => $meta,
            'schoolName' => $meta['schoolName'],
            'schoolAddress' => $meta['schoolAddress'],
            'schoolPhone' => $meta['schoolPhone'],
            'schoolEmail' => $meta['schoolEmail'],
            'principalName' => $meta['principalName'],
            'principalNiy' => $meta['principalNiy'],
            'logoSrc' => $meta['logoSrc'],
        ])->setPaper('a4', 'landscape');

        $response = $pdf->download($fileName);

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Download Excel template for importing students with all 175 comprehensive fields matching official format.
     */
    public function downloadTemplate(?Request $request = null)
    {
        $request = $request ?? request();
        $templatePath = public_path('Template_Import_Siswa_Lengkap.xlsx');

        // Dynamically recreate if missing or out of sync
        if (!file_exists($templatePath)) {
            $sourceFile = public_path('Data_Siswa.xlsx');
            if (file_exists($sourceFile)) {
                $reader = IOFactory::createReaderForFile($sourceFile);
                $sourceSpreadsheet = $reader->load($sourceFile);
                $sourceSheet = $sourceSpreadsheet->getActiveSheet();
                $highestCol = $sourceSheet->getHighestColumn();
                $highestColIndex = Coordinate::columnIndexFromString($highestCol);

                $templateSpreadsheet = new Spreadsheet();
                $sheet = $templateSpreadsheet->getActiveSheet();
                $sheet->setTitle('Template Import Murid');

                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $headerVal = trim((string)$sourceSheet->getCell($colLetter . '1')->getValue());
                    $sheet->setCellValueExplicit($colLetter . '1', $headerVal, DataType::TYPE_STRING);
                    $sampleVal = (string)$sourceSheet->getCell($colLetter . '2')->getValue();
                    $sheet->setCellValueExplicit($colLetter . '2', $sampleVal, DataType::TYPE_STRING);
                }

                $headerRange = "A1:{$highestCol}1";
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
                $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sampleRange = "A2:{$highestCol}2";
                $sheet->getStyle($sampleRange)->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
                $sheet->getStyle($sampleRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->getStyle("A1:{$highestCol}2")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCBD5E1'));
                $sheet->freezePane('D2');

                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $headerLen = mb_strlen((string)$sheet->getCell($colLetter . '1')->getValue());
                    $sheet->getColumnDimension($colLetter)->setWidth(max(14, min(35, $headerLen + 4)));
                }

                $writer = new Xlsx($templateSpreadsheet);
                $writer->save($templatePath);
            }
        }

        if ($request->filled('download_token')) {
            setcookie('download_token', $request->query('download_token'), time() + 60, '/', '', false, false);
        }

        $filename = 'Template_Impor_Murid_Lengkap_175_Kolom.xlsx';
        if (file_exists($templatePath)) {
            $response = response()->download($templatePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        } else {
            // Fallback in case of unexpected file system constraint
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Import Murid');
            $sheet->setCellValue('A1', 'NIS');
            $sheet->setCellValue('B1', 'Nama Siswa');
            $sheet->setCellValue('C1', 'Kelas');
            $writer = new Xlsx($spreadsheet);
            $response = response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        }

        if ($request->filled('download_token')) {
            $response->headers->setCookie(cookie('download_token', $request->query('download_token'), 1, '/', null, false, false));
        }

        return $response;
    }

    /**
     * Import students from Excel spreadsheet with intelligent column detection & upsert.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'default_academic_year_id' => 'nullable|exists:academic_years,id',
            'default_classroom_id' => 'nullable|exists:classrooms,id',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file wajib berupa .xlsx, .xls, atau .csv.'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, false, false);

        if (count($rawRows) < 2) {
            return redirect()->back()->with('error', 'File Excel kosong atau tidak memiliki data murid.');
        }

        // Detect Header Row & Data Rows
        $headerRowIdx = 0;
        foreach (array_slice($rawRows, 0, 3) as $idx => $r) {
            $rowString = strtolower(implode(' ', array_filter(array_map('strval', $r))));
            if (str_contains($rowString, 'nama lengkap') || (str_contains($rowString, 'nama') && str_contains($rowString, 'nis'))) {
                $headerRowIdx = $idx;
                break;
            }
        }

        $headers = $rawRows[$headerRowIdx];
        $dataRows = array_slice($rawRows, $headerRowIdx + 1);

        // Build dynamic header mapper with comprehensive coverage for all 175 columns
        $colMap = [];
        foreach ($headers as $cIdx => $hText) {
            $h = trim(mb_strtolower((string)$hText));
            if ($h === '') continue;

            // 1. Uploaded Scans / Google Drive links (6 document columns)
            if (str_contains($h, 'upload scan') || str_starts_with($h, 'upload')) {
                if (str_contains($h, 'nisn')) { $colMap['doc_nisn'] = $cIdx; }
                elseif (str_contains($h, 'sekolah lama')) { $colMap['doc_previous_school'] = $cIdx; }
                elseif (str_contains($h, 'kia')) { $colMap['doc_kia'] = $cIdx; }
                elseif (str_contains($h, 'rapot')) { $colMap['doc_report'] = $cIdx; }
                elseif (str_contains($h, 'assesment') || str_contains($h, 'assessment')) { $colMap['doc_assessment'] = $cIdx; }
                elseif (str_contains($h, 'kms')) { $colMap['doc_kms'] = $cIdx; }
                continue;
            }

            // 2. Specific Questionnaires with potential substring overlaps
            if (str_contains($h, 'fasilitas game') || str_contains($h, 'game dan sejenisnya')) {
                $colMap['game_facility'] = $cIdx;
            }
            elseif (str_contains($h, 'difasilitasi menonton atau membuka youtube') || (str_contains($h, 'menonton atau membuka youtube') && !str_contains($h, 'bagaimana reaksi'))) {
                $colMap['youtube_access'] = $cIdx;
            }
            elseif (str_contains($h, 'reaksi ananda setelah difasilitasi menonton atau membuka youtube') || (str_contains($h, 'bagaimana reaksi') && str_contains($h, 'youtube'))) {
                $colMap['youtube_reaction'] = $cIdx;
            }
            elseif (str_contains($h, 'selesai menempuh pendidikan di paud') && str_contains($h, 'mengharapkan kemampuan')) {
                $colMap['parent_aspiration_paud'] = $cIdx;
            }
            elseif (str_contains($h, 'pernah paud non-formal') || str_contains($h, 'paud non-formal')) {
                $colMap['is_paud_formal_before'] = $cIdx;
            }
            elseif (str_contains($h, 'dusun')) {
                $colMap['dusun'] = $cIdx;
            }

            // 3. Relationships & Communications
            elseif (str_contains($h, 'hubungan ananda dengan ayah')) $colMap['rel_father'] = $cIdx;
            elseif (str_contains($h, 'hubungan ananda dengan bunda')) $colMap['rel_mother'] = $cIdx;
            elseif (str_contains($h, 'hubungan ananda dengan') && (str_contains($h, 'saudara') || str_contains($h, 'saudaranya'))) $colMap['rel_siblings'] = $cIdx;
            elseif (str_contains($h, 'hubungan ananda dengan kakek')) $colMap['rel_grandparents'] = $cIdx;
            elseif (str_contains($h, 'hubungan ananda dengan teman sebaya')) $colMap['rel_peers'] = $cIdx;
            elseif (str_contains($h, 'kesempatan bergaul')) $colMap['peer_opportunity'] = $cIdx;
            elseif (str_contains($h, 'kebersamaan ayah') || (str_contains($h, 'komunikasi') && str_contains($h, 'ayah'))) $colMap['comm_father'] = $cIdx;
            elseif (str_contains($h, 'kebersamaan bunda') || (str_contains($h, 'komunikasi') && str_contains($h, 'bunda'))) $colMap['comm_mother'] = $cIdx;
            elseif (str_contains($h, 'sehari - hari paling erat') || str_contains($h, 'paling erat dengan')) $colMap['closest_bond'] = $cIdx;

            // 4. House & Living
            elseif (str_contains($h, 'status rumah tinggal') || str_contains($h, 'tempat tinggal keluarga')) $colMap['house_ownership'] = $cIdx;
            elseif (str_contains($h, 'lingkungan rumah tergolong')) $colMap['housing_environment'] = $cIdx;
            elseif (str_contains($h, 'keadaannya') && str_contains($h, 'luar rumah')) $colMap['playyard_state'] = $cIdx;
            elseif (str_contains($h, 'memiliki halaman tempat bermain')) $colMap['has_playyard'] = $cIdx;
            elseif (str_contains($h, 'penghuni dewasa')) $colMap['adult_residents'] = $cIdx;
            elseif (str_contains($h, 'penghuni anak-anak')) $colMap['child_residents'] = $cIdx;
            elseif (str_contains($h, 'mengantar - jemput') || str_contains($h, 'mengantar-jemput')) $colMap['trusted_pickup'] = $cIdx;
            elseif (str_contains($h, 'anda tinggal bersama') || str_contains($h, 'tinggal bersama') || ($h === 'tempat tinggal')) $colMap['living_with'] = $cIdx;

            // 5. Religion & Community
            elseif (str_contains($h, 'iklim keagamaan')) $colMap['religious_climate'] = $cIdx;
            elseif (str_contains($h, 'mengajak ananda beribadah')) $colMap['family_worship'] = $cIdx;
            elseif (str_contains($h, 'ada masjid')) $colMap['nearby_mosque'] = $cIdx;
            elseif (str_contains($h, 'radius 300m') || str_contains($h, 'taman pendidikan al-qur')) $colMap['nearby_tpq'] = $cIdx;

            // 6. Father details
            elseif (str_contains($h, 'nama ayah') || ($h === 'ayah')) $colMap['father_name'] = $cIdx;
            elseif (str_contains($h, 'nik ayah')) $colMap['father_nik'] = $cIdx;
            elseif (str_contains($h, 'tempat lahir ayah')) $colMap['father_birth_place'] = $cIdx;
            elseif (str_contains($h, 'tanggal lahir ayah')) $colMap['father_birth_date'] = $cIdx;
            elseif (str_contains($h, 'pendidikan') && str_contains($h, 'ayah')) $colMap['father_education'] = $cIdx;
            elseif (str_contains($h, 'pekerjaan ayah')) $colMap['father_job'] = $cIdx;
            elseif (str_contains($h, 'instansi ayah')) $colMap['father_company'] = $cIdx;
            elseif (str_contains($h, 'penghasilan ayah')) $colMap['father_income'] = $cIdx;
            elseif (str_contains($h, 'depan ayah') || str_contains($h, 'gelas depan ayah')) $colMap['father_title_prefix'] = $cIdx;
            elseif (str_contains($h, 'belakang ayah')) $colMap['father_title_suffix'] = $cIdx;
            elseif ((str_contains($h, 'telepon ayah') || str_contains($h, 'hp ayah')) && str_contains($h, 'ayah')) $colMap['father_phone'] = $cIdx;

            // 7. Mother details
            elseif (str_contains($h, 'nama bunda') || str_contains($h, 'nama ibu')) $colMap['mother_name'] = $cIdx;
            elseif (str_contains($h, 'nik bunda') || str_contains($h, 'nik ibu')) $colMap['mother_nik'] = $cIdx;
            elseif (str_contains($h, 'tempat lahir bunda') || str_contains($h, 'tempat lahir ibu')) $colMap['mother_birth_place'] = $cIdx;
            elseif (str_contains($h, 'tanggal lahir bunda') || str_contains($h, 'tanggal lahir ibu')) $colMap['mother_birth_date'] = $cIdx;
            elseif (str_contains($h, 'pendidikan') && (str_contains($h, 'bunda') || str_contains($h, 'ibu'))) $colMap['mother_education'] = $cIdx;
            elseif (str_contains($h, 'pekerjaan bunda') || str_contains($h, 'pekerjaan ibu')) $colMap['mother_job'] = $cIdx;
            elseif (str_contains($h, 'instansi bunda') || str_contains($h, 'instansi ibu')) $colMap['mother_company'] = $cIdx;
            elseif (str_contains($h, 'penghasilan bunda') || str_contains($h, 'penghasilan ibu')) $colMap['mother_income'] = $cIdx;
            elseif (str_contains($h, 'depan bunda') || str_contains($h, 'depan ibu')) $colMap['mother_title_prefix'] = $cIdx;
            elseif (str_contains($h, 'belakang bunda') || str_contains($h, 'belakang ibu')) $colMap['mother_title_suffix'] = $cIdx;
            elseif (str_contains($h, 'telepon bunda') || str_contains($h, 'telepon ibu') || str_contains($h, 'hp bunda') || str_contains($h, 'hp ibu')) $colMap['mother_phone'] = $cIdx;

            // 8. Guardian details
            elseif (str_contains($h, 'nama wali')) $colMap['guardian_name'] = $cIdx;
            elseif (str_contains($h, 'nik wali')) $colMap['guardian_nik'] = $cIdx;
            elseif (str_contains($h, 'tempat lahir wali')) $colMap['guardian_birth_place'] = $cIdx;
            elseif (str_contains($h, 'tanggal lahir wali')) $colMap['guardian_birth_date'] = $cIdx;
            elseif (str_contains($h, 'pendidikan') && str_contains($h, 'wali')) $colMap['guardian_education'] = $cIdx;
            elseif (str_contains($h, 'pekerjaan wali')) $colMap['guardian_job'] = $cIdx;
            elseif (str_contains($h, 'instansi wali')) $colMap['guardian_company'] = $cIdx;
            elseif (str_contains($h, 'penghasilan wali')) $colMap['guardian_income'] = $cIdx;
            elseif (str_contains($h, 'depan') && str_contains($h, 'wali')) $colMap['guardian_title_prefix'] = $cIdx;
            elseif (str_contains($h, 'belakang') && str_contains($h, 'wali')) $colMap['guardian_title_suffix'] = $cIdx;
            elseif (str_contains($h, 'hubungan keluarga')) $colMap['guardian_relation'] = $cIdx;
            elseif (str_contains($h, 'didampingi wali')) $colMap['is_accompanied_by_guardian'] = $cIdx;
            elseif (str_contains($h, 'telepon wali') || str_contains($h, 'hp wali')) $colMap['guardian_phone'] = $cIdx;

            // 9. Emergency Contact & Parent Contacts
            elseif (str_contains($h, 'nomor kontak darurat') || str_contains($h, 'no hp darurat') || str_contains($h, 'telepon darurat') || str_contains($h, 'hp darurat')) {
                $colMap['emergency_contact_phone'] = $cIdx;
            }
            elseif (str_contains($h, 'hubungan kontak darurat') || str_contains($h, 'hubungan darurat')) {
                $colMap['emergency_contact_relation'] = $cIdx;
            }
            elseif (str_contains($h, 'darurat')) {
                $colMap['emergency_contact'] = $cIdx;
            }
            elseif (str_contains($h, 'email') || str_contains($h, 'surel')) {
                $colMap['parent_email'] = $cIdx;
            }
            elseif (str_contains($h, 'whatsapp') || str_contains($h, 'kontak utama') || $h === 'nomor telepon' || $h === 'telepon' || $h === 'no hp' || $h === 'no wa') {
                $colMap['parent_phone'] = $cIdx;
            }

            // 10. Aspirations & Schools
            elseif (str_contains($h, 'asal sekolah')) $colMap['previous_school'] = $cIdx;
            elseif (str_contains($h, 'sekolah yang direncanakan')) $colMap['planned_school_after'] = $cIdx;
            elseif (str_contains($h, 'hobby siswa') || str_contains($h, 'hobi')) $colMap['student_hobby'] = $cIdx;
            elseif (str_contains($h, 'cita-cita siswa')) $colMap['student_aspiration'] = $cIdx;
            elseif (str_contains($h, 'cita-cita orangtua')) $colMap['parent_aspiration'] = $cIdx;

            // 11. Health, Milestones & Questionnaire
            elseif (str_contains($h, 'usia kandungan')) $colMap['gestational_age'] = $cIdx;
            elseif (str_contains($h, 'waktu dalam kandungan')) $colMap['prenatal_condition'] = $cIdx;
            elseif (str_contains($h, 'cara persalinan')) $colMap['delivery_method'] = $cIdx;
            elseif (str_contains($h, 'tindakan persalinan')) $colMap['delivery_reason'] = $cIdx;
            elseif (str_contains($h, 'panjang badan ananda pada saat lahir') || str_contains($h, 'pada saat lahir (kg/cm)')) $colMap['birth_weight_length'] = $cIdx;
            elseif (str_contains($h, 'disusui oleh')) $colMap['breastfed_by'] = $cIdx;
            elseif (str_contains($h, 'mendapatkan asi')) $colMap['breastfed_duration'] = $cIdx;
            elseif (str_contains($h, 'susu formula')) $colMap['formula_start'] = $cIdx;
            elseif (str_contains($h, 'makanan tambahan')) $colMap['mpasi_start'] = $cIdx;
            elseif (str_contains($h, 'tumbuh gigi')) $colMap['teeth_age'] = $cIdx;
            elseif (str_contains($h, 'dapat berjalan')) $colMap['walking_age'] = $cIdx;
            elseif (str_contains($h, 'dapat berbicara')) $colMap['talking_age'] = $cIdx;
            elseif (str_contains($h, 'gangguan sebutkan')) $colMap['developmental_issues'] = $cIdx;
            elseif (str_contains($h, 'imunisasi yang pernah')) $colMap['immunizations'] = $cIdx;
            elseif (str_contains($h, 'penyakit berat')) $colMap['severe_diseases'] = $cIdx;
            elseif (str_contains($h, 'penyakit yang sering')) $colMap['frequent_diseases'] = $cIdx;
            elseif (str_contains($h, 'alergi terhadap makanan')) $colMap['food_allergy'] = $cIdx;
            elseif (str_contains($h, 'reaksi ananda terhadap alergi')) $colMap['allergy_reaction'] = $cIdx;
            elseif (str_contains($h, 'penyembuhan alergi')) $colMap['allergy_treatment'] = $cIdx;
            elseif (str_contains($h, 'alergi terhadap minuman')) $colMap['drink_allergy'] = $cIdx;
            elseif (str_contains($h, 'diperiksakan ke')) $colMap['preferred_doctor'] = $cIdx;
            elseif (str_contains($h, 'alternatif penyembuhan')) $colMap['treatment_alternative'] = $cIdx;
            elseif (str_contains($h, 'pesan orang tua atas kesehatan')) $colMap['school_health_notes'] = $cIdx;
            elseif (str_contains($h, 'memakai diapers')) $colMap['uses_diapers'] = $cIdx;
            elseif (str_contains($h, 'buang air pada umumnya')) $colMap['toilet_habit'] = $cIdx;
            elseif (str_contains($h, 'kencing/ngompol') || str_contains($h, 'ngompol')) $colMap['bedwetting'] = $cIdx;
            elseif (str_contains($h, 'kebiasaan ananda dalam menggosok gigi')) $colMap['toothbrush_habit'] = $cIdx;
            elseif (str_contains($h, 'berapa kali ananda gosok gigi')) $colMap['toothbrush_frequency'] = $cIdx;
            elseif (str_contains($h, 'menggosok gigi apakah ananda dibantu')) $colMap['toothbrush_assisted'] = $cIdx;
            elseif (str_contains($h, 'selera makan pagi')) $colMap['breakfast_appetite'] = $cIdx;
            elseif (str_contains($h, 'selera makan siang')) $colMap['lunch_appetite'] = $cIdx;
            elseif (str_contains($h, 'selera makan malam')) $colMap['dinner_appetite'] = $cIdx;
            elseif (str_contains($h, 'makan diantara waktu')) $colMap['snack_habit'] = $cIdx;
            elseif (str_contains($h, 'waktu makan, dilakukan sambil')) $colMap['eating_manner'] = $cIdx;
            elseif (str_contains($h, 'berdoa pada saat sebelum dan sesudah makan')) $colMap['prays_before_after_meal'] = $cIdx;
            elseif (str_contains($h, 'makanan yang disukai')) $colMap['favorite_food'] = $cIdx;
            elseif (str_contains($h, 'makanan yang tidak disukai')) $colMap['disliked_food'] = $cIdx;
            elseif (str_contains($h, 'minuman yang disukai')) $colMap['favorite_drink'] = $cIdx;
            elseif (str_contains($h, 'minuman yang tidak disukai')) $colMap['disliked_drink'] = $cIdx;
            elseif (str_contains($h, 'bagaimana ananda tidur')) $colMap['sleeping_with'] = $cIdx;
            elseif (str_contains($h, 'tidur malam pukul')) $colMap['bedtime'] = $cIdx;
            elseif (str_contains($h, 'tidur siang pukul')) $colMap['nap_time'] = $cIdx;
            elseif (str_contains($h, 'bangun tidur siang')) $colMap['wake_nap_time'] = $cIdx;
            elseif (str_contains($h, 'bangun pagi pukul')) $colMap['wake_morning_time'] = $cIdx;
            elseif (str_contains($h, 'kamar tidur sendiri')) $colMap['has_own_bedroom'] = $cIdx;
            elseif (str_contains($h, 'mengigau dalam tidur')) $colMap['sleep_talking'] = $cIdx;
            elseif (str_contains($h, 'diajari doa')) $colMap['sleep_prayers'] = $cIdx;
            elseif (str_contains($h, 'hal - hal pada waktu tidur') || str_contains($h, 'hal-hal pada waktu tidur')) $colMap['sleep_notes'] = $cIdx;

            // 12. Psychology & Habits
            elseif (str_contains($h, 'karakter atau sifat')) $colMap['character_traits'] = $cIdx;
            elseif (str_contains($h, 'kepatuhan ananda')) $colMap['obedience'] = $cIdx;
            elseif (str_contains($h, 'tingkah laku ananda')) $colMap['behavior_notes'] = $cIdx;
            elseif (str_contains($h, 'kebiasaan bermain')) $colMap['play_habits'] = $cIdx;
            elseif (str_contains($h, 'tempat bermain')) $colMap['play_location'] = $cIdx;
            elseif (str_contains($h, 'bermain bersama siapa')) $colMap['playmates'] = $cIdx;
            elseif (str_contains($h, 'mainan yang disenangi')) $colMap['favorite_toys'] = $cIdx;
            elseif (str_contains($h, 'mainan yang tidak disenangi')) $colMap['disliked_toys'] = $cIdx;
            elseif (str_contains($h, 'dinilai orang tua berbahaya')) $colMap['dangerous_toys'] = $cIdx;
            elseif (str_contains($h, 'melihat televisi') && str_contains($h, 'sering')) $colMap['tv_frequency'] = $cIdx;
            elseif (str_contains($h, 'mendampingi ananda saat menonton')) $colMap['tv_companion'] = $cIdx;
            elseif (str_contains($h, 'didampingi ketika melihat televisi')) $colMap['tv_accompanied'] = $cIdx;
            elseif (str_contains($h, 'kosentrasi ananda ketika melihat')) $colMap['tv_concentration'] = $cIdx;
            elseif (str_contains($h, 'acara tv yang disukai')) $colMap['favorite_tv'] = $cIdx;
            elseif (str_contains($h, 'acara tv yang tidak disukai')) $colMap['disliked_tv'] = $cIdx;
            elseif (str_contains($h, 'menindaklanjuti')) $colMap['assessment_agreement'] = $cIdx;
            elseif (str_contains($h, 'tindak lanjut yang ayah-bunda setujui')) $colMap['agreed_followup_types'] = $cIdx;
            elseif (str_contains($h, 'saran-saran orang tua')) $colMap['parent_suggestions'] = $cIdx;

            // 13. Student identity
            elseif ($h === 'nisn' || (str_contains($h, 'nisn') && !str_contains($h, 'upload') && !str_contains($h, 'game') && !str_contains($h, 'sejenisnya'))) {
                $colMap['nisn'] = $cIdx;
            }
            elseif (str_contains($h, 'kelamin') || str_contains($h, 'l/p') || str_contains($h, 'gender') || $h === 'jk') $colMap['gender'] = $cIdx;
            elseif (($h === 'nis' || str_starts_with($h, 'nis ') || str_contains($h, 'nomor induk siswa') || str_contains($h, 'no. induk')) && !str_contains($h, 'nisn') && !str_contains($h, 'imunisasi')) {
                if (!isset($colMap['nis'])) { $colMap['nis'] = $cIdx; }
            }
            elseif (str_contains($h, 'nik ananda') || str_contains($h, 'nik siswa') || ($h === 'nik')) $colMap['nik'] = $cIdx;
            elseif (str_contains($h, 'kartu keluarga') || str_contains($h, 'no. kk') || str_contains($h, 'nomor kk') || $h === 'kk') $colMap['no_kk'] = $cIdx;
            elseif (str_contains($h, 'akta')) $colMap['no_akta'] = $cIdx;
            elseif (str_contains($h, 'nama lengkap') || (str_contains($h, 'nama') && (str_contains($h, 'ananda') || str_contains($h, 'siswa')))) {
                if (!isset($colMap['full_name']) || str_contains($h, 'nama lengkap')) { $colMap['full_name'] = $cIdx; }
            }
            elseif (str_contains($h, 'panggilan')) $colMap['nickname'] = $cIdx;
            elseif (str_contains($h, 'tempat lahir')) $colMap['birth_place'] = $cIdx;
            elseif (str_contains($h, 'tanggal lahir') || str_contains($h, 'tgl lahir')) $colMap['birth_date'] = $cIdx;
            elseif ($h === 'agama') $colMap['religion'] = $cIdx;
            elseif (str_contains($h, 'kewarganegaraan') || str_contains($h, 'wni')) $colMap['citizenship'] = $cIdx;
            elseif (str_contains($h, 'alamat')) $colMap['address'] = $cIdx;
            elseif ($h === 'rt') $colMap['rt'] = $cIdx;
            elseif ($h === 'rw') $colMap['rw'] = $cIdx;
            elseif (str_contains($h, 'kelurahan') || str_contains($h, 'desa')) $colMap['kelurahan'] = $cIdx;
            elseif (str_contains($h, 'kecamatan')) $colMap['kecamatan'] = $cIdx;
            elseif (str_contains($h, 'kota') || str_contains($h, 'kabupaten')) $colMap['city'] = $cIdx;
            elseif (str_contains($h, 'kode pos') || str_contains($h, 'kodepos')) $colMap['postal_code'] = $cIdx;
            elseif (str_contains($h, 'transportasi')) $colMap['transportation'] = $cIdx;
            elseif (str_contains($h, 'anak ke')) $colMap['child_order'] = $cIdx;
            elseif (str_contains($h, 'jumlah saudara kandung') || str_contains($h, 'saudara kandung')) $colMap['siblings_count'] = $cIdx;
            elseif (str_contains($h, 'golongan darah') || $h === 'darah') $colMap['blood_type'] = $cIdx;
            elseif (str_contains($h, 'detail kebutuhan khusus') || str_contains($h, 'rincian kebutuhan khusus') || str_contains($h, 'kebutuhan khusus detail')) {
                $colMap['special_needs_detail'] = $cIdx;
            }
            elseif (str_contains($h, 'kebutuhan khusus') || str_contains($h, 'disabilitas')) {
                if (!isset($colMap['special_needs'])) { $colMap['special_needs'] = $cIdx; }
            }
            elseif (str_contains($h, 'tinggi badan') || str_contains($h, 'tinggi bandan') || $h === 'tb') $colMap['height'] = $cIdx;
            elseif (str_contains($h, 'berat badan') || $h === 'bb') $colMap['weight'] = $cIdx;
            elseif (str_contains($h, 'lingkar kepala') || $h === 'lk') $colMap['head_circumference'] = $cIdx;
            elseif (str_contains($h, 'lingkar lengan') || str_contains($h, 'lila')) $colMap['arm_circumference'] = $cIdx;
            elseif (str_contains($h, 'jarak')) $colMap['distance_to_school'] = $cIdx;
            elseif (str_contains($h, 'waktu tempuh')) $colMap['travel_time_minutes'] = $cIdx;
            elseif (str_contains($h, 'sub unit')) $colMap['sub_unit'] = $cIdx;
            elseif (str_contains($h, 'jenjang')) $colMap['jenjang'] = $cIdx;
            elseif (str_contains($h, 'daycare')) $colMap['daycare'] = $cIdx;
            elseif (str_contains($h, 'ikut tpq')) $colMap['is_tpq'] = $cIdx;
            elseif (str_contains($h, 'kelompok tpq')) $colMap['tpq_rombel'] = $cIdx;
            elseif (str_contains($h, 'kelompok') || str_contains($h, 'rombel') || $h === 'kelas') {
                if (!isset($colMap['classroom'])) { $colMap['classroom'] = $cIdx; }
            }
            elseif (str_contains($h, 'pin')) $colMap['pin_access'] = $cIdx;
            elseif (str_contains($h, 'status')) $colMap['status'] = $cIdx;
            elseif (str_contains($h, 'catatan')) $colMap['notes'] = $cIdx;
        }

        // Positional fallback for legacy 24-column template
        if (!isset($colMap['full_name'])) {
            $colMap['full_name'] = 3;
            $colMap['nis'] = 0;
            $colMap['nisn'] = 1;
            $colMap['nik'] = 2;
            $colMap['nickname'] = 4;
            $colMap['gender'] = 5;
            $colMap['birth_place'] = 6;
            $colMap['birth_date'] = 7;
            $colMap['religion'] = 8;
            $colMap['sub_unit'] = 9;
            $colMap['jenjang'] = 10;
            $colMap['classroom'] = 11;
            $colMap['daycare'] = 12;
            $colMap['is_tpq'] = 13;
            $colMap['tpq_rombel'] = 14;
            $colMap['father_name'] = 15;
            $colMap['father_phone'] = 16;
            $colMap['mother_name'] = 17;
            $colMap['mother_phone'] = 18;
            $colMap['parent_phone'] = 19;
            $colMap['address'] = 20;
            $colMap['city'] = 21;
            $colMap['pin_access'] = 22;
            $colMap['status'] = 23;
        }

        $activeAY = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        $defaultAYId = $request->input('default_academic_year_id', $activeAY?->id);
        $defaultClassroomId = $request->input('default_classroom_id');

        $errors = [];
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($dataRows as $index => $row) {
            $rowNum = $index + $headerRowIdx + 2;

            $getVal = function ($key) use ($row, $colMap) {
                if (isset($colMap[$key]) && isset($row[$colMap[$key]])) {
                    $v = trim((string)$row[$colMap[$key]]);
                    return $v !== '' ? $v : null;
                }
                return null;
            };

            $fullName = $getVal('full_name');
            if (empty($fullName)) {
                continue;
            }

            $limitStr = function ($key, $len = 150) use ($getVal) {
                $val = $getVal($key);
                return $val !== null ? mb_substr((string)$val, 0, $len) : null;
            };

            $nis = $limitStr('nis', 50);
            $nisn = $limitStr('nisn', 50);
            $nik = $limitStr('nik', 50);
            $noKk = $limitStr('no_kk', 50);
            $noAkta = $limitStr('no_akta', 50);
            $nickname = $limitStr('nickname', 100);
            $genderRaw = $getVal('gender') ?? 'L';
            $birthPlace = $limitStr('birth_place', 100);
            $birthDateRaw = isset($colMap['birth_date']) ? ($row[$colMap['birth_date']] ?? null) : null;
            $religion = $limitStr('religion', 50) ?? 'Islam';
            $citizenship = $limitStr('citizenship', 50) ?? 'WNI';
            $address = $getVal('address');
            $rt = $limitStr('rt', 10);
            $rw = $limitStr('rw', 10);
            $kelurahan = $limitStr('kelurahan', 100);
            $kecamatan = $limitStr('kecamatan', 100);
            $city = $limitStr('city', 100) ?? 'Malang';
            $dusun = $limitStr('dusun', 100);
            $postalCode = $limitStr('postal_code', 20);
            $livingWith = $limitStr('living_with', 100) ?? 'Bersama Orang Tua';
            $transportation = $limitStr('transportation', 100);
            $childOrder = is_numeric($getVal('child_order')) ? (int)$getVal('child_order') : null;
            $siblingsCount = is_numeric($getVal('siblings_count')) ? (int)$getVal('siblings_count') : null;
            $bloodType = $limitStr('blood_type', 10);
            $specialNeeds = $limitStr('special_needs', 100) ?? 'Tidak';
            $specialNeedsDetail = $limitStr('special_needs_detail', 255);

            // Smart extraction for numeric measurements with realistic bounds for PAUD children
            $extractNum = function ($val, $max = 250) {
                if ($val === null || $val === '') return null;
                $valStr = trim((string)$val);
                $num = null;
                if (preg_match('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2})\b/i', $valStr, $m)) {
                    $num = (float)$m[1];
                } elseif (preg_match('/^\d{4}-\d{2}-(\d{1,2})/', $valStr, $m)) {
                    $num = (float)$m[1];
                } elseif (preg_match('/(\d+(?:[\.,]\d+)?)/', $valStr, $m)) {
                    $num = (float)str_replace(',', '.', $m[1]);
                } elseif (is_numeric($val)) {
                    $num = (float)$val;
                }
                return ($num !== null && $num > 0 && $num <= $max) ? $num : null;
            };

            $height = $extractNum($getVal('height'), 250);
            $weight = $extractNum($getVal('weight'), 150);
            $headCirc = $extractNum($getVal('head_circumference'), 100);
            $armCirc = $extractNum($getVal('arm_circumference'), 60);

            // Distance to school normalization
            $distanceRaw = $getVal('distance_to_school');
            $distanceToSchool = null;
            if (!empty($distanceRaw)) {
                $distStr = (string)$distanceRaw;
                if (preg_match('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2})\b/i', $distStr, $m)) {
                    $distanceToSchool = $m[1] . ' km';
                } elseif (preg_match('/^\d{4}-\d{2}-(\d{2})/', $distStr, $m)) {
                    $distanceToSchool = ((int)$m[1]) . ' km';
                } else {
                    $distanceToSchool = mb_substr($distStr, 0, 150);
                }
            }

            $travelTime = is_numeric($getVal('travel_time_minutes')) ? (int)$getVal('travel_time_minutes') : null;

            $previousSchool = $limitStr('previous_school', 255);
            $isPaudFormalBefore = in_array(strtolower($getVal('is_paud_formal_before') ?? ''), ['ya', 'true', '1', 'pernah']);
            $plannedSchoolAfter = $limitStr('planned_school_after', 150);
            $studentAspiration = $limitStr('student_aspiration', 150);
            $studentHobby = $limitStr('student_hobby', 150);
            $parentAspiration = $getVal('parent_aspiration');
            $parentAspirationPaud = $getVal('parent_aspiration_paud');
            if (!empty($parentAspirationPaud)) {
                $parentAspiration = $parentAspiration ? ($parentAspiration . ' | Harapan selesai PAUD: ' . $parentAspirationPaud) : $parentAspirationPaud;
            }

            $subUnitRaw = strtoupper($getVal('sub_unit') ?? 'TK');
            $jenjangRaw = $getVal('jenjang');
            $kelompokRaw = $getVal('classroom');
            $daycareRaw = $getVal('daycare');
            $tpqRaw = $getVal('is_tpq');
            $tpqKelompokRaw = $getVal('tpq_rombel');

            // Father fields
            $fatherName = $limitStr('father_name', 255);
            $fatherNik = $limitStr('father_nik', 50);
            $fatherBirthPlace = $limitStr('father_birth_place', 100);
            $fatherBirthDateRaw = $getVal('father_birth_date');
            $fatherBirthDate = null;
            if (!empty($fatherBirthDateRaw)) {
                $ts = strtotime(str_replace('/', '-', (string)$fatherBirthDateRaw));
                if ($ts !== false) $fatherBirthDate = date('Y-m-d', $ts);
            }
            $fatherEdu = $limitStr('father_education', 100);
            $fatherJob = $limitStr('father_job', 100);
            $fatherCompany = $limitStr('father_company', 150);
            $fatherIncome = $limitStr('father_income', 50);
            $fatherTitlePrefix = $limitStr('father_title_prefix', 150);
            $fatherTitleSuffix = $limitStr('father_title_suffix', 150);
            $fatherPhone = $limitStr('father_phone', 50);

            // Mother fields
            $motherName = $limitStr('mother_name', 255);
            $motherNik = $limitStr('mother_nik', 50);
            $motherBirthPlace = $limitStr('mother_birth_place', 100);
            $motherBirthDateRaw = $getVal('mother_birth_date');
            $motherBirthDate = null;
            if (!empty($motherBirthDateRaw)) {
                $ts = strtotime(str_replace('/', '-', (string)$motherBirthDateRaw));
                if ($ts !== false) $motherBirthDate = date('Y-m-d', $ts);
            }
            $motherEdu = $limitStr('mother_education', 100);
            $motherJob = $limitStr('mother_job', 100);
            $motherCompany = $limitStr('mother_company', 150);
            $motherIncome = $limitStr('mother_income', 50);
            $motherTitlePrefix = $limitStr('mother_title_prefix', 150);
            $motherTitleSuffix = $limitStr('mother_title_suffix', 150);
            $motherPhone = $limitStr('mother_phone', 50);

            // Guardian fields
            $guardianName = $limitStr('guardian_name', 255);
            $guardianNik = $limitStr('guardian_nik', 50);
            $guardianBirthPlace = $limitStr('guardian_birth_place', 100);
            $guardianBirthDateRaw = $getVal('guardian_birth_date');
            $guardianBirthDate = null;
            if (!empty($guardianBirthDateRaw)) {
                $ts = strtotime(str_replace('/', '-', (string)$guardianBirthDateRaw));
                if ($ts !== false) $guardianBirthDate = date('Y-m-d', $ts);
            }
            $guardianEdu = $limitStr('guardian_education', 100);
            $guardianJob = $limitStr('guardian_job', 100);
            $guardianCompany = $limitStr('guardian_company', 150);
            $guardianIncome = $limitStr('guardian_income', 50);
            $guardianTitlePrefix = $limitStr('guardian_title_prefix', 150);
            $guardianTitleSuffix = $limitStr('guardian_title_suffix', 150);
            $guardianRelation = $limitStr('guardian_relation', 100);
            $isAccompaniedGuardian = in_array(strtolower($getVal('is_accompanied_by_guardian') ?? ''), ['ya', 'true', '1']);
            $guardianPhone = $limitStr('guardian_phone', 50);

            // Contacts
            $parentPhone = $limitStr('parent_phone', 50) ?? ($fatherPhone ?: $motherPhone ?: $guardianPhone);
            $parentEmail = $limitStr('parent_email', 100);
            $emergencyContact = $limitStr('emergency_contact', 150);
            $emergencyContactPhone = $limitStr('emergency_contact_phone', 50);
            $emergencyContactRelation = $limitStr('emergency_contact_relation', 100);
            $pinAccess = $limitStr('pin_access', 50) ?? '1234';
            $statusRaw = strtolower($getVal('status') ?? 'aktif');
            $notes = $getVal('notes');

            // Normalize gender
            $gender = in_array(strtoupper($genderRaw), ['P', 'PEREMPUAN', 'FEMALE', 'W']) ? 'P' : 'L';

            // Parse Birth Date
            $birthDate = null;
            if (!empty($birthDateRaw)) {
                if (is_numeric($birthDateRaw)) {
                    try {
                        $birthDate = ExcelDate::excelToDateTimeObject($birthDateRaw)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $birthDate = null;
                    }
                } else {
                    $ts = strtotime(str_replace('/', '-', (string)$birthDateRaw));
                    if ($ts !== false) {
                        $birthDate = date('Y-m-d', $ts);
                    }
                }
            }

            // Resolve Classroom, Jenjang, Level & Sub-Unit dynamically
            $rawClassString = trim((string)$kelompokRaw);
            $parts = array_map('trim', explode(',', $rawClassString));

            $matchedClassroom = null;
            $daycareClassroomId = null;
            $isTpq = in_array(strtolower($tpqRaw ?? ''), ['ya', 'true', '1', 'tpq', 'ikut']);
            $tpqClassroomId = null;

            foreach ($parts as $part) {
                $p = trim($part);
                if ($p === '') continue;

                if (stripos($p, 'TPQ') !== false) {
                    $isTpq = true;
                    $tpqCls = Classroom::whereHas('jenjang', function($jq) {
                        $jq->where('code', 'TPQ')->orWhere('name', 'like', '%TPQ%');
                    })->where('name', 'like', "%{$p}%")->first() ?? Classroom::where('code', 'TPQ')->first();
                    $tpqClassroomId = $tpqCls?->id;
                } elseif (stripos($p, 'TPA') !== false || stripos($p, 'DAYCARE') !== false) {
                    $dc = Classroom::whereHas('jenjang', function($jq) {
                        $jq->whereIn('code', ['DAYCARE', 'TPA'])->orWhere('name', 'like', '%Daycare%')->orWhere('name', 'like', '%TPA%');
                    })->where('name', 'like', "%{$p}%")->first() ?? Classroom::where('name', 'like', "%{$p}%")->first();
                    if ($dc) {
                        $daycareClassroomId = $dc->id;
                    }
                } else {
                    $cls = Classroom::with(['jenjang', 'classLevel.jenjang'])->where('name', 'like', "%{$p}%")->first();
                    if ($cls) {
                        $matchedClassroom = $cls;
                    }
                }
            }

            if (!$matchedClassroom && $daycareClassroomId) {
                $matchedClassroom = Classroom::with(['jenjang', 'classLevel.jenjang'])->find($daycareClassroomId);
            }

            if (!$matchedClassroom && !empty($parts[0])) {
                $matchedClassroom = Classroom::with(['jenjang', 'classLevel.jenjang'])->where('name', 'like', "%{$parts[0]}%")->first();
            }

            $classroomId = $matchedClassroom?->id ?? $defaultClassroomId;
            $classLevelId = $matchedClassroom?->class_level_id;
            $jenjangId = $matchedClassroom?->jenjang_id ?? $matchedClassroom?->classLevel?->jenjang_id;
            $subUnit = $matchedClassroom?->jenjang?->code ?? $matchedClassroom?->sub_unit ?? 'TK';

            if ($matchedClassroom) {
                $cName = strtoupper($matchedClassroom->name);
                if (str_starts_with($cName, 'KB') || str_contains($cName, 'PLAYGROUP')) {
                    $subUnit = 'PG';
                    $j = Jenjang::where('code', 'KB')->first();
                    if ($j) $jenjangId = $j->id;
                } elseif (str_starts_with($cName, 'TK')) {
                    $subUnit = 'TK';
                    $j = Jenjang::where('code', 'TK')->first();
                    if ($j) $jenjangId = $j->id;
                } elseif (str_starts_with($cName, 'TPA') || str_contains($cName, 'DAYCARE')) {
                    $subUnit = 'DAYCARE';
                    $j = Jenjang::whereIn('code', ['DAYCARE', 'TPA'])->first();
                    if ($j) $jenjangId = $j->id;
                } elseif (str_contains($cName, 'TPQ')) {
                    $subUnit = 'TPQ';
                    $j = Jenjang::where('code', 'TPQ')->first();
                    if ($j) $jenjangId = $j->id;
                }
            }

            if (!$classLevelId && !empty($jenjangRaw)) {
                $matchedLevel = ClassLevel::with('jenjang')->where('name', 'like', "%{$jenjangRaw}%")->first();
                if ($matchedLevel) {
                    $classLevelId = $matchedLevel->id;
                    if (!$jenjangId) $jenjangId = $matchedLevel->jenjang_id;
                    if (!$subUnit) $subUnit = $matchedLevel->jenjang?->code;
                }
            }

            if (!$jenjangId && !empty($jenjangRaw)) {
                $matchedJenjang = Jenjang::where('name', 'like', "%{$jenjangRaw}%")->orWhere('code', 'like', "%{$jenjangRaw}%")->first();
                if ($matchedJenjang) {
                    $jenjangId = $matchedJenjang->id;
                    $subUnit = $matchedJenjang->code;
                }
            }

            if (!$daycareClassroomId && !empty($daycareRaw)) {
                $dc = Classroom::whereHas('jenjang', function($jq) {
                    $jq->whereIn('code', ['DAYCARE', 'TPA'])->orWhere('name', 'like', '%Daycare%')->orWhere('name', 'like', '%TPA%');
                })->where('name', 'like', "%{$daycareRaw}%")->first() ?? Classroom::where('name', 'like', "%{$daycareRaw}%")->first();
                $daycareClassroomId = $dc?->id;
            }

            if (!$tpqClassroomId && !empty($tpqKelompokRaw)) {
                $tpqCls = Classroom::whereHas('jenjang', function($jq) {
                    $jq->where('code', 'TPQ')->orWhere('name', 'like', '%TPQ%');
                })->where('name', 'like', "%{$tpqKelompokRaw}%")->first() ?? Classroom::where('name', 'like', "%{$tpqKelompokRaw}%")->first();
                $tpqClassroomId = $tpqCls?->id;
                if ($tpqClassroomId) $isTpq = true;
            }

            // Match student by NIS, NIK, or Full Name + Birth Date
            $student = null;
            if (!empty($nis)) {
                $student = Student::where('nis', $nis)->first();
            }
            if (!$student && !empty($nik)) {
                $student = Student::where('nik', $nik)->first();
            }
            if (!$student && $birthDate && $fullName) {
                $student = Student::where('full_name', $fullName)->where('birth_date', $birthDate)->first();
            }

            // Prepare Family Data JSON
            $familyData = [
                'father' => [
                    'name' => $fatherName,
                    'nik' => $fatherNik,
                    'birth_place' => $fatherBirthPlace,
                    'birth_date' => $fatherBirthDate,
                    'education' => $fatherEdu,
                    'job' => $fatherJob,
                    'company' => $fatherCompany,
                    'income' => $fatherIncome,
                    'title_prefix' => $fatherTitlePrefix,
                    'title' => $fatherTitleSuffix,
                    'phone' => $fatherPhone,
                ],
                'mother' => [
                    'name' => $motherName,
                    'nik' => $motherNik,
                    'birth_place' => $motherBirthPlace,
                    'birth_date' => $motherBirthDate,
                    'education' => $motherEdu,
                    'job' => $motherJob,
                    'company' => $motherCompany,
                    'income' => $motherIncome,
                    'title_prefix' => $motherTitlePrefix,
                    'title' => $motherTitleSuffix,
                    'phone' => $motherPhone,
                ],
                'guardian' => [
                    'is_active' => !empty($guardianName),
                    'name' => $guardianName,
                    'nik' => $guardianNik,
                    'birth_place' => $guardianBirthPlace,
                    'birth_date' => $guardianBirthDate,
                    'education' => $guardianEdu,
                    'job' => $guardianJob,
                    'company' => $guardianCompany,
                    'income' => $guardianIncome,
                    'title_prefix' => $guardianTitlePrefix,
                    'title' => $guardianTitleSuffix,
                    'relation' => $guardianRelation,
                    'is_accompanied' => $isAccompaniedGuardian,
                    'phone' => $guardianPhone,
                ],
                'environment' => [
                    'house_ownership' => $getVal('house_ownership'),
                    'housing_environment' => $getVal('housing_environment'),
                    'has_playyard' => $getVal('has_playyard'),
                    'playyard_state' => $getVal('playyard_state'),
                    'adult_residents' => $getVal('adult_residents'),
                    'child_residents' => $getVal('child_residents'),
                ],
                'relationships' => [
                    'closest_bond' => $getVal('closest_bond'),
                    'rel_father' => $getVal('rel_father'),
                    'rel_mother' => $getVal('rel_mother'),
                    'rel_siblings' => $getVal('rel_siblings'),
                    'rel_grandparents' => $getVal('rel_grandparents'),
                    'rel_peers' => $getVal('rel_peers'),
                    'peer_opportunity' => $getVal('peer_opportunity'),
                    'comm_father' => $getVal('comm_father'),
                    'comm_mother' => $getVal('comm_mother'),
                    'trusted_pickup' => $getVal('trusted_pickup'),
                ],
                'religious_climate' => [
                    'climate' => $getVal('religious_climate'),
                    'family_worship' => $getVal('family_worship'),
                    'nearby_mosque' => $getVal('nearby_mosque'),
                    'nearby_tpq' => $getVal('nearby_tpq'),
                ]
            ];

            // Prepare Health Habits JSON
            $healthHabits = [
                'surroundings' => $getVal('housing_environment'),
                'near_mosque' => $getVal('nearby_mosque'),
                'external_tpq' => $getVal('nearby_tpq'),
                'birth_history' => [
                    'gestational_age' => $getVal('gestational_age'),
                    'prenatal_condition' => $getVal('prenatal_condition'),
                    'delivery_method' => $getVal('delivery_method'),
                    'delivery_reason' => $getVal('delivery_reason'),
                    'birth_weight_length' => $getVal('birth_weight_length'),
                ],
                'milestones' => [
                    'breastfed_by' => $getVal('breastfed_by'),
                    'breastfed_duration' => $getVal('breastfed_duration'),
                    'formula_start' => $getVal('formula_start'),
                    'mpasi_start' => $getVal('mpasi_start'),
                    'teeth_age' => $getVal('teeth_age'),
                    'walking_age' => $getVal('walking_age'),
                    'talking_age' => $getVal('talking_age'),
                    'developmental_issues' => $getVal('developmental_issues'),
                ],
                'medical_history' => [
                    'immunizations' => $getVal('immunizations'),
                    'severe_diseases' => $getVal('severe_diseases'),
                    'frequent_diseases' => $getVal('frequent_diseases'),
                    'food_allergy' => $getVal('food_allergy'),
                    'allergy_reaction' => $getVal('allergy_reaction'),
                    'allergy_treatment' => $getVal('allergy_treatment'),
                    'drink_allergy' => $getVal('drink_allergy'),
                    'preferred_doctor' => $getVal('preferred_doctor'),
                    'treatment_alternative' => $getVal('treatment_alternative'),
                    'school_health_notes' => $getVal('school_health_notes'),
                ],
                'toilet_training' => [
                    'uses_diapers' => $getVal('uses_diapers'),
                    'toilet_habit' => $getVal('toilet_habit'),
                    'bedwetting' => $getVal('bedwetting'),
                    'toothbrush_habit' => $getVal('toothbrush_habit'),
                    'toothbrush_frequency' => $getVal('toothbrush_frequency'),
                    'toothbrush_assisted' => $getVal('toothbrush_assisted'),
                ],
                'meals' => [
                    'breakfast' => $getVal('breakfast_appetite'),
                    'lunch' => $getVal('lunch_appetite'),
                    'dinner' => $getVal('dinner_appetite'),
                    'snack_habit' => $getVal('snack_habit'),
                    'eating_manner' => $getVal('eating_manner'),
                    'prays_before_after' => $getVal('prays_before_after_meal'),
                    'favorite' => $getVal('favorite_food'),
                    'avoided' => $getVal('disliked_food'),
                    'favorite_drink' => $getVal('favorite_drink'),
                    'avoided_drink' => $getVal('disliked_drink'),
                ],
                'sleeping' => [
                    'sleeping_with' => $getVal('sleeping_with'),
                    'bedtime' => $getVal('bedtime'),
                    'nap_time' => $getVal('nap_time'),
                    'wake_nap_time' => $getVal('wake_nap_time'),
                    'wake_morning_time' => $getVal('wake_morning_time'),
                    'has_own_bedroom' => $getVal('has_own_bedroom'),
                    'sleep_talking' => $getVal('sleep_talking'),
                    'sleep_prayers' => $getVal('sleep_prayers'),
                    'sleep_notes' => $getVal('sleep_notes'),
                ]
            ];

            // Prepare Development Psychology JSON
            $devPsychology = [
                'traits' => [
                    'character_traits' => $getVal('character_traits'),
                    'obedience' => $getVal('obedience'),
                    'behavior_notes' => $getVal('behavior_notes'),
                ],
                'play' => [
                    'play_habits' => $getVal('play_habits'),
                    'play_location' => $getVal('play_location'),
                    'playmates' => $getVal('playmates'),
                    'favorite_toys' => $getVal('favorite_toys'),
                    'disliked_toys' => $getVal('disliked_toys'),
                    'dangerous_toys' => $getVal('dangerous_toys'),
                ],
                'screen_time' => [
                    'game_facility' => $getVal('game_facility'),
                    'youtube_access' => $getVal('youtube_access'),
                    'tv_frequency' => $getVal('tv_frequency'),
                    'tv_companion' => $getVal('tv_companion'),
                    'tv_accompanied' => $getVal('tv_accompanied'),
                    'tv_concentration' => $getVal('tv_concentration'),
                    'favorite_tv' => $getVal('favorite_tv'),
                    'disliked_tv' => $getVal('disliked_tv'),
                    'youtube_reaction' => $getVal('youtube_reaction'),
                ],
                'commitments' => [
                    'assessment_agreement' => $getVal('assessment_agreement'),
                    'agreed_followup_types' => $getVal('agreed_followup_types'),
                    'paud_outcome_expectation' => $getVal('parent_aspiration_paud'),
                    'parent_suggestions' => $getVal('parent_suggestions'),
                ]
            ];

            // Extract Google Drive digital scan document links
            $rawDocs = [
                'nisn_scan' => $getVal('doc_nisn'),
                'previous_school_cert' => $getVal('doc_previous_school'),
                'kia_scan' => $getVal('doc_kia'),
                'last_report_card' => $getVal('doc_report'),
                'assessment_file' => $getVal('doc_assessment'),
                'kms_book' => $getVal('doc_kms'),
            ];
            $existingDocs = is_array($student?->documents) ? $student->documents : [];
            $newDocs = array_filter($rawDocs, fn($v) => !is_null($v) && $v !== '');
            $documents = array_merge($existingDocs, $newDocs);

            $data = [
                'nis' => $nis,
                'nisn' => $nisn,
                'nik' => $nik,
                'no_kk' => $noKk,
                'no_akta' => $noAkta,
                'full_name' => $fullName,
                'nickname' => $nickname,
                'gender' => $gender,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'religion' => $religion,
                'citizenship' => $citizenship,
                'address' => $address,
                'dusun' => $dusun,
                'rt' => $rt,
                'rw' => $rw,
                'kelurahan' => $kelurahan,
                'kecamatan' => $kecamatan,
                'city' => $city,
                'postal_code' => $postalCode,
                'living_with' => $livingWith,
                'transportation' => $transportation,
                'child_order' => $childOrder,
                'siblings_count' => $siblingsCount,
                'blood_type' => $bloodType,
                'special_needs' => $specialNeeds,
                'special_needs_detail' => $specialNeedsDetail,
                'height' => $height,
                'weight' => $weight,
                'head_circumference' => $headCirc,
                'arm_circumference' => $armCirc,
                'distance_to_school' => $distanceToSchool,
                'travel_time_minutes' => $travelTime,
                'previous_school' => $previousSchool,
                'is_paud_formal_before' => $isPaudFormalBefore,
                'planned_school_after' => $plannedSchoolAfter,
                'student_aspiration' => $studentAspiration,
                'student_hobby' => $studentHobby,
                'parent_aspiration' => $parentAspiration,
                'jenjang_id' => $jenjangId,
                'sub_unit' => $subUnit,
                'class_level_id' => $classLevelId,
                'classroom_id' => $classroomId,
                'daycare_classroom_id' => $daycareClassroomId,
                'is_tpq' => $isTpq,
                'tpq_classroom_id' => $tpqClassroomId,
                'academic_year_id' => $defaultAYId,

                // Father dedicated fields
                'father_name' => $fatherName,
                'father_nik' => $fatherNik,
                'father_birth_place' => $fatherBirthPlace,
                'father_birth_date' => $fatherBirthDate,
                'father_education' => $fatherEdu,
                'father_job' => $fatherJob,
                'father_company' => $fatherCompany,
                'father_income' => $fatherIncome,
                'father_title_prefix' => $fatherTitlePrefix,
                'father_title_suffix' => $fatherTitleSuffix,
                'father_phone' => $fatherPhone,

                // Mother dedicated fields
                'mother_name' => $motherName,
                'mother_nik' => $motherNik,
                'mother_birth_place' => $motherBirthPlace,
                'mother_birth_date' => $motherBirthDate,
                'mother_education' => $motherEdu,
                'mother_job' => $motherJob,
                'mother_company' => $motherCompany,
                'mother_income' => $motherIncome,
                'mother_title_prefix' => $motherTitlePrefix,
                'mother_title_suffix' => $motherTitleSuffix,
                'mother_phone' => $motherPhone,

                // Guardian dedicated fields
                'guardian_name' => $guardianName,
                'guardian_nik' => $guardianNik,
                'guardian_birth_place' => $guardianBirthPlace,
                'guardian_birth_date' => $guardianBirthDate,
                'guardian_education' => $guardianEdu,
                'guardian_job' => $guardianJob,
                'guardian_company' => $guardianCompany,
                'guardian_income' => $guardianIncome,
                'guardian_title_prefix' => $guardianTitlePrefix,
                'guardian_title_suffix' => $guardianTitleSuffix,
                'guardian_relation' => $guardianRelation,
                'is_accompanied_by_guardian' => $isAccompaniedGuardian,
                'guardian_phone' => $guardianPhone,

                // Emergency & Access
                'parent_phone' => $parentPhone,
                'parent_email' => $parentEmail,
                'emergency_contact_name' => $emergencyContact,
                'emergency_contact_phone' => $emergencyContactPhone,
                'emergency_contact_relation' => $emergencyContactRelation,
                'family_data' => $familyData,
                'health_habits' => $healthHabits,
                'development_psychology' => $devPsychology,
                'documents' => !empty($documents) ? $documents : null,
                'pin_access' => $pinAccess,
                'status' => in_array($statusRaw, ['aktif', 'lulus', 'mutasi', 'keluar', 'nonaktif']) ? $statusRaw : 'aktif',
                'notes' => $notes,
                'enrolled_date' => now()->toDateString(),
            ];

            if ($student) {
                $student->update($data);
                $updatedCount++;
            } else {
                $student = Student::create($data);
                $importedCount++;
            }

            // Sync classroom history
            if ($student->classroom_id) {
                $student->load(['classroom.classLevel', 'classroom.homeroomTeacher']);
                StudentClassroomHistory::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $student->academic_year_id,
                        'classroom_id' => $student->classroom_id,
                    ],
                    [
                        'sub_unit' => $student->sub_unit,
                        'classroom_name' => $student->classroom?->name,
                        'grade_level' => $student->classroom?->classLevel?->name,
                        'homeroom_teacher_name' => $student->classroom?->homeroomTeacher?->name,
                        'status' => $student->status,
                        'start_date' => $student->enrolled_date ?? now()->toDateString(),
                        'notes' => 'Impor Data Excel Lengkap',
                    ]
                );
            }
        }

        $msg = "Proses impor selesai! {$importedCount} data murid baru berhasil ditambahkan, {$updatedCount} data murid diperbarui.";

        if (!empty($errors)) {
            return redirect()->route('students.index')
                ->with('success', $msg)
                ->with('import_errors', $errors);
        }

        return redirect()->route('students.index')->with('success', $msg);
    }
}

