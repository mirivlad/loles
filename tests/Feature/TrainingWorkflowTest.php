<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Organization;
use App\Models\TrainingApplication;
use App\Models\TrainingIntake;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrainingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
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

    private function intake(User $manager, int $capacity = 2, string $status = 'open'): TrainingIntake
    {
        $course = Course::create([
            'title' => 'Сварочное дело', 'slug' => 'weld-course-' . uniqid(),
            'is_active' => true,
        ]);
        $this->actingAs($manager)->post(route('admin.training.programs.store'), [
            'name' => 'Подготовка сварщиков', 'is_active' => 1,
        ])->assertRedirect();
        $program = TrainingProgram::latest('id')->firstOrFail();
        $this->post(route('admin.training.programs.courses.add', $program), [
            'course_id' => $course->id,
        ])->assertRedirect();
        $this->post(route('admin.training.intakes.store'), [
            'training_program_id' => $program->id,
            'name' => 'Декабрьские сварщики',
            'application_deadline' => today()->addDays(2)->toDateString(),
            'starts_on' => today()->addDays(3)->toDateString(),
            'ends_on' => today()->addDays(14)->toDateString(),
            'capacity' => $capacity,
            'required_progress_percent' => 100,
            'status' => $status,
        ])->assertRedirect();
        return TrainingIntake::latest('id')->firstOrFail();
    }

    private function submit(TrainingIntake $intake, array $overrides = []): TrainingApplication
    {
        $data = array_merge([
            'applicant_type' => 'individual',
            'contact_name' => 'Иванов Иван',
            'students_text' => '',
        ], $overrides);
        $this->post(route('admin.training.applications.store', $intake), $data)
            ->assertRedirect();
        return TrainingApplication::latest('id')->firstOrFail();
    }

    private function approveAndPay(TrainingIntake $intake, TrainingApplication $application): void
    {
        $this->post(route('admin.training.applications.review', [$intake, $application]), [
            'decision' => 'approved',
        ])->assertRedirect();
        $this->post(route('admin.training.applications.payment', [$intake, $application]), [
            'payment_status' => 'paid',
        ])->assertRedirect();
    }

    public function test_manager_creates_program_and_intake_with_immutable_course_snapshot(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager);
        $program = $intake->program;

        $this->assertEqualsCanonicalizing(
            $program->courses->pluck('course_id')->all(),
            $intake->courses->pluck('course_id')->all()
        );
        $course = $intake->courses->first()->course;
        $this->delete(route('admin.training.programs.courses.remove', [$program, $course]))->assertRedirect();
        $this->assertSame(0, $program->courses()->count());
        $this->assertSame(1, $intake->fresh()->courses()->count());

        $this->get(route('admin.training.programs.show', $program))->assertOk()->assertSee('Подготовка сварщиков');
        $this->get(route('admin.training.intakes.show', $intake))->assertOk()->assertSee('Декабрьские сварщики');

        $student = $this->actor('Student');
        $this->actingAs($student)->get(route('admin.training.intakes.index'))->assertForbidden();
        $this->get(route('admin.training.programs.index'))->assertForbidden();
        $this->get(route('student.courses.show', $course))->assertForbidden();
    }

    public function test_application_is_separate_from_approval_payment_and_enrollment(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager, 3);
        $organization = Organization::create(['name' => 'Энергомонтаж', 'is_active' => true]);
        $application = $this->submit($intake, [
            'applicant_type' => 'organization',
            'organization_id' => $organization->id,
            'contact_name' => 'Главный инженер',
            'students_text' => "Иванов Иван\nПетров Пётр",
        ]);
        $this->assertSame('pending', $application->status);
        $this->assertSame('unpaid', $application->payment_status);
        $this->assertSame(0, $intake->enrollments()->count());

        $this->post(route('admin.training.applications.enroll', [$intake, $application]))
            ->assertSessionHasErrors('enrollment');
        $this->approveAndPay($intake, $application);

        $this->post(route('admin.training.intakes.groups.add', $intake), ['name' => 'Утренняя'])
            ->assertRedirect();
        $group = $intake->groups()->firstOrFail();

        $this->post(route('admin.training.applications.enroll', [$intake, $application]), [
            'training_intake_group_id' => $group->id,
        ])->assertRedirect();

        $this->assertSame(2, $intake->enrollments()->count());
        $this->assertSame(2, $application->students()->whereNotNull('user_id')->count());
        foreach ($intake->enrollments()->with('user.issuedCredential')->get() as $enrollment) {
            $this->assertSame($organization->id, $enrollment->organization_id);
            $this->assertSame($group->id, $enrollment->training_intake_group_id);
            $this->assertNull($enrollment->user->organization_id);
            $this->assertTrue($enrollment->user->hasRole('Student'));
            $this->assertNotEmpty($enrollment->user->issuedCredential->secret);
        }
        $this->post(route('admin.training.applications.enroll', [$intake, $application]))
            ->assertSessionHasErrors('enrollment');
        $this->assertSame(2, $intake->enrollments()->count());
    }

    public function test_capacity_overflow_rolls_back_and_preserves_every_applicant(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager, 1);
        $application = $this->submit($intake, [
            'applicant_type' => 'organization',
            'organization_id' => Organization::create(['name' => 'Ремонтный участок'])->id,
            'contact_name' => 'Секретарь',
            'students_text' => "Первый\nВторой",
        ]);
        $this->approveAndPay($intake, $application);

        $this->post(route('admin.training.applications.enroll', [$intake, $application]))
            ->assertSessionHasErrors('enrollment');
        $this->assertSame(0, $intake->enrollments()->count());
        $this->assertSame(2, $application->students()->count());
        $this->assertSame(0, $application->students()->whereNotNull('user_id')->count());
        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_intake_and_group_must_match_when_enrolling(): void
    {
        $manager = $this->actor('Manager');
        $first = $this->intake($manager, 4);
        $other = $this->intake($manager, 4);
        $group = $other->groups()->create(['name' => 'Не тот набор']);
        $application = $this->submit($first);
        $this->approveAndPay($first, $application);

        $this->post(route('admin.training.applications.enroll', [$first, $application]), [
            'training_intake_group_id' => $group->id,
        ])->assertSessionHasErrors('training_intake_group_id');
        $this->assertSame(0, $first->enrollments()->count());
    }

    public function test_existing_user_is_not_enrolled_twice_in_same_intake(): void
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $intake = $this->intake($manager, 5);
        $first = $this->submit($intake, ['user_id' => $student->id]);
        $other = $this->submit($intake, ['user_id' => $student->id]);
        $this->approveAndPay($intake, $first);
        $this->approveAndPay($intake, $other);
        $this->post(route('admin.training.applications.enroll', [$intake, $first]))->assertRedirect();
        $this->post(route('admin.training.applications.enroll', [$intake, $other]))
            ->assertSessionHasErrors('enrollment');
        $this->assertSame(1, $intake->enrollments()->count());
    }

    public function test_deadline_is_inclusive_and_late_application_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-10 12:00:00'));
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager);
        $intake->update(['application_deadline' => today()->toDateString()]);
        $this->actingAs($manager);
        $this->post(route('admin.training.applications.store', $intake), [
            'applicant_type' => 'individual', 'contact_name' => 'Елена',
        ])->assertRedirect();
        $this->assertSame(1, $intake->applications()->count());

        Carbon::setTestNow(Carbon::parse('2026-12-11 00:00:00'));
        $this->post(route('admin.training.applications.store', $intake), [
            'applicant_type' => 'individual', 'contact_name' => 'Мария',
        ])->assertSessionHasErrors('training_intake_id');
        $this->assertSame(1, $intake->applications()->count());
    }

    public function test_intake_requires_program_courses_and_consistent_dates(): void
    {
        $manager = $this->actor('Manager');
        $this->actingAs($manager)->post(route('admin.training.programs.store'), [
            'name' => 'Пустая программа', 'is_active' => 1,
        ])->assertRedirect();
        $program = TrainingProgram::firstOrFail();
        $valid = [
            'training_program_id' => $program->id, 'name' => 'Набор',
            'application_deadline' => today()->toDateString(),
            'starts_on' => today()->addDay()->toDateString(),
            'ends_on' => today()->addDays(3)->toDateString(),
            'capacity' => 4, 'required_progress_percent' => 80, 'status' => 'open',
        ];
        $this->post(route('admin.training.intakes.store'), $valid)
            ->assertSessionHasErrors('training_program_id');
        $this->post(route('admin.training.intakes.store'), array_merge($valid, [
            'application_deadline' => today()->addDays(5)->toDateString(),
        ]))->assertSessionHasErrors('application_deadline');
        $this->assertSame(0, TrainingIntake::count());
    }
    public function test_manager_pages_render_and_students_are_denied(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager, 5);
        $program = $intake->program;
        $application = $this->submit($intake);

        foreach ([
            route('admin.training.programs.index'),
            route('admin.training.programs.create'),
            route('admin.training.programs.show', $program),
            route('admin.training.programs.edit', $program),
            route('admin.training.intakes.index'),
            route('admin.training.intakes.create'),
            route('admin.training.intakes.show', $intake),
            route('admin.training.intakes.edit', $intake),
            route('admin.training.applications.create', $intake),
            route('admin.training.applications.show', [$intake, $application]),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $student = $this->actor('Student');
        $this->actingAs($student)->get(route('admin.training.applications.show', [$intake, $application]))
            ->assertForbidden();
    }

    public function test_manager_can_reassign_people_to_groups_within_the_same_intake(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager);
        $application = $this->submit($intake);
        $this->approveAndPay($intake, $application);
        $this->post(route('admin.training.applications.enroll', [$intake, $application]))
            ->assertRedirect();
        $enrollment = $intake->enrollments()->firstOrFail();

        $group = $intake->groups()->create(['name' => 'Дневная']);
        $this->put(route('admin.training.intakes.enrollments.group', [$intake, $enrollment]), [
            'training_intake_group_id' => $group->id,
        ])->assertRedirect();
        $this->assertSame($group->id, $enrollment->fresh()->training_intake_group_id);

        $other = $this->intake($manager);
        $wrongGroup = $other->groups()->create(['name' => 'Чужая группа']);
        $this->put(route('admin.training.intakes.enrollments.group', [$intake, $enrollment]), [
            'training_intake_group_id' => $wrongGroup->id,
        ])->assertSessionHasErrors('training_intake_group_id');
        $this->assertSame($group->id, $enrollment->fresh()->training_intake_group_id);
    }

    public function test_status_transitions_and_archiving_preserve_history(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager, 2, 'draft');
        $base = [
            'name' => $intake->name,
            'application_deadline' => $intake->application_deadline->toDateString(),
            'starts_on' => $intake->starts_on->toDateString(),
            'ends_on' => $intake->ends_on->toDateString(),
            'capacity' => $intake->capacity,
            'required_progress_percent' => 100,
        ];

        $this->put(route('admin.training.intakes.update', $intake), array_merge($base, [
            'status' => 'completed',
        ]))->assertSessionHasErrors('status');
        $this->assertSame('draft', $intake->fresh()->status);

        foreach (['open', 'training', 'completed'] as $status) {
            $this->put(route('admin.training.intakes.update', $intake), array_merge($base, [
                'status' => $status,
            ]))->assertRedirect();
            $this->assertSame($status, $intake->fresh()->status);
        }
        $this->post(route('admin.training.intakes.archive', $intake))->assertRedirect();
        $this->assertSame('archived', $intake->fresh()->status);
        $this->get(route('admin.training.intakes.edit', $intake))->assertStatus(409);
    }

    public function test_manager_cannot_reduce_capacity_below_existing_enrollments(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager, 2);
        $application = $this->submit($intake, [
            'applicant_type' => 'organization',
            'organization_id' => Organization::create(['name' => 'Сборка'])->id,
            'contact_name' => 'Диспетчер',
            'students_text' => "Первый\nВторой",
        ]);
        $this->approveAndPay($intake, $application);
        $this->post(route('admin.training.applications.enroll', [$intake, $application]))
            ->assertRedirect();
        $this->put(route('admin.training.intakes.update', $intake), [
            'name' => $intake->name,
            'application_deadline' => $intake->application_deadline->toDateString(),
            'starts_on' => $intake->starts_on->toDateString(),
            'ends_on' => $intake->ends_on->toDateString(),
            'capacity' => 1,
            'required_progress_percent' => 100,
            'status' => 'open',
        ])->assertSessionHasErrors('capacity');
        $this->assertSame(2, $intake->fresh()->capacity);
    }

    public function test_enrollment_controls_access_to_courses_and_tests_with_inclusive_dates(): void
    {
        $manager = $this->actor('Manager');
        $intake = $this->intake($manager, 2);
        $course = $intake->courses()->firstOrFail()->course;
        $test = \App\Models\Test::create([
            'course_id' => $course->id,
            'title' => 'Проверка знаний',
            'is_active' => true,
        ]);
        $application = $this->submit($intake);
        $this->approveAndPay($intake, $application);
        $this->post(route('admin.training.applications.enroll', [$intake, $application]))
            ->assertRedirect();
        $student = $intake->enrollments()->with('user')->firstOrFail()->user;
        $this->actingAs($student);

        $this->get(route('student.trainings.index'))->assertOk()->assertSee($intake->name);
        $this->get(route('student.courses.show', $course))->assertForbidden();
        $this->get(route('student.tests.show', $test))->assertForbidden();

        $intake->update(['status' => 'training']);
        Carbon::setTestNow($intake->starts_on->copy()->startOfDay());
        $this->get(route('student.trainings.index'))->assertOk()
            ->assertSee('Обучение доступно');
        $this->get(route('student.courses.index'))->assertOk()->assertSee($course->title);
        $this->get(route('student.courses.show', $course))->assertOk();
        $this->get(route('student.tests.index'))->assertOk()->assertSee($test->title);
        $this->get(route('student.tests.show', $test))->assertOk();
        $this->get(route('dashboard'))->assertOk()->assertSee($course->title);

        Carbon::setTestNow($intake->ends_on->copy()->endOfDay());
        $this->get(route('student.courses.show', $course))->assertOk();
        Carbon::setTestNow($intake->ends_on->copy()->addDay()->startOfDay());
        $this->get(route('student.courses.show', $course))->assertForbidden();
        $this->get(route('student.tests.show', $test))->assertForbidden();
    }

    public function test_old_assignment_cannot_bypass_enrollment_for_intake_courses(): void
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $intake = $this->intake($manager);
        $course = $intake->courses()->firstOrFail()->course;
        $intake->update(['status' => 'training']);
        \App\Models\CourseAssignment::create([
            'course_id' => $course->id,
            'type' => 'individual',
            'user_id' => $student->id,
            'is_active' => true,
            'start_date' => today()->subDay()->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
        ]);
        Carbon::setTestNow($intake->starts_on->copy()->startOfDay());
        $this->actingAs($student)->get(route('student.courses.show', $course))->assertForbidden();
        $this->get(route('student.courses.index'))->assertDontSee($course->title);
        $this->get(route('student.trainings.index'))->assertDontSee($intake->name);
    }

}
