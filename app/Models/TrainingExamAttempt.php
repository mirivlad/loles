<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingExamAttempt extends Model
{
    protected $fillable = [
        'training_exam_rule_id', 'training_enrollment_id', 'user_id',
        'attempt_number', 'status', 'started_at', 'expires_at', 'finished_at',
        'rule_snapshot', 'correct_count', 'error_count', 'total_count',
        'score_percent', 'passed', 'voided_at', 'voided_by', 'void_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime', 'expires_at' => 'datetime', 'finished_at' => 'datetime',
        'voided_at' => 'datetime', 'rule_snapshot' => 'array', 'passed' => 'boolean',
        'correct_count' => 'integer', 'error_count' => 'integer',
        'total_count' => 'integer', 'score_percent' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TrainingExamRule::class, 'training_exam_rule_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class, 'training_enrollment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TrainingExamAttemptQuestion::class)->orderBy('sort_order');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
