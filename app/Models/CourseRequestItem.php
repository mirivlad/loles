<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'course_id',
        'user_id',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CourseRequest::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
