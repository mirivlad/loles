<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_active',
        'status',
        'started_on',
        'finished_on',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'started_on' => 'date',
        'finished_on' => 'date',
    ];

    public function statusLabel(): string
    {
        return match ($this->status) {
            'planned' => 'Запланирована',
            'active' => 'Обучается',
            'completed' => 'Завершена',
            'archived' => 'В архиве',
            default => 'Неизвестное состояние',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['completed', 'archived'], true);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_groups');
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }
}
