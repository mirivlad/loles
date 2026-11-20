<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;

class CourseAccess
{
    public function canStudy(Course $course, User $user): bool
    {
        if (!$course->is_active) {
            return false;
        }

        $groupIds = $user->groups()->pluck('groups.id');

        return CourseAssignment::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->where(function ($query) use ($user, $groupIds) {
                $query->where(fn ($q) => $q->where('type', 'individual')
                    ->where('user_id', $user->id));

                if ($groupIds->isNotEmpty()) {
                    $query->orWhere(fn ($q) => $q->where('type', 'group')
                        ->whereIn('group_id', $groupIds));
                }

                if ($user->organization_id) {
                    $query->orWhere(fn ($q) => $q->where('type', 'organization')
                        ->where('organization_id', $user->organization_id));
                }
            })->exists();
    }

    public function authorizeStudy(Course $course, User $user): void
    {
        abort_unless($course->is_active, 404);
        abort_unless($this->canStudy($course, $user), 403,
            'У вас нет доступа к этому курсу.');
    }

    public function canReadMaterial(Course $course, User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Curator'])
            || $this->canStudy($course, $user);
    }
}
