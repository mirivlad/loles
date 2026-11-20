<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\TrainingEnrollment;
use App\Models\TrainingIntakeCourse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    /**
     * Intake membership is independent of enterprise membership. The status,
     * dates and the enrollment itself are all checked server-side.
     */
    public function eligibleIntakeCourses(User $user): Builder
    {
        $today = today()->toDateString();
        $enrolledIntakeIds = TrainingEnrollment::query()
            ->select('training_intake_id')
            ->where('user_id', $user->id)
            ->where('status', 'enrolled')
            ->whereHas('intake', fn (Builder $query) => $query
                ->where('status', 'training')
                ->where('starts_on', '<=', $today)
                ->where('ends_on', '>=', $today));

        return TrainingIntakeCourse::query()
            ->whereIn('training_intake_id', $enrolledIntakeIds)
            ->whereHas('course', fn (Builder $query) => $query->where('is_active', true));
    }

    public function accessibleCourseIds(User $user): Collection
    {
        // Once a course participates in the new intake workflow, legacy
        // group/enterprise assignments can no longer bypass enrollment.
        $intakeCourseIds = TrainingIntakeCourse::query()->select('course_id');
        return $this->eligibleAssignments($user)
            ->whereNotIn('course_id', $intakeCourseIds)
            ->distinct()->pluck('course_id')
            ->merge($this->eligibleIntakeCourses($user)->distinct()->pluck('course_id'))
            ->unique()->values();
    }

    public function canStudy(Course $course, User $user): bool
    {
        if (TrainingIntakeCourse::where('course_id', $course->id)->exists()) {
            return $this->eligibleIntakeCourses($user)
                ->where('course_id', $course->id)->exists();
        }
        return $this->eligibleAssignments($user)
            ->where('course_id', $course->id)->exists();
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
