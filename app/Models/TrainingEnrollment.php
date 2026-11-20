<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    protected $fillable = ['training_intake_id', 'user_id', 'training_application_student_id',
        'organization_id', 'training_intake_group_id', 'status', 'enrolled_at', 'enrolled_by'];
    protected $casts = ['enrolled_at' => 'datetime'];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(TrainingIntake::class, 'training_intake_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TrainingIntakeGroup::class, 'training_intake_group_id');
    }

    public function applicationStudent(): BelongsTo
    {
        return $this->belongsTo(TrainingApplicationStudent::class, 'training_application_student_id');
    }
}
