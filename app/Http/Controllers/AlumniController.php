<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassroomHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AlumniController extends Controller
{
    /**
     * Display a listing of Alumni (Lulusan KB & TK) with multi-year history.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $activeAcademicYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $selectedYearId = $request->get('academic_year_id', 'all');
        $selectedSubUnit = $request->get('sub_unit', 'ALL');

        // Query students who have status 'lulus' or have a history entry with 'lulus'
        $query = Student::with([
            'classroom.classLevel',
            'classLevel',
            'academicYear',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'desc');
            }
        ])->where(function ($q) {
            $q->where('status', 'lulus')
              ->orWhereHas('classroomHistories', function ($hq) {
                  $hq->where('status', 'lulus');
              });
        });

        // Filter by Academic Year of Graduation
        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where(function ($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->orWhereHas('classroomHistories', function ($hq) use ($selectedYearId) {
                      $hq->where('academic_year_id', $selectedYearId)->where('status', 'lulus');
                  });
            });
        }

        // Filter by Sub Unit
        if ($selectedSubUnit && $selectedSubUnit !== 'ALL') {
            $query->where(function ($q) use ($selectedSubUnit) {
                $q->where('sub_unit', $selectedSubUnit)
                  ->orWhereHas('classroomHistories', function ($hq) use ($selectedSubUnit) {
                      $hq->where('sub_unit', $selectedSubUnit)->where('status', 'lulus');
                  });
            });
        }

        // Search query
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Gender filter
        if ($gender = $request->get('gender')) {
            if ($gender !== 'all') {
                $query->where('gender', $gender);
            }
        }

        // Base query for statistics
        $statsBaseQuery = Student::where(function ($q) {
            $q->where('status', 'lulus')
              ->orWhereHas('classroomHistories', function ($hq) {
                  $hq->where('status', 'lulus');
              });
        });

        if ($selectedYearId && $selectedYearId !== 'all') {
            $statsBaseQuery->where(function ($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->orWhereHas('classroomHistories', function ($hq) use ($selectedYearId) {
                      $hq->where('academic_year_id', $selectedYearId)->where('status', 'lulus');
                  });
            });
        }

        $totalAlumni = (clone $statsBaseQuery)->count();
        $alumniTk = (clone $statsBaseQuery)->where(function ($q) {
            $q->where('sub_unit', 'TK')
              ->orWhereHas('classroomHistories', function ($hq) {
                  $hq->where('sub_unit', 'TK')->where('status', 'lulus');
              });
        })->count();

        $alumniPg = (clone $statsBaseQuery)->where(function ($q) {
            $q->where('sub_unit', 'PG')
              ->orWhereHas('classroomHistories', function ($hq) {
                  $hq->where('sub_unit', 'PG')->where('status', 'lulus');
              });
        })->count();

        $maleAlumni = (clone $statsBaseQuery)->whereIn('gender', ['L', 'Laki-laki', 'Male'])->count();
        $femaleAlumni = (clone $statsBaseQuery)->whereIn('gender', ['P', 'Perempuan', 'Female'])->count();

        $stats = [
            'total' => $totalAlumni,
            'tk' => $alumniTk,
            'pg' => $alumniPg,
            'male' => $maleAlumni,
            'female' => $femaleAlumni,
        ];

        $alumniList = $query->orderBy('updated_at', 'desc')->orderBy('full_name', 'asc')->paginate(20)->withQueryString();

        return view('admin.alumni.index', compact(
            'alumniList',
            'stats',
            'academicYears',
            'activeAcademicYear',
            'selectedYearId',
            'selectedSubUnit'
        ));
    }

    /**
     * Show single alumni profile with complete chronological history journey.
     */
    public function show($id): JsonResponse
    {
        $student = Student::with([
            'classroom.classLevel',
            'classroom.homeroomTeacher',
            'classLevel',
            'academicYear',
            'spmbCandidate',
            'classroomHistories' => function ($q) {
                $q->with(['academicYear', 'classroom'])->orderBy('id', 'asc');
            }
        ])->findOrFail($id);

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

    /**
     * Update alumni notes (e.g. destination elementary school / SD lanjutan, achievements).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'parent_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Data catatan alumni {$student->full_name} berhasil diperbarui.",
            'student' => $student,
        ]);
    }

    /**
     * Restore an alumnus back to active status in a designated classroom.
     */
    public function restoreToActive(Request $request, $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'notes' => 'nullable|string',
        ]);

        $classroom = Classroom::with(['classLevel', 'homeroomTeacher'])->findOrFail($validated['classroom_id']);

        $student->update([
            'classroom_id' => $classroom->id,
            'class_level_id' => $classroom->class_level_id,
            'sub_unit' => $classroom->sub_unit ?: 'TK',
            'academic_year_id' => $validated['academic_year_id'],
            'status' => 'aktif',
            'notes' => $validated['notes'] ?? 'Diaktifkan kembali dari status alumni',
        ]);

        // Add history record
        StudentClassroomHistory::create([
            'student_id' => $student->id,
            'academic_year_id' => $validated['academic_year_id'],
            'classroom_id' => $classroom->id,
            'sub_unit' => $classroom->sub_unit ?: 'TK',
            'classroom_name' => $classroom->name,
            'grade_level' => $classroom->classLevel?->name,
            'homeroom_teacher_name' => $classroom->homeroomTeacher?->name,
            'status' => 'aktif',
            'start_date' => now()->toDateString(),
            'notes' => 'Re-aktivasi Murid dari Alumni',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Ananda {$student->full_name} berhasil diaktifkan kembali ke kelompok {$classroom->name}.",
        ]);
    }

    /**
     * Export Alumni data to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $selectedYearId = $request->get('academic_year_id', 'all');
        $selectedSubUnit = $request->get('sub_unit', 'ALL');

        $query = Student::with([
            'classroom.classLevel',
            'classLevel',
            'academicYear',
            'classroomHistories' => function ($q) {
                $q->with('academicYear')->orderBy('id', 'desc');
            }
        ])->where(function ($q) {
            $q->where('status', 'lulus')
              ->orWhereHas('classroomHistories', function ($hq) {
                  $hq->where('status', 'lulus');
              });
        });

        if ($selectedYearId && $selectedYearId !== 'all') {
            $query->where(function ($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)
                  ->orWhereHas('classroomHistories', function ($hq) use ($selectedYearId) {
                      $hq->where('academic_year_id', $selectedYearId)->where('status', 'lulus');
                  });
            });
        }

        if ($selectedSubUnit && $selectedSubUnit !== 'ALL') {
            $query->where(function ($q) use ($selectedSubUnit) {
                $q->where('sub_unit', $selectedSubUnit)
                  ->orWhereHas('classroomHistories', function ($hq) use ($selectedSubUnit) {
                      $hq->where('sub_unit', $selectedSubUnit)->where('status', 'lulus');
                  });
            });
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        $alumni = $query->orderBy('academic_year_id', 'desc')->orderBy('full_name', 'asc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Alumni');

        // Document Title
        $sheet->setCellValue('A1', 'BUKU INDUK & DAFTAR ALUMNI SISWA');
        $sheet->setCellValue('A2', 'KB - TK - DAYCARE ANAK SALEH MALANG');
        $yearText = ($selectedYearId && $selectedYearId !== 'all') 
            ? ($academicYears->firstWhere('id', $selectedYearId)?->name ?? 'Semua') 
            : 'Semua Tahun Ajaran';
        $sheet->setCellValue('A3', "Tahun Kelulusan: {$yearText} | Sub-Unit: {$selectedSubUnit} | Total Alumni: {$alumni->count()} Siswa | Unduh: " . date('d/m/Y H:i'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF475569');

        // Headers
        $headers = [
            'No',
            'NIS',
            'NISN',
            'NIK',
            'Nama Lengkap',
            'Nama Panggilan',
            'L/P',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Program Lulus',
            'Kelompok Terakhir',
            'Wali Kelas Terakhir',
            'Tahun Kelulusan',
            'Nama Ayah',
            'Nama Ibu',
            'No. WhatsApp Wali',
            'Alamat Domisili',
            'Sekolah Lanjutan / Catatan'
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
            ->getStartColor()->setARGB('FFD97706'); // Amber 600
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($startRow)->setRowHeight(26);

        // Populate Data
        $currentRow = $startRow + 1;
        foreach ($alumni as $index => $s) {
            $latestGradHistory = $s->classroomHistories->firstWhere('status', 'lulus') ?? $s->classroomHistories->first();
            
            $subUnitLabel = $latestGradHistory?->sub_unit ?? $s->sub_unit ?? 'TK';
            $programLabel = $subUnitLabel === 'PG' ? 'Alumni Playgroup (KB)' : 'Alumni TK (Taman Kanak-Kanak)';
            $lastClass = $latestGradHistory?->classroom_name ?? $s->classroom?->name ?? '-';
            $lastTeacher = $latestGradHistory?->homeroom_teacher_name ?? $s->classroom?->homeroomTeacher?->name ?? '-';
            $gradYear = $latestGradHistory?->academicYear?->name ?? $s->academicYear?->name ?? '-';
            $genderLabel = in_array(strtoupper($s->gender), ['L', 'LAKI-LAKI', 'MALE']) ? 'L' : 'P';
            $birthDateFormatted = $s->birth_date ? date('Y-m-d', strtotime($s->birth_date)) : '-';

            $sheet->setCellValue("A{$currentRow}", $index + 1);
            $sheet->setCellValueExplicit("B{$currentRow}", (string)$s->nis, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string)($s->nisn ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currentRow}", (string)($s->nik ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$currentRow}", $s->full_name);
            $sheet->setCellValue("F{$currentRow}", $s->nickname ?? '-');
            $sheet->setCellValue("G{$currentRow}", $genderLabel);
            $sheet->setCellValue("H{$currentRow}", $s->birth_place ?? '-');
            $sheet->setCellValue("I{$currentRow}", $birthDateFormatted);
            $sheet->setCellValue("J{$currentRow}", $programLabel);
            $sheet->setCellValue("K{$currentRow}", $lastClass);
            $sheet->setCellValue("L{$currentRow}", $lastTeacher);
            $sheet->setCellValue("M{$currentRow}", "T.A. {$gradYear}");
            $sheet->setCellValue("N{$currentRow}", $s->father_name ?? '-');
            $sheet->setCellValue("O{$currentRow}", $s->mother_name ?? '-');
            $sheet->setCellValueExplicit("P{$currentRow}", (string)($s->clean_parent_phone ?? $s->parent_phone ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("Q{$currentRow}", $s->address ?? '-');
            $sheet->setCellValue("R{$currentRow}", $s->notes ?? '-');

            // Alignment
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$currentRow}:J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Striping
            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFDF8F6');
            }

            $currentRow++;
        }

        $dataEndRow = max($currentRow - 1, $startRow);
        $sheet->getStyle("A{$startRow}:{$lastCol}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE2E8F0'));

        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Buku_Induk_Alumni_PAUD_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
