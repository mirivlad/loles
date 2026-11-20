<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseRequestApprovalChoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function requestPayload(string $status, User $learner, Course $course): array
    {
        return [
            'status' => $status,
            'note' => 'Согласовать обучение вахтовиков',
            'items_json' => json_encode([[
                'course_id' => $course->id,
                'user_ids' => [$learner->id],
                'start_date' => today()->toDateString(),
                'end_date' => today()->addWeek()->toDateString(),
            ]]),
        ];
    }

    public function test_manager_can_create_pending_request_without_granting_access_until_approval(): void
    {
        $manager = $this->user('Manager');
        $learner = $this->user('Student');
        $course = Course::create(['title' => 'Курс заявок', 'slug' => 'requests-pending', 'is_active' => true]);

        $this->actingAs($manager)
            ->get(route('admin.course-requests.create'))
            ->assertOk()
            ->assertSee('Ожидает подтверждения — сохранить без назначения')
            ->assertSee('Одобрена — сразу назначить обучение');

        $this->actingAs($manager)
            ->post(route('admin.course-requests.store'), $this->requestPayload('pending', $learner, $course))
            ->assertRedirect(route('admin.course-requests.index'));

        $request = CourseRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->approved_by);
        $this->assertDatabaseCount('course_assignments', 0);

        $this->actingAs($learner)->get(route('student.courses.show', $course))->assertForbidden();

        $this->actingAs($manager)->post(route('admin.course-requests.approve', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertDatabaseCount('course_assignments', 1);
        $this->actingAs($learner)->get(route('student.courses.show', $course))->assertOk();
    }

    public function test_explicit_approval_creates_assignment_and_missing_status_defaults_to_pending(): void
    {
        $manager = $this->user('Manager');
        $learner = $this->user('Student');
        $course = Course::create(['title' => 'Курс без статуса', 'slug' => 'requests-default', 'is_active' => true]);

        $payload = $this->requestPayload('pending', $learner, $course);
        unset($payload['status']);
        $this->actingAs($manager)->post(route('admin.course-requests.store'), $payload)
            ->assertRedirect(route('admin.course-requests.index'));
        $this->assertSame('pending', CourseRequest::firstOrFail()->status);
        $this->assertDatabaseCount('course_assignments', 0);

        $this->actingAs($manager)->post(route('admin.course-requests.store'), $this->requestPayload('approved', $learner, $course))
            ->assertRedirect(route('admin.course-requests.index'));
        $this->assertDatabaseHas('course_requests', ['status' => 'approved']);
        $this->assertDatabaseCount('course_assignments', 1);
    }

    public function test_rejected_is_not_a_valid_initial_status(): void
    {
        $manager = $this->user('Manager');
        $student = $this->user('Student');
        $course = Course::create(['title' => 'Курс валидации', 'slug' => 'requests-invalid', 'is_active' => true]);
        $this->actingAs($manager)->post(route('admin.course-requests.store'), $this->requestPayload('rejected', $student, $course))
            ->assertSessionHasErrors('status');
        $this->assertDatabaseCount('course_requests', 0);
    }
}
