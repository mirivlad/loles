<?php

namespace App\Services;

use App\Models\CourseModule;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingIntake;
use App\Models\TrainingStudiedMaterial;
use Illuminate\Support\Collection;

class TrainingOutcomeService
{
    /**
     * Calculate independent results for each enrollment. Never infer a
     * student's intake membership from their current employer or legacy assignments.
     *
     * @param Collection $enrollments
     * @return Collection keyed by enrollment ID
     */
    public function summarize(TrainingIntake $intake, Collection $enrollments): Collection
    {
        $intake->loadMissing(['courses.course', 'exams.test']);
        $courses = $intake->courses;
        $requiredExams = $intake->exams->filter(
            fn ($exam) => $exam->kind === 'official' && $exam->is_required
        );
        $courseIds = $courses->pluck('course_id')->all();
        $enrollmentIds = $enrollments->pluck('id')->all();
        if ($enrollmentIds === []) {
            return collect();
        }

        $moduleRows = CourseModule::whereIn('course_id', $courseIds)
            ->get()->groupBy('course_id');
        $progress = app(TrainingStudyProgress::class);
        $visibleByCourse = [];
        foreach ($courseIds as $courseId) {
            $visibleByCourse[$courseId] = $progress->visibleFromModules(
                $moduleRows->get($courseId, collect())
            )->all();
        }

        $studied = TrainingStudiedMaterial::whereIn('training_enrollment_id', $enrollmentIds)
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('course_module_id')
            ->get(['training_enrollment_id', 'course_id', 'course_module_id'])
            ->groupBy('training_enrollment_id');

        $requiredIds = $requiredExams->pluck('id')->all();
        $passed = collect();
        if ($requiredIds) {
            $passed = TrainingExamAttempt::whereIn('training_enrollment_id', $enrollmentIds)
                ->whereIn('training_exam_rule_id', $requiredIds)
                ->where('status', 'submitted')->where('passed', true)->whereNull('voided_at')
                ->get(['training_enrollment_id', 'training_exam_rule_id'])
                ->groupBy('training_enrollment_id');
        }

        $outcomes = collect();
        foreach ($enrollments as $enrollment) {
            $records = $studied->get($enrollment->id, collect());
            $percentages = [];
            foreach ($courses as $item) {
                $moduleIds = $visibleByCourse[$item->course_id];
                $total = count($moduleIds);
                $completed = $records->where('course_id', $item->course_id)
                    ->pluck('course_module_id')->unique()->intersect($moduleIds)->count();
                $percentages[$item->course_id] = $total
                    ? (int) round(100 * $completed / $total)
                    : 0;
            }
            $allStudied = $courses->isNotEmpty()
                && collect($percentages)->every(
                    fn ($percentage) => $percentage >= $intake->required_progress_percent
                );
            $passedIds = $passed->get($enrollment->id, collect())
                ->pluck('training_exam_rule_id')->unique()->all();
            $examResults = [];
            foreach ($requiredExams as $exam) {
                $examResults[$exam->id] = in_array($exam->id, $passedIds);
            }
            $hasBasis = $requiredExams->isNotEmpty();
            $allExamsPassed = $hasBasis && !in_array(false, $examResults, true);
            $meetsRequirements = $enrollment->status === 'enrolled'
                && $allStudied && $allExamsPassed;
            $status = !$hasBasis ? 'not_configured'
                : ($meetsRequirements ? 'passed'
                : (in_array($intake->status, ['completed', 'archived'], true)
                    ? 'not_passed' : 'in_progress'));

            $reasons = [];
            if (!$hasBasis) {
                $reasons[] = 'Не настроены обязательные официальные экзамены';
            } else {
                if (!$allExamsPassed) {
                    $reasons[] = 'Не сданы все обязательные официальные экзамены';
                }
                if (!$allStudied) {
                    $reasons[] = 'Освоение одного или нескольких курсов ниже порога';
                }
            }
            if ($enrollment->status !== 'enrolled') {
                $reasons[] = 'Зачисление не активно';
            }

            $outcomes->put($enrollment->id, [
                'status' => $status,
                'label' => match ($status) {
                    'passed' => 'Условия обучения выполнены',
                    'not_passed' => 'Не завершил обучение',
                    'in_progress' => 'В процессе',
                    default => 'Недостаточно условий аттестации',
                },
                'course_progress' => $percentages,
                'exam_results' => $examResults,
                'required_exam_count' => count($requiredIds),
                'passed_exam_count' => count(array_filter($examResults)),
                'reasons' => implode('; ', $reasons),
            ]);
        }
        return $outcomes;
    }
}
