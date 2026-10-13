<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomJournal extends Model
{
    use HasFactory;

    protected $table = 'classroom_journals';

    protected $fillable = [
        'classroom_id',
        'academic_year_id',
        'teacher_id',
        'date',
        'start_time',
        'end_time',
        'topic',
        'class_condition',
        'attachment_photo',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Formatted Time Range (e.g. 07:30 - 10:30 WIB).
     */
    public function getFormattedTimeAttribute(): string
    {
        if ($this->start_time && $this->end_time) {
            $st = substr((string)$this->start_time, 0, 5);
            $et = substr((string)$this->end_time, 0, 5);
            return "{$st} - {$et} WIB";
        } elseif ($this->start_time) {
            $st = substr((string)$this->start_time, 0, 5);
            return "Mulai {$st} WIB";
        }
        return '-';
    }

    /**
     * Summary of student attendance on this date for this classroom.
     */
    public function getAttendanceSummaryAttribute(): ?array
    {
        $records = StudentAttendance::where('classroom_id', $this->classroom_id)
            ->whereDate('date', $this->date)
            ->get();

        if ($records->isEmpty()) {
            return null;
        }

        return [
            'h' => $records->where('status', 'H')->count(),
            's' => $records->where('status', 'S')->count(),
            'i' => $records->where('status', 'I')->count(),
            'a' => $records->where('status', 'A')->count(),
            'total' => $records->count(),
        ];
    }
}
