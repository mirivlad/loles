<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_request_id',
        'course_id',
        'user_id',
        'group_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function courseRequest(): BelongsTo
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getTypeAttribute(): string
    {
        if ($this->user_id) {
            return 'individual';
        } elseif ($this->group_id) {
            return 'group';
        }
        return 'organization';
    }
}
