<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingApplication extends Model
{
    protected $fillable = ['training_intake_id', 'applicant_type', 'organization_id', 'contact_name',
        'contact_email', 'contact_phone', 'notes', 'status', 'payment_status', 'approved_by',
        'approved_at', 'created_by'];
    protected $casts = ['approved_at' => 'datetime'];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(TrainingIntake::class, 'training_intake_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(TrainingApplicationStudent::class);
    }
}
