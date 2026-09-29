<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningObjective extends Model
{
    use HasFactory;

    protected $table = 'learning_objectives';

    protected $fillable = [
        'category',
        'sub_unit',
        'grade_level',
        'title',
        'sample_narrative',
        'order',
    ];
}
