<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeroomAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'jenjang_id',
        'class_level_id',
        'classroom_id',
        'employee_id',
        'role',
        'subject_name',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'wali_kelas' => 'Wali Kelas',
            'guru_kelas' => 'Guru Kelas',
            'gpk' => 'GPK (Inklusi)',
            'gpq' => 'GPQ (Al-Qur\'an)',
            'guru_mapel' => 'Guru Sentra / Mapel',
            'koordinator_tingkat' => 'Koordinator Tingkat',
            'pendamping_tingkat' => 'Pendamping Tingkat',
            default => 'Wali Kelas',
        };
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    protected static function booted()
    {
        static::saved(function ($assignment) {
            if ($assignment->employee_id) {
                \Illuminate\Support\Facades\Cache::forget('user_is_active_homeroom_' . $assignment->employee_id);
            }
        });

        static::deleted(function ($assignment) {
            if ($assignment->employee_id) {
                \Illuminate\Support\Facades\Cache::forget('user_is_active_homeroom_' . $assignment->employee_id);
            }
        });
    }
}
