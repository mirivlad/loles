<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingApplicationStudent extends Model
{
    protected $fillable = ['training_application_id', 'full_name', 'user_id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class, 'training_application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(TrainingEnrollment::class);
    }
}
