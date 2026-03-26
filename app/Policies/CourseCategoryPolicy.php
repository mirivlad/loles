<?php

namespace App\Policies;

use App\Models\CourseCategory;
use App\Models\User;

class CourseCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function view(User $user, CourseCategory $category): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }

    public function update(User $user, CourseCategory $category): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }

    public function delete(User $user, CourseCategory $category): bool
    {
        return $user->hasRole(['Administrator']);
    }
}
