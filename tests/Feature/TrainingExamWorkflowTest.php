<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Test;
use App\Models\TrainingEnrollment;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingExamRule;
use App\Models\TrainingIntake;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\TrainingExamService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrainingExamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-11-21 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actor(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function training(): array
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $course = Course::create([
            'title' => 'Безопасность сварки', 'slug' => 'welding-' . uniqid(), 'is_active' => true,
        ]);
        $program = TrainingProgram::create(['name' => 'Подготовка сварщиков', 'is_active' => true]);
        $program->courses()->create(['course_id' => $course->id]);
        $intake = TrainingIntake::create([
            'training_program_id' => $program->id, 'name' => 'Ноябрьская группа',
            'application_deadline' => today()->subDays(5)->toDateString(),
            'starts_on' => today()->subDay()->toDateString(),
            'ends_on' => today()->addDays(3)->toDateString(),
            'capacity' => 20, 'required_progress_percent' => 100, 'status' => 'training',
        ]);
        $intake->courses()->create(['course_id' => $course->id]);
        $enrollment = TrainingEnrollment::create([
            'training_intake_id' => $intake->id, 'user_id' => $student->id,
            'enrolled_by' => $manager->id, 'enrolled_at' => now(), 'status' => 'enrolled',
        ]);
        $test = Test::create(['course_id' => $course->id, 'title' => 'Экзамен по сварке', 'is_active' => true]);
        $q1 = $test->questions()->create([
            'type' => 'multiple_choice', 'question_text' => 'Что нужно перед началом работы?',
            'sort_order' => 1,
        ]);
        $a1 = $q1->answers()->create(['answer_text' => 'Надеть защиту', 'is_correct' => true]);
        $q1->answers()->create(['answer_text' => 'Снять защиту', 'is_correct' => false]);

        $q2 = $test->questions()->create([
            'type' => 'multiple_choice', 'question_text' => 'Какие предметы обязательны?',
            'sort_order' => 2,
        ]);
        $b1 = $q2->answers()->create(['answer_text' => 'Маска', 'is_correct' => true]);
        $b2 = $q2->answers()->create(['answer_text' => 'Перчатки', 'is_correct' => true]);
        $q2->answers()->create(['answer_text' => 'Сандалии', 'is_correct' => false]);
        return compact('manager', 'student', 'course', 'program', 'intake',
            'enrollment', 'test', 'q1', 'q2', 'a1', 'b1', 'b2');
    }

    private function rule(array $setup, array $overrides = []): TrainingExamRule
    {
        $intake = $setup['intake'];
        $data = array_merge([
            'test_id' => $setup['test']->id, 'kind' => 'official',
            'pass_mode' => 'allowed_errors', 'allowed_errors' => 0,
            'opens_on' => today()->subDay()->toDateString(),
            'closes_on' => today()->addDays(2)->toDateString(),
            'time_limit_minutes' => 20, 'max_attempts' => 2, 'is_required' => 1,
        ], $overrides);
        $this->actingAs($setup['manager'])
            ->post(route('admin.training.exams.store', $intake), $data)->assertRedirect();
        $rule = TrainingExamRule::latest('id')->firstOrFail();
        $this->post(route('admin.training.exams.toggle', [$intake, $rule]), [
            'is_enabled' => 1,
        ])->assertRedirect();
        return $rule->fresh();
    }

    private function start(TrainingExamRule $rule, User $student): TrainingExamAttempt
    {
        $this->actingAs($student)->post(route('student.exams.start', $rule))->assertRedirect();
        return TrainingExamAttempt::latest('id')->firstOrFail();
    }

    private function answers(array $setup, TrainingExamAttempt $attempt, bool $correct = true): array
    {
        $items = $attempt->questions()->orderBy('sort_order')->get();
        return [
            $items[0]->id => [$setup['a1']->id],
            $items[1]->id => $correct ? [$setup['b1']->id, $setup['b2']->id] : [$setup['b1']->id],
        ];
    }

    public function test_manager_sets_rules_and_student_cannot_change_them(): void
    {
        $s = $this->training();
        $intake = $s['intake'];
        $this->actingAs($s['manager'])->get(route('admin.training.exams.create', $intake))
            ->assertOk()->assertSee('Добавить экзамен');
        $rule = $this->rule($s);

        $this->get(route('admin.training.exams.show', [$intake, $rule]))
            ->assertOk()->assertSee('Экзамен по сварке');
        $this->post(route('admin.training.exams.store', $intake), [
            'test_id' => $s['test']->id, 'kind' => 'official', 'pass_mode' => 'allowed_errors',
            'allowed_errors' => 0, 'opens_on' => today()->subDay()->toDateString(),
            'closes_on' => today()->addDays(2)->toDateString(),
            'time_limit_minutes' => 20, 'max_attempts' => 1, 'is_required' => 1,
        ])->assertSessionHasErrors('test_id');
        $this->assertSame(1, $intake->exams()->count());

        $this->actingAs($s['student'])->get(route('admin.training.exams.show', [$intake, $rule]))
            ->assertForbidden();
        $this->post(route('admin.training.exams.toggle', [$intake, $rule]), [
            'is_enabled' => 0,
        ])->assertForbidden();
        $this->assertTrue($rule->fresh()->is_enabled);
    }

    public function test_exact_match_multiple_choice_grades_and_preserves_answer_snapshot(): void
    {
        $s = $this->training();
        $rule = $this->rule($s);
        $attempt = $this->start($rule, $s['student']);
        $this->assertSame('in_progress', $attempt->status);
        $this->assertSame(2, $attempt->questions()->count());
        $page = $this->get(route('student.exams.attempt.show', [$rule, $attempt]))->assertOk();
        $page->assertSee('Маска')->assertSee('Перчатки')
            ->assertDontSee('верный вариант');

        // Editing the source bank must not change an already-started attempt.
        $s['a1']->update(['is_correct' => false, 'answer_text' => 'Изменено после старта']);
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $this->answers($s, $attempt),
        ])->assertRedirect();
        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertTrue($attempt->passed);
        $this->assertSame(2, $attempt->correct_count);
        $this->assertSame(100, $attempt->score_percent);
        $this->assertSame(0, $attempt->error_count);
        $this->assertTrue($attempt->questions->every(fn ($q) => $q->is_correct));
        $this->get(route('student.exams.attempt.show', [$rule, $attempt]))
            ->assertOk()->assertSee('Результат: Сдан');
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => [],
        ])->assertSessionHasErrors('exam');
        $this->assertSame(2, $attempt->fresh()->correct_count);
    }

    public function test_zero_errors_fails_if_one_answer_missing_and_percent_uses_unrounded_ratio(): void
    {
        $s = $this->training();
        $rule = $this->rule($s, ['allowed_errors' => 0]);
        $attempt = $this->start($rule, $s['student']);
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $this->answers($s, $attempt, false),
        ])->assertRedirect();
        $this->assertFalse($attempt->fresh()->passed);
        $this->assertSame(1, $attempt->fresh()->error_count);

        $s2 = $this->training();
        $percent = $this->rule($s2, [
            'pass_mode' => 'percent', 'allowed_errors' => null, 'passing_percent' => 51,
        ]);
        $try = $this->start($percent, $s2['student']);
        $this->post(route('student.exams.attempt.submit', [$percent, $try]), [
            'answers' => $this->answers($s2, $try, false),
        ])->assertRedirect();
        $this->assertSame(50, $try->fresh()->score_percent);
        $this->assertFalse($try->fresh()->passed);
    }

    public function test_manual_opening_date_bounds_and_user_enrollment_are_enforced(): void
    {
        $s = $this->training();
        $rule = $this->rule($s);
        $this->actingAs($s['manager'])->post(route('admin.training.exams.toggle', [$s['intake'], $rule]), [
            'is_enabled' => 0,
        ])->assertRedirect();
        $this->actingAs($s['student'])->post(route('student.exams.start', $rule))
            ->assertSessionHasErrors('exam');
        $this->actingAs($s['manager'])->post(route('admin.training.exams.toggle', [$s['intake'], $rule]), [
            'is_enabled' => 1,
        ])->assertRedirect();

        Carbon::setTestNow($rule->opens_on->copy()->startOfDay());
        $this->actingAs($s['student'])->post(route('student.exams.start', $rule))
            ->assertRedirect();
        $this->assertSame(1, TrainingExamAttempt::count());
        Carbon::setTestNow($rule->closes_on->copy()->endOfDay());
        $this->get(route('student.exams.show', $rule))->assertOk();

        $stranger = $this->actor('Student');
        $this->actingAs($stranger)->get(route('student.exams.show', $rule))->assertForbidden();
        $this->post(route('student.exams.start', $rule))->assertForbidden();
        $this->get(route('student.exams.attempt.show', [$rule, TrainingExamAttempt::first()]))
            ->assertForbidden();
        $this->assertSame(1, TrainingExamAttempt::count());
    }

    public function test_one_active_attempt_is_reused_and_max_attempts_are_server_side(): void
    {
        $s = $this->training();
        $rule = $this->rule($s, ['max_attempts' => 1]);
        $one = $this->start($rule, $s['student']);
        $this->start($rule, $s['student']);
        $this->assertSame(1, TrainingExamAttempt::count());
        $this->post(route('student.exams.attempt.submit', [$rule, $one]), [
            'answers' => $this->answers($s, $one),
        ])->assertRedirect();
        $this->post(route('student.exams.start', $rule))->assertSessionHasErrors('exam');
        $this->assertSame(1, TrainingExamAttempt::count());
    }

    public function test_timeout_closes_attempt_and_rejects_late_answers(): void
    {
        $s = $this->training();
        $rule = $this->rule($s, ['time_limit_minutes' => 1]);
        $attempt = $this->start($rule, $s['student']);
        Carbon::setTestNow(now()->addSeconds(61));

        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $this->answers($s, $attempt),
        ])->assertRedirect();
        $this->assertSame('expired', $attempt->fresh()->status);
        $this->assertFalse($attempt->fresh()->passed);
        $this->assertNull($attempt->fresh()->questions()->first()->selected_ids);
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $this->answers($s, $attempt),
        ])->assertSessionHasErrors('exam');
    }

    public function test_tampered_question_or_answer_ids_are_rejected_without_finishing(): void
    {
        $s = $this->training();
        $rule = $this->rule($s);
        $attempt = $this->start($rule, $s['student']);
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => [999999 => [1]],
        ])->assertSessionHasErrors('answers');
        $answers = $this->answers($s, $attempt);
        $first = array_key_first($answers);
        $answers[$first] = [999999];
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $answers,
        ])->assertSessionHasErrors('answers');
        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->questions()->first()->selected_ids);
    }

    public function test_voided_attempt_retains_audit_but_no_longer_consumes_attempt_slot(): void
    {
        $s = $this->training();
        $rule = $this->rule($s, ['max_attempts' => 1]);
        $attempt = $this->start($rule, $s['student']);
        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $this->answers($s, $attempt),
        ])->assertRedirect();

        $this->actingAs($s['manager'])
            ->post(route('admin.training.exams.attempts.void', [$s['intake'], $rule, $attempt]), [
                'reason' => 'Ошибочная идентификация при экзамене',
            ])->assertRedirect();
        $attempt->refresh();
        $this->assertNotNull($attempt->voided_at);
        $this->assertSame($s['manager']->id, $attempt->voided_by);
        $this->assertSame(2, $attempt->questions()->count());
        $this->assertTrue($attempt->passed);

        $this->get(route('admin.training.exams.attempts.show', [$s['intake'], $rule, $attempt]))
            ->assertOk()->assertSee('Ошибочная идентификация');
        $this->post(route('admin.training.exams.attempts.void', [$s['intake'], $rule, $attempt]), [
            'reason' => 'Повторное аннулирование без основания',
        ])->assertSessionHasErrors('reason');

        $second = $this->start($rule, $s['student']);
        $this->assertSame(2, $second->attempt_number);
        $this->assertSame(2, TrainingExamAttempt::count());
    }

    public function test_trial_is_never_required_and_mismatched_test_is_rejected(): void
    {
        $s = $this->training();
        $trial = $this->rule($s, ['kind' => 'trial', 'is_required' => 1]);
        $this->assertSame('trial', $trial->kind);
        $this->assertFalse($trial->is_required);
        $this->assertSame(0, $s['intake']->exams()->where('kind', 'official')->count());

        $foreignCourse = Course::create([
            'title' => 'Чужой курс', 'slug' => 'foreign-' . uniqid(), 'is_active' => true,
        ]);
        $foreign = Test::create(['course_id' => $foreignCourse->id, 'title' => 'Чужой тест']);
        $this->actingAs($s['manager'])
            ->post(route('admin.training.exams.store', $s['intake']), [
                'test_id' => $foreign->id, 'kind' => 'official',
                'pass_mode' => 'allowed_errors', 'allowed_errors' => 0,
                'opens_on' => today()->subDay()->toDateString(),
                'closes_on' => today()->addDay()->toDateString(),
                'time_limit_minutes' => 30, 'max_attempts' => 1, 'is_required' => 1,
            ])->assertSessionHasErrors('test_id');
    }

    public function test_unsupported_questions_and_immutable_rules_after_start(): void
    {
        $s = $this->training();
        $s['test']->questions()->create([
            'type' => 'matching', 'question_text' => 'Сопоставьте факты',
        ]);
        $rule = $this->rule($s);
        $this->actingAs($s['student'])->post(route('student.exams.start', $rule))
            ->assertSessionHasErrors('exam');
        $this->assertSame(0, TrainingExamAttempt::count());
        $s['test']->questions()->where('type', 'matching')->delete();
        $this->start($rule, $s['student']);

        $this->actingAs($s['manager'])
            ->get(route('admin.training.exams.edit', [$s['intake'], $rule]))->assertStatus(409);
        $this->put(route('admin.training.exams.update', [$s['intake'], $rule]), [
            'test_id' => $s['test']->id, 'kind' => 'official', 'pass_mode' => 'percent',
            'passing_percent' => 100, 'opens_on' => today()->subDay()->toDateString(),
            'closes_on' => today()->addDay()->toDateString(),
            'time_limit_minutes' => 10, 'max_attempts' => 1, 'is_required' => 1,
        ])->assertSessionHasErrors('exam');
        $this->assertSame('allowed_errors', $rule->fresh()->pass_mode);
    }

    public function test_exam_pages_render_for_student_and_manager(): void
    {
        $s = $this->training();
        $rule = $this->rule($s);
        $this->actingAs($s['student'])->get(route('student.exams.index'))->assertOk()
            ->assertSee('Экзамен по сварке');
        $this->get(route('student.exams.show', $rule))->assertOk()
            ->assertSee('Начать / продолжить попытку');
        $attempt = $this->start($rule, $s['student']);
        $this->get(route('student.exams.attempt.show', [$rule, $attempt]))
            ->assertOk()->assertSee('Что нужно перед началом работы?');
        $this->actingAs($s['manager'])
            ->get(route('admin.training.exams.show', [$s['intake'], $rule]))
            ->assertOk()->assertSee('Журнал попыток');
        $this->get(route('admin.training.exams.attempts.show', [$s['intake'], $rule, $attempt]))
            ->assertOk()->assertSee('Аудит попытки');
    }
    public function test_manual_pause_preserves_a_running_attempt_and_its_score(): void
    {
        $s = $this->training();
        $rule = $this->rule($s);
        $attempt = $this->start($rule, $s['student']);

        $this->actingAs($s['manager'])
            ->post(route('admin.training.exams.toggle', [$s['intake'], $rule]), [
                'is_enabled' => 0,
            ])->assertRedirect();

        $this->actingAs($s['student'])->post(route('student.exams.start', $rule))
            ->assertRedirect(route('student.exams.attempt.show', [$rule, $attempt]));
        $this->assertSame('in_progress', $attempt->fresh()->status);

        $this->post(route('student.exams.attempt.submit', [$rule, $attempt]), [
            'answers' => $this->answers($s, $attempt),
        ])->assertRedirect();

        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->assertTrue($attempt->fresh()->passed);
        $this->post(route('student.exams.start', $rule))->assertSessionHasErrors('exam');
    }

}
