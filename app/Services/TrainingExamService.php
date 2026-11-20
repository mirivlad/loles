<?php

namespace App\Services;

use App\Models\TrainingEnrollment;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingExamRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingExamService
{
    public function start(TrainingExamRule $exam, User $student): TrainingExamAttempt
    {
        return DB::transaction(function () use ($exam, $student) {
            $exam = TrainingExamRule::with(['intake', 'test.questions.answers'])
                ->lockForUpdate()->findOrFail($exam->id);
            $enrollment = TrainingEnrollment::where('training_intake_id', $exam->training_intake_id)
                ->where('user_id', $student->id)->lockForUpdate()->first();

            abort_unless($student->hasRole('Student') && $student->is_active
                && $enrollment && $enrollment->status === 'enrolled', 403);

            $attempts = $exam->attempts()->where('training_enrollment_id', $enrollment->id)
                ->orderByDesc('attempt_number')->get();
            $active = $attempts->first(fn ($item) => $item->status === 'in_progress' && !$item->isVoided());
            if ($active) {
                if ($active->expires_at->isFuture()) {
                    // Pausing the exam prevents new attempts, but never steals
                    // the remaining time from a student who already started.
                    return $active;
                }
                $this->expire($active);
            }

            if (!$exam->acceptsAttempts()) {
                throw ValidationException::withMessages(['exam' => 'Экзамен сейчас недоступен.']);
            }

            $used = $attempts->filter(fn ($item) => !$item->isVoided())->count();
            if ($used >= $exam->max_attempts) {
                throw ValidationException::withMessages(['exam' => 'Лимит попыток исчерпан.']);
            }

            $questions = $exam->test->questions->sortBy([['sort_order', 'asc'], ['id', 'asc']]);
            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['exam' => 'Тест пока не содержит вопросов.']);
            }
            foreach ($questions as $question) {
                $answers = $question->answers;
                if ($question->type !== 'multiple_choice' || $answers->count() < 2
                    || !$answers->contains('is_correct', true)
                    || !$answers->contains('is_correct', false)) {
                    throw ValidationException::withMessages([
                        'exam' => 'Экзамен содержит неподдерживаемые или неполные вопросы. '
                            . 'Пока поддерживается выбор одного или нескольких ответов.',
                    ]);
                }
            }

            $expiresAt = now()->addMinutes($exam->time_limit_minutes)
                ->min($exam->closes_on->copy()->endOfDay())
                ->min($exam->intake->ends_on->copy()->endOfDay());
            $attempt = $exam->attempts()->create([
                'training_enrollment_id' => $enrollment->id,
                'user_id' => $student->id,
                'attempt_number' => ($attempts->max('attempt_number') ?? 0) + 1,
                'status' => 'in_progress',
                'started_at' => now(),
                'expires_at' => $expiresAt,
                'rule_snapshot' => [
                    'kind' => $exam->kind,
                    'pass_mode' => $exam->pass_mode,
                    'allowed_errors' => $exam->allowed_errors,
                    'passing_percent' => $exam->passing_percent,
                    'time_limit_minutes' => $exam->time_limit_minutes,
                ],
            ]);
            foreach ($questions->values() as $position => $question) {
                $attempt->questions()->create([
                    'question_id' => $question->id,
                    'sort_order' => $position,
                    'question_text' => $question->question_text,
                    'options_snapshot' => $question->answers->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                        ->map(fn ($answer) => [
                            'id' => $answer->id,
                            'text' => $answer->answer_text,
                            'image' => $answer->image,
                            'correct' => $answer->is_correct,
                        ])->values()->all(),
                ]);
            }
            return $attempt;
        }, 3);
    }

    public function submit(TrainingExamAttempt $attempt, User $student, mixed $submitted): TrainingExamAttempt
    {
        return DB::transaction(function () use ($attempt, $student, $submitted) {
            $attempt = TrainingExamAttempt::with(['rule.intake', 'rule.test', 'questions'])
                ->lockForUpdate()->findOrFail($attempt->id);
            abort_unless($attempt->user_id === $student->id && $student->hasRole('Student')
                && $attempt->enrollment->status === 'enrolled', 403);

            if ($attempt->status !== 'in_progress' || $attempt->isVoided()) {
                throw ValidationException::withMessages(['exam' => 'Эта попытка уже закрыта.']);
            }

            if (now()->greaterThanOrEqualTo($attempt->expires_at)
                || $attempt->rule->intake->status !== 'training') {
                $this->expire($attempt);
                return $attempt->fresh();
            }

            if (!is_array($submitted)) {
                throw ValidationException::withMessages(['answers' => 'Некорректный формат ответов.']);
            }

            $questionIds = $attempt->questions->pluck('id')->map(fn ($id) => (string) $id)->all();
            foreach (array_keys($submitted) as $questionId) {
                if (!in_array((string) $questionId, $questionIds, true)) {
                    throw ValidationException::withMessages(['answers' => 'В запросе обнаружен посторонний вопрос.']);
                }
            }

            $correctCount = 0;
            foreach ($attempt->questions as $question) {
                $selected = $submitted[(string) $question->id] ?? [];
                if (!is_array($selected) || count($selected) > count($question->options_snapshot)) {
                    throw ValidationException::withMessages(['answers' => 'Недопустимый список ответов.']);
                }
                $selectedIds = array_map(function ($id) {
                    if (!ctype_digit((string) $id)) {
                        throw ValidationException::withMessages(['answers' => 'Недопустимый идентификатор ответа.']);
                    }
                    return (int) $id;
                }, $selected);
                if (count($selectedIds) !== count(array_unique($selectedIds))) {
                    throw ValidationException::withMessages(['answers' => 'Повторяющиеся ответы недопустимы.']);
                }

                $allowedIds = array_column($question->options_snapshot, 'id');
                if (array_diff($selectedIds, $allowedIds)) {
                    throw ValidationException::withMessages(['answers' => 'Ответ не относится к этому вопросу.']);
                }
                $correctIds = array_column(array_filter($question->options_snapshot,
                    fn ($option) => $option['correct']), 'id');
                sort($selectedIds);
                sort($correctIds);
                $isCorrect = $selectedIds === $correctIds;
                $correctCount += (int) $isCorrect;
                $question->update(['selected_ids' => $selectedIds, 'is_correct' => $isCorrect]);
            }

            $total = $attempt->questions->count();
            $errors = $total - $correctCount;
            $config = $attempt->rule_snapshot;
            $passed = $config['pass_mode'] === 'allowed_errors'
                ? $errors <= $config['allowed_errors']
                : $correctCount * 100 >= $total * $config['passing_percent'];

            $attempt->update([
                'status' => 'submitted', 'finished_at' => now(),
                'correct_count' => $correctCount, 'error_count' => $errors,
                'total_count' => $total, 'score_percent' => (int) round(100 * $correctCount / $total),
                'passed' => $passed,
            ]);
            return $attempt->fresh();
        }, 3);
    }

    public function expireIfNeeded(TrainingExamAttempt $attempt): TrainingExamAttempt
    {
        if ($attempt->status !== 'in_progress' || $attempt->expires_at->isFuture()) {
            return $attempt;
        }
        return DB::transaction(function () use ($attempt) {
            $locked = TrainingExamAttempt::lockForUpdate()->findOrFail($attempt->id);
            if ($locked->status === 'in_progress' && !$locked->expires_at->isFuture()) {
                $this->expire($locked);
            }
            return $locked->fresh();
        });
    }

    private function expire(TrainingExamAttempt $attempt): void
    {
        if ($attempt->status !== 'in_progress') {
            return;
        }
        $count = $attempt->questions()->count();
        $attempt->update([
            'status' => 'expired', 'finished_at' => now(),
            'total_count' => $count, 'correct_count' => 0,
            'error_count' => $count, 'score_percent' => 0, 'passed' => false,
        ]);
    }
}
