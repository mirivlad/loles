<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'organization_id',
        'group_id',
        'user_id',
        'type',
        'start_date',
        'end_date',
        'note',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function typeLabel(): string
    {
        return match ($this->type) {
            'individual' => 'Учащийся',
            'group' => 'Учебная группа',
            'organization' => 'Предприятие',
            default => 'Неизвестный тип',
        };
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'Отключено';
        }
        if ($this->start_date && $this->start_date->startOfDay()->greaterThan(today())) {
            return 'Начнётся позднее';
        }
        if ($this->end_date && $this->end_date->startOfDay()->lessThan(today())) {
            return 'Срок истёк';
        }
        return 'Доступно сейчас';
    }

    public function statusColorClass(): string
    {
        return match ($this->statusLabel()) {
            'Доступно сейчас' => 'text-bg-success',
            'Начнётся позднее' => 'text-bg-info',
            'Срок истёк' => 'text-bg-secondary',
            default => 'text-bg-light border',
        };
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
