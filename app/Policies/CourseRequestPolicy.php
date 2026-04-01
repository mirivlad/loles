<?php

namespace App\Policies;

use App\Models\CourseRequest;
use App\Models\User;

class CourseRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function view(User $user, CourseRequest $courseRequest): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function update(User $user, CourseRequest $courseRequest): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function delete(User $user, CourseRequest $courseRequest): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }

    public function approve(User $user, CourseRequest $courseRequest): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function reject(User $user, CourseRequest $courseRequest): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }
}
