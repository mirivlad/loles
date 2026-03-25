<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScormData extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'package_id',
        'sco_id',
        'data',
        'last_access',
    ];

    protected $casts = [
        'data' => 'array',
        'last_access' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
