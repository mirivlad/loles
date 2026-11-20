<?php

namespace App\Policies;

use App\Models\User;

class TrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }

    public function view(User $user, object $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, object $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, object $model): bool
    {
        return false; // Audit history is preserved; archive instead.
    }
}
