<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingIntakeCourse extends Model
{
    public $timestamps = false;
    protected $fillable = ['training_intake_id', 'course_id', 'sort_order'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
