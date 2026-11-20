<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingExamRule extends Model
{
    protected $fillable = [
        'training_intake_id', 'test_id', 'kind', 'pass_mode', 'allowed_errors', 'passing_percent',
        'opens_on', 'closes_on', 'time_limit_minutes', 'max_attempts', 'is_required',
        'is_enabled', 'created_by',
    ];

    protected $casts = [
        'opens_on' => 'date', 'closes_on' => 'date', 'is_required' => 'boolean',
        'is_enabled' => 'boolean', 'time_limit_minutes' => 'integer',
        'max_attempts' => 'integer', 'allowed_errors' => 'integer', 'passing_percent' => 'integer',
    ];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(TrainingIntake::class, 'training_intake_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TrainingExamAttempt::class);
    }

    public function kindLabel(): string
    {
        return $this->kind === 'official' ? 'Официальный' : 'Пробный';
    }

    public function acceptsAttempts(): bool
    {
        $intake = $this->intake;
        return $this->is_enabled && $intake->status === 'training'
            && $this->opens_on->lte(today()) && $this->closes_on->gte(today())
            && $intake->starts_on->lte(today()) && $intake->ends_on->gte(today())
            && $this->test?->is_active && $this->test?->course?->is_active;
    }
}
