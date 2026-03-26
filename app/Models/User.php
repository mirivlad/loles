<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'user_groups');
    }

    public function createdCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    public function courseRequests(): HasMany
    {
        return $this->hasMany(CourseRequest::class, 'requested_by_user_id');
    }

    public function approvedCourseRequests(): HasMany
    {
        return $this->hasMany(CourseRequest::class, 'approved_by_user_id');
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function scormData(): HasMany
    {
        return $this->hasMany(ScormData::class);
    }

    public function courseProgress(): HasMany
    {
        return $this->hasMany(UserCourseProgress::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('Administrator');
    }

    public function isManager(): bool
    {
        return $this->hasRole('Manager');
    }

    public function isCurator(): bool
    {
        return $this->hasRole('Curator');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('Student');
    }
}
