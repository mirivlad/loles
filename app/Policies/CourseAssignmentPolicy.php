<?php

namespace App\Policies;

use App\Models\CourseAssignment;
use App\Models\User;

class CourseAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function view(User $user, CourseAssignment $assignment): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }

    public function update(User $user, CourseAssignment $assignment): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }

    public function delete(User $user, CourseAssignment $assignment): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }
}
