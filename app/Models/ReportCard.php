<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCard extends Model
{
    use HasFactory;

    protected $table = 'report_cards';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'classroom_id',
        'semester',
        'entry_mode',
        'report_date',
        'place',
        'homeroom_teacher_name',
        'principal_name',
        'height',
        'weight',
        'head_circumference',
        'attendance_sick',
        'attendance_permission',
        'attendance_unexcused',
        'nabp_narrative',
        'jati_diri_narrative',
        'steam_narrative',
        'p5_project_name',
        'p5_narrative',
        'daycare_narrative',
        'tpq_jilid',
        'tpq_surah',
        'tpq_hadith_doa',
        'tpq_narrative',
        'teacher_notes',
        'parent_feedback',
        'photos',
        'pdf_file',
        'status',
        'revision_notes',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'photos' => 'array',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'head_circumference' => 'decimal:2',
        'attendance_sick' => 'integer',
        'attendance_permission' => 'integer',
        'attendance_unexcused' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Semester Label
     */
    public function getSemesterLabelAttribute(): string
    {
        $sem = (string)$this->semester;
        if ($sem === 'mid_ganjil' || strtolower($sem) === 'mid semester ganjil' || strtolower($sem) === 'mid ganjil') return 'Mid Semester Ganjil';
        if ($sem === '1' || strtolower($sem) === 'ganjil' || strtolower($sem) === 'semester ganjil') return 'Semester Ganjil';
        if ($sem === 'mid_genap' || strtolower($sem) === 'mid semester genap' || strtolower($sem) === 'mid genap') return 'Mid Semester Genap';
        if ($sem === '2' || strtolower($sem) === 'genap' || strtolower($sem) === 'semester genap') return 'Semester Genap';
        return "Semester {$sem}";
    }

    /**
     * Estimated Nutritional Status (Status Gizi TB/BB)
     */
    public function getNutritionalStatusAttribute(): ?string
    {
        if (!$this->height || !$this->weight || $this->height <= 0) return null;
        $heightM = $this->height / 100;
        $bmi = $this->weight / ($heightM * $heightM);

        if ($bmi < 14) return 'Gizi Kurang';
        if ($bmi <= 18) return 'Gizi Baik (Normal)';
        if ($bmi <= 20) return 'Berisiko Gizi Lebih';
        return 'Gizi Lebih (Overweight)';
    }

    /**
     * PDF Document URL
     */
    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_file ? asset('storage/' . $this->pdf_file) : null;
    }
}
