<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDdtkRecord extends Model
{
    use HasFactory;

    protected $table = 'student_ddtk_records';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'academic_year_id',
        'period',
        'date',
        // DDTK (Pertumbuhan)
        'height',
        'weight',
        'head_circumference',
        'arm_circumference',
        // SDIDTK (Skrining Perkembangan & Medis)
        'visus_right',
        'visus_left',
        'kpsp_result',
        'dental_health',
        'blood_sugar',
        'has_leprosy',
        'has_frambesia',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
        'height' => 'float',
        'weight' => 'float',
        'head_circumference' => 'float',
        'arm_circumference' => 'float',
        'blood_sugar' => 'float',
        'has_leprosy' => 'boolean',
        'has_frambesia' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Compute Body Mass Index (BMI / IMT).
     */
    public function getBmiAttribute(): ?float
    {
        if (!$this->height || !$this->weight || $this->height <= 0) {
            return null;
        }
        $hMeter = $this->height / 100;
        return round($this->weight / ($hMeter * $hMeter), 1);
    }

    /**
     * Formatted Period Label (e.g. '2026-10' -> 'Oktober 2026').
     */
    public function getFormattedPeriodAttribute(): string
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $this->period, $m)) {
            $months = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            ];
            return ($months[$m[2]] ?? $m[2]) . ' ' . $m[1];
        }
        return $this->period;
    }
}
