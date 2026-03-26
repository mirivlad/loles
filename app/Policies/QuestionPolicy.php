<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function view(User $user, Question $question): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function update(User $user, Question $question): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator']);
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->hasRole(['Administrator', 'Manager']);
    }
}
