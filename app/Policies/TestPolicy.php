<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function view(User $user, Test $test): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function update(User $user, Test $test): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function delete(User $user, Test $test): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }
}
