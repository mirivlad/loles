<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingIntake extends Model
{
    protected $fillable = ['training_program_id', 'name', 'application_deadline', 'starts_on', 'ends_on',
        'capacity', 'required_progress_percent', 'status', 'notes', 'created_by'];
    protected $casts = ['application_deadline' => 'date', 'starts_on' => 'date', 'ends_on' => 'date',
        'capacity' => 'integer', 'required_progress_percent' => 'integer'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(TrainingIntakeCourse::class)->orderBy('sort_order')->orderBy('id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TrainingIntakeGroup::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(TrainingExamRule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function acceptsApplications(): bool
    {
        return $this->status === 'open' && today()->lte($this->application_deadline);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Черновик', 'open' => 'Приём заявок', 'closed' => 'Приём завершён',
            'training' => 'Обучение', 'completed' => 'Завершён', 'archived' => 'Архив',
            default => 'Неизвестно',
        };
    }
}
