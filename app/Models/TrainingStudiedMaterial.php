<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingStudiedMaterial extends Model
{
    protected $fillable = [
        'training_enrollment_id', 'course_id', 'course_module_id', 'material_title', 'studied_at',
    ];
    protected $casts = ['studied_at' => 'datetime'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class, 'training_enrollment_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }
}
