<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenjang_id',
        'name',
        'code',
        'sub_unit',
        'class_level_id',
        'academic_year_id',
        'homeroom_teacher_id',
        'capacity',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'homeroom_teacher_id');
    }

    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function ddtkRecords(): HasMany
    {
        return $this->hasMany(StudentDdtkRecord::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(ClassroomJournal::class);
    }

    /**
     * Get query for all active students enrolled in this classroom (supports primary, daycare, tpq).
     */
    public function enrolledStudentsQuery($academicYearId = null)
    {
        $sub = strtoupper((string) ($this->sub_unit ?: ($this->jenjang?->code ?? '')));
        $query = Student::where('status', 'aktif');

        $yearId = $academicYearId ?: $this->academic_year_id;
        if ($yearId && $yearId !== 'all') {
            $query->where('academic_year_id', $yearId);
        }

        if ($sub === 'DAYCARE' || $sub === 'TPA' || str_contains($sub, 'DAYCARE') || str_contains($sub, 'TPA')) {
            return $query->where(function ($q) {
                $q->where('classroom_id', $this->id)
                  ->orWhere('daycare_classroom_id', $this->id);
            });
        } elseif ($sub === 'TPQ' || str_contains($sub, 'TPQ')) {
            return $query->where(function ($q) {
                $q->where('classroom_id', $this->id)
                  ->orWhere('tpq_classroom_id', $this->id);
            });
        }

        return $query->where('classroom_id', $this->id);
    }

    /**
     * Get the active students count (supports Daycare & TPQ multi-classroom enrollment).
     */
    public function getActiveStudentsCountAttribute(): int
    {
        return $this->enrolledStudentsQuery()->count();
    }
}
