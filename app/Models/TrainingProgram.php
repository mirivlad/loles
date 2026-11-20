<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'created_by'];
    protected $casts = ['is_active' => 'boolean'];

    public function courses(): HasMany
    {
        return $this->hasMany(TrainingProgramCourse::class)->orderBy('sort_order')->orderBy('id');
    }

    public function intakes(): HasMany
    {
        return $this->hasMany(TrainingIntake::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
