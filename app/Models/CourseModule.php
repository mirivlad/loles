<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'parent_id',
        'title',
        'content',
        'type',  // section, lesson, video, file, link, test
        'sort_order',
        'duration_minutes',
        'is_required',
        'is_active',
        'video_url',
        'file_path',
        'external_url',
        'test_id',  // Связь с тестом
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CourseModule::class, 'parent_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserCourseProgress::class);
    }
}
