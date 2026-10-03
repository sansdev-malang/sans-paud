<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jenjang extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function classLevels(): HasMany
    {
        return $this->hasMany(ClassLevel::class)->orderBy('order', 'asc');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class);
    }

    /**
     * Scope for active jenjang.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
