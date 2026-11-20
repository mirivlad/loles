<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'created_by',
        'title',
        'slug',
        'description',
        'objectives',
        'thumbnail',
        'type',
        'scorm_package_path',
        'h5p_package_path',
        'duration_minutes',
        'has_certificate',
        'passing_score',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'has_certificate' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(CoursePackage::class)->latest();
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function scormData(): HasMany
    {
        return $this->hasMany(ScormData::class);
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserCourseProgress::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(CourseRequestItem::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'course_assignments');
    }
}
