<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CourseAccess
{
    /**
     * One canonical access query for courses, tests, reader and attachments.
     * Dates are inclusive and interpreted in the application's local day.
     */
    public function eligibleAssignments(User $user): Builder
    {
        $groupIds = $user->groups()->pluck('groups.id');
        $today = today()->toDateString();

        return CourseAssignment::query()
            ->where('is_active', true)
            ->whereHas('course', fn (Builder $query) => $query->where('is_active', true))
            ->where('start_date', '<=', $today)
            ->where(fn (Builder $query) => $query
                ->whereNull('end_date')
                ->orWhere('end_date', '>=', $today))
            ->where(function (Builder $query) use ($user, $groupIds) {
                $query->where(fn (Builder $sub) => $sub->where('type', 'individual')
                    ->where('user_id', $user->id));

                if ($groupIds->isNotEmpty()) {
                    $query->orWhere(fn (Builder $sub) => $sub->where('type', 'group')
                        ->whereIn('group_id', $groupIds));
                }

                if ($user->organization_id) {
                    $query->orWhere(fn (Builder $sub) => $sub->where('type', 'organization')
                        ->where('organization_id', $user->organization_id));
                }
            });
    }

    public function canStudy(Course $course, User $user): bool
    {
        return $this->eligibleAssignments($user)
            ->where('course_id', $course->id)
            ->exists();
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
