<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingExamAttemptQuestion extends Model
{
    protected $fillable = [
        'training_exam_attempt_id', 'question_id', 'sort_order', 'question_text',
        'options_snapshot', 'selected_ids', 'is_correct',
    ];

    protected $casts = [
        'options_snapshot' => 'array', 'selected_ids' => 'array', 'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TrainingExamAttempt::class, 'training_exam_attempt_id');
    }
}
