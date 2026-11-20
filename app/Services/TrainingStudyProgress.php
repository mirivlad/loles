<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\TrainingEnrollment;
use App\Models\TrainingStudiedMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TrainingStudyProgress
{
    /**
     * Only a deliberate learner action records a studied item.
     * GET/preview never changes progress.
     */
    public function mark(TrainingEnrollment $enrollment, Course $course,
        CourseModule $module, User $student): void
    {
        abort_unless($student->hasRole('Student') && $student->is_active
            && $enrollment->user_id === $student->id && $enrollment->status === 'enrolled', 403);

        $intake = $enrollment->intake;
        abort_unless($intake->status === 'training'
            && $intake->starts_on->lte(today()) && $intake->ends_on->gte(today())
            && $intake->courses()->where('course_id', $course->id)->exists()
            && $course->is_active, 403);

        $visible = $this->visibleStudyModuleIds($course);
        abort_unless($module->course_id === $course->id
            && $visible->contains($module->id), 404);

        DB::table('training_studied_materials')->insertOrIgnore([
            'training_enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'course_module_id' => $module->id,
            'material_title' => $module->title,
            'studied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function visibleStudyModuleIds(Course $course): Collection
    {
        return app(CourseOutline::class)->steps(app(CourseOutline::class)->entries($course))
            ->filter(fn ($entry) => $entry['item']->type !== 'test')
            ->pluck('item.id');
    }

    /**
     * Current active-course content. Deleted/hidden materials remain in the
     * audit table but are not counted as active learning progress.
     *
     * @param Collection<int, CourseModule> $modules
     * @return Collection<int, int>
     */
    public function visibleFromModules(Collection $modules): Collection
    {
        $byId = $modules->keyBy('id');
        return $modules->filter(function ($module) use ($byId) {
            if (!$module->is_active || in_array($module->type, ['section', 'test'], true)) {
                return false;
            }
            if (!$module->parent_id) {
                return true;
            }
            $parent = $byId->get($module->parent_id);
            return $parent && $parent->is_active && $parent->type === 'section';
        })->pluck('id')->values();
    }
}
