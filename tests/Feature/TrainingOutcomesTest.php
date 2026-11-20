<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Organization;
use App\Models\Test;
use App\Models\TrainingEnrollment;
use App\Models\TrainingExamRule;
use App\Models\TrainingIntake;
use App\Models\TrainingProgram;
use App\Models\TrainingStudiedMaterial;
use App\Models\User;
use App\Services\TrainingExamService;
use App\Services\TrainingOutcomeService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrainingOutcomesTest extends TestCase
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

    private function fixture(): array
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $course = Course::create([
            'title' => 'Пожарная безопасность',
            'slug' => 'fire-' . uniqid(), 'is_active' => true,
        ]);
        $program = TrainingProgram::create(['name' => 'Обучение безопасности', 'is_active' => true]);
        $program->courses()->create(['course_id' => $course->id]);
        $intake = TrainingIntake::create([
            'training_program_id' => $program->id, 'name' => 'Набор 25',
            'application_deadline' => today()->subDays(3),
            'starts_on' => today()->subDay(), 'ends_on' => today()->addDays(2),
            'capacity' => 40, 'required_progress_percent' => 100, 'status' => 'training',
        ]);
        $intake->courses()->create(['course_id' => $course->id]);
        $first = $course->modules()->create([
            'title' => 'Теория', 'type' => 'lesson', 'content' => 'Вводный урок',
            'is_active' => true, 'sort_order' => 1,
        ]);
        $second = $course->modules()->create([
            'title' => 'Практика', 'type' => 'lesson', 'content' => 'Практика',
            'is_active' => true, 'sort_order' => 2,
        ]);
        $enrollment = TrainingEnrollment::create([
            'training_intake_id' => $intake->id, 'user_id' => $student->id,
            'status' => 'enrolled', 'enrolled_at' => now(), 'enrolled_by' => $manager->id,
        ]);
        $test = Test::create([
            'course_id' => $course->id, 'title' => 'Итоговый контроль', 'is_active' => true,
        ]);
        $question = $test->questions()->create([
            'type' => 'multiple_choice', 'question_text' => 'Правильный выбор?',
        ]);
        $correct = $question->answers()->create([
            'answer_text' => 'Да', 'is_correct' => true,
        ]);
        $question->answers()->create([
            'answer_text' => 'Нет', 'is_correct' => false,
        ]);
        return compact('manager', 'student', 'course', 'program', 'intake',
            'enrollment', 'first', 'second', 'test', 'question', 'correct');
    }

    private function rule(array $s, string $kind = 'official'): TrainingExamRule
    {
        return $s['intake']->exams()->create([
            'test_id' => $s['test']->id, 'kind' => $kind,
            'pass_mode' => 'allowed_errors', 'allowed_errors' => 0,
            'opens_on' => today()->subDay(), 'closes_on' => today()->addDay(),
            'time_limit_minutes' => 20, 'max_attempts' => 2,
            'is_required' => $kind === 'official', 'is_enabled' => true,
        ]);
    }

    private function complete(array $s, ?TrainingEnrollment $enrollment = null): void
    {
        $enrollment ??= $s['enrollment'];
        $this->actingAs($s['student'])
            ->post(route('student.trainings.progress.complete', [
                $enrollment, $s['course'], $s['first'],
            ]))->assertRedirect();
        $this->post(route('student.trainings.progress.complete', [
            $enrollment, $s['course'], $s['second'],
        ]))->assertRedirect();
    }

    private function pass(array $s, TrainingExamRule $rule)
    {
        $service = app(TrainingExamService::class);
        $attempt = $service->start($rule, $s['student']);
        $item = $attempt->questions()->firstOrFail();
        $service->submit($attempt, $s['student'], [
            $item->id => [$s['correct']->id],
        ]);
        return $attempt->fresh();
    }

    private function outcome(array $s): array
    {
        return app(TrainingOutcomeService::class)
            ->summarize($s['intake']->fresh(), collect([$s['enrollment']->fresh()]))
            ->get($s['enrollment']->id);
    }

    public function test_viewing_material_does_not_mark_it_and_explicit_confirmation_does(): void
    {
        $s = $this->fixture();
        $this->actingAs($s['student'])
            ->get(route('student.courses.material', [$s['course'], $s['first']]))
            ->assertOk()->assertSee('Отметить изученным');
        $this->assertSame(0, TrainingStudiedMaterial::count());

        $this->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $s['first'],
        ]))->assertRedirect();
        $this->assertSame(1, TrainingStudiedMaterial::count());
        $this->assertSame(50, $this->outcome($s)['course_progress'][$s['course']->id]);
        $this->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $s['first'],
        ]))->assertRedirect();
        $this->assertSame(1, TrainingStudiedMaterial::count());

        $this->get(route('student.courses.material', [$s['course'], $s['first']]))
            ->assertOk()->assertSee('Изучено');
        $this->get(route('student.trainings.index'))->assertOk()->assertSee('50% отмечено изученным');
    }

    public function test_only_full_study_and_required_official_exams_count_as_completed(): void
    {
        $s = $this->fixture();
        $official = $this->rule($s);

        $this->complete($s);
        $outcome = $this->outcome($s);
        $this->assertSame(100, $outcome['course_progress'][$s['course']->id]);
        $this->assertSame('in_progress', $outcome['status']);
        $this->assertSame(0, $outcome['passed_exam_count']);

        $attempt = $this->pass($s, $official);
        $this->assertTrue($attempt->passed);
        $outcome = $this->outcome($s);
        $this->assertSame('passed', $outcome['status']);
        $this->assertSame(1, $outcome['passed_exam_count']);

        $this->actingAs($s['manager'])
            ->get(route('admin.training.reports.show', $s['intake']))->assertOk()
            ->assertSee('Условия обучения выполнены')
            ->assertSee('Итоговый контроль');
    }

    public function test_only_one_course_progress_percent_below_threshold_prevents_pass(): void
    {
        $s = $this->fixture();
        $official = $this->rule($s);
        $this->pass($s, $official);

        $this->actingAs($s['student'])->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $s['first'],
        ]))->assertRedirect();
        $this->assertSame(50, $this->outcome($s)['course_progress'][$s['course']->id]);
        $this->assertSame('in_progress', $this->outcome($s)['status']);

        $s['intake']->update(['required_progress_percent' => 50]);
        $this->assertSame('passed', $this->outcome($s)['status']);
    }

    public function test_trial_or_voided_attempts_never_satisfy_official_requirement(): void
    {
        $s = $this->fixture();
        $trial = $this->rule($s, 'trial');
        $this->complete($s);
        $this->pass($s, $trial);
        $this->assertSame('not_configured', $this->outcome($s)['status']);

        $official = $this->rule($s, 'official');
        $attempt = $this->pass($s, $official);
        $this->assertSame('passed', $this->outcome($s)['status']);

        $this->actingAs($s['manager'])
            ->post(route('admin.training.exams.attempts.void',
                [$s['intake'], $official, $attempt]), [
                'reason' => 'Аннулирован из-за нарушения порядка проведения',
            ])->assertRedirect();
        $this->assertSame('in_progress', $this->outcome($s)['status']);
    }

    public function test_ended_intake_reports_not_passed_without_qualifications(): void
    {
        $s = $this->fixture();
        $this->rule($s);
        $this->assertSame('in_progress', $this->outcome($s)['status']);
        $s['intake']->update(['status' => 'completed']);
        $this->assertSame('not_passed', $this->outcome($s)['status']);
    }

    public function test_progress_is_separate_for_repeated_enrollment_in_same_course(): void
    {
        $s = $this->fixture();
        $this->rule($s);
        $otherIntake = TrainingIntake::create([
            'training_program_id' => $s['program']->id, 'name' => 'Повторное обучение',
            'application_deadline' => today()->subDays(3), 'starts_on' => today()->subDay(),
            'ends_on' => today()->addDays(5), 'capacity' => 10,
            'required_progress_percent' => 100, 'status' => 'training',
        ]);
        $otherIntake->courses()->create(['course_id' => $s['course']->id]);
        $otherEnrollment = TrainingEnrollment::create([
            'training_intake_id' => $otherIntake->id,
            'user_id' => $s['student']->id, 'status' => 'enrolled',
            'enrolled_at' => now(), 'enrolled_by' => $s['manager']->id,
        ]);
        $this->complete($s);
        $this->assertSame(100, $this->outcome($s)['course_progress'][$s['course']->id]);

        $result = app(TrainingOutcomeService::class)->summarize(
            $otherIntake, collect([$otherEnrollment])
        )->get($otherEnrollment->id);
        $this->assertSame(0, $result['course_progress'][$s['course']->id]);
        $this->actingAs($s['student'])->get(route('student.trainings.index'))
            ->assertOk()->assertSee('Повторное обучение');
    }

    public function test_foreign_students_cannot_mark_materials_or_access_reports(): void
    {
        $s = $this->fixture();
        $outsider = $this->actor('Student');
        $this->actingAs($outsider)->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $s['first'],
        ]))->assertForbidden();
        $this->get(route('admin.training.reports.show', $s['intake']))->assertForbidden();
        $this->get(route('admin.training.reports.export', $s['intake']))->assertForbidden();
        $this->assertSame(0, TrainingStudiedMaterial::count());

        $this->actingAs($s['student']);
        $foreignCourse = Course::create([
            'title' => 'Не тот курс', 'slug' => 'other-' . uniqid(), 'is_active' => true,
        ]);
        $this->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $foreignCourse, $s['first'],
        ]))->assertForbidden();
        $this->assertSame(0, TrainingStudiedMaterial::count());
    }

    public function test_hidden_or_test_modules_do_not_earn_study_progress(): void
    {
        $s = $this->fixture();
        $hidden = $s['course']->modules()->create([
            'title' => 'Скрытое', 'type' => 'lesson', 'is_active' => false,
        ]);
        $testModule = $s['course']->modules()->create([
            'title' => 'Экзаменационный модуль', 'type' => 'test', 'is_active' => true,
        ]);
        $this->actingAs($s['student'])->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $hidden,
        ]))->assertNotFound();
        $this->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $testModule,
        ]))->assertNotFound();
        $this->assertSame(0, TrainingStudiedMaterial::count());

        $this->complete($s);
        $this->assertSame(100, $this->outcome($s)['course_progress'][$s['course']->id]);
    }

    public function test_organization_and_individual_report_filters_use_enrollment_snapshot(): void
    {
        $s = $this->fixture();
        $factory = Organization::create(['name' => 'Завод номер 1']);
        $otherFactory = Organization::create(['name' => 'Завод номер 2']);
        $s['student']->update(['organization_id' => $otherFactory->id]);
        $s['enrollment']->update(['organization_id' => $factory->id]);
        $individual = $this->actor('Student');
        TrainingEnrollment::create([
            'training_intake_id' => $s['intake']->id,
            'user_id' => $individual->id, 'status' => 'enrolled',
            'enrolled_at' => now(), 'enrolled_by' => $s['manager']->id,
            'organization_id' => null,
        ]);
        $this->actingAs($s['manager'])
            ->get(route('admin.training.reports.show', [$s['intake'], 'scope' => 'enterprise']))
            ->assertOk()->assertSee($s['student']->name)->assertDontSee($individual->name);
        $this->get(route('admin.training.reports.show', [$s['intake'], 'scope' => 'individual']))
            ->assertOk()->assertSee($individual->name)->assertDontSee($s['student']->name);
        $this->get(route('admin.training.reports.show', [
            $s['intake'], 'scope' => 'enterprise', 'organization_id' => $factory->id,
        ]))->assertOk()->assertSee($factory->name)->assertDontSee($otherFactory->name);
    }

    public function test_csv_preserves_filters_and_escapes_spreadsheet_formulas(): void
    {
        $s = $this->fixture();
        $s['student']->update(['name' => '=HYPERLINK("http://example.com")']);
        $this->actingAs($s['manager']);
        $response = $this->get(route('admin.training.reports.export', [
            $s['intake'], 'scope' => 'individual',
        ]));
        $response->assertOk();
        ob_start();
        $response->baseResponse->sendContent();
        $data = ob_get_clean();

        $this->assertStringContainsString('Статус', $data);
        $this->assertStringContainsString("'=HYPERLINK", $data);
        $this->assertStringContainsString('Физическое лицо', $data);
        $this->assertStringContainsString('Недостаточно условий аттестации', $data);
    }
    public function test_every_course_must_meet_threshold_not_just_average(): void
    {
        $s = $this->fixture();
        $extra = Course::create([
            'title' => 'Охрана труда', 'slug' => 'safety-' . uniqid(), 'is_active' => true,
        ]);
        $s['intake']->courses()->create(['course_id' => $extra->id]);
        $lesson = $extra->modules()->create([
            'title' => 'Дополнительный урок', 'type' => 'lesson', 'is_active' => true,
        ]);
        $official = $this->rule($s);
        $this->complete($s);
        $this->pass($s, $official);

        $state = $this->outcome($s);
        $this->assertSame(100, $state['course_progress'][$s['course']->id]);
        $this->assertSame(0, $state['course_progress'][$extra->id]);
        $this->assertSame('in_progress', $state['status']);

        $this->actingAs($s['student'])
            ->post(route('student.trainings.progress.complete', [
                $s['enrollment'], $extra, $lesson,
            ]))->assertRedirect();
        $this->assertSame('passed', $this->outcome($s)['status']);
    }

    public function test_study_confirmation_rejects_expired_and_inactive_enrollments(): void
    {
        $s = $this->fixture();
        Carbon::setTestNow(today()->addDays(10)->startOfDay());
        $this->actingAs($s['student'])->post(
            route('student.trainings.progress.complete', [
                $s['enrollment'], $s['course'], $s['first'],
            ])
        )->assertForbidden();

        Carbon::setTestNow(Carbon::parse('2026-11-21 12:00:00'));
        $s['enrollment']->update(['status' => 'cancelled']);
        $this->post(route('student.trainings.progress.complete', [
            $s['enrollment'], $s['course'], $s['first'],
        ]))->assertForbidden();
        $this->assertSame(0, TrainingStudiedMaterial::count());
    }

}
