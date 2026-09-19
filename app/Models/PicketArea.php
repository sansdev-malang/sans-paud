<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PicketArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'jobs',
        'start_time',
        'end_time',
        'duty_hours',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the duty_hours formatted automatically from start_time and end_time if duty_hours is null.
     */
    public function getDutyHoursAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }
        if ($this->start_time && $this->end_time) {
            return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);
        }
        return '06:30 - 07:00';
    }

    /**
     * Picket area has many schedules.
     */
    public function schedules()
    {
        return $this->hasMany(PicketSchedule::class);
    }
}
