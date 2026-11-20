<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingIntakeGroup extends Model
{
    protected $fillable = ['training_intake_id', 'name', 'notes'];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(TrainingIntake::class, 'training_intake_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }
}
