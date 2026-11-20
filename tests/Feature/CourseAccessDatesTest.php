<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAttachment;
use App\Models\Group;
use App\Models\Organization;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseAccessDatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->travelTo(now()->setDate(2026, 11, 20)->startOfDay()->addHours(12));
    }

    private function course(string $slug): Course
    {
        return Course::create([
            'title' => $slug, 'slug' => $slug, 'is_active' => true,
        ]);
    }

    private function student(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        return $user;
    }

    private function assign(Course $course, User $student, string $start, ?string $end): CourseAssignment
    {
        return CourseAssignment::create([
            'course_id' => $course->id, 'type' => 'individual',
            'user_id' => $student->id, 'start_date' => $start,
            'end_date' => $end, 'is_active' => true,
        ]);
    }

    public function test_dates_are_inclusive_in_catalog_reader_and_tests(): void
    {
        $student = $this->student();
        $yesterday = $this->course('expired-course');
        $tomorrow = $this->course('future-course');
        $today = $this->course('today-course');
        $noEnd = $this->course('no-expiry-course');

        $this->assign($yesterday, $student, '2026-11-01', '2026-11-19');
        $this->assign($tomorrow, $student, '2026-11-21', null);
        $this->assign($today, $student, '2026-11-20', '2026-11-20');
        $this->assign($noEnd, $student, '2026-11-01', null);

        $lesson = $today->modules()->create([
            'title' => 'Сегодня доступно', 'type' => 'lesson',
            'content' => '<p>Правила безопасности</p>',
        ]);
        $expiredLesson = $yesterday->modules()->create([
            'title' => 'Недоступная лекция', 'type' => 'lesson',
        ]);
        $validTest = Test::create([
            'course_id' => $today->id, 'title' => 'Доступный тест',
            'type' => 'final', 'passing_score' => 70, 'is_active' => true,
        ]);
        $expiredTest = Test::create([
            'course_id' => $yesterday->id, 'title' => 'Старый тест',
            'type' => 'final', 'passing_score' => 70, 'is_active' => true,
        ]);

        $this->actingAs($student)->get(route('student.courses.index'))
            ->assertOk()->assertSee('today-course')->assertSee('no-expiry-course')
            ->assertDontSee('expired-course')->assertDontSee('future-course');

        $this->actingAs($student)->get(route('student.courses.show', $yesterday))
            ->assertForbidden();
        $this->actingAs($student)->get(route('student.courses.show', $tomorrow))
            ->assertForbidden();
        $this->actingAs($student)->get(route('student.courses.material', [$yesterday, $expiredLesson]))
            ->assertForbidden();
        $this->actingAs($student)->get(route('student.courses.material', [$today, $lesson]))
            ->assertOk();
        $this->actingAs($student)->get(route('student.tests.index'))
            ->assertOk()->assertSee('Доступный тест')->assertDontSee('Старый тест');
        $this->actingAs($student)->get(route('student.tests.show', $validTest))->assertOk();
        $this->actingAs($student)->get(route('student.tests.show', $expiredTest))->assertForbidden();

        // Midnight after the end date ends access to every material and test.
        $this->travelTo(now()->addDay()->startOfDay());
        $this->actingAs($student)->get(route('student.courses.show', $today))->assertForbidden();
        $this->actingAs($student)->get(route('student.tests.show', $validTest))->assertForbidden();
    }

    public function test_expired_group_assignment_prevents_course_access_even_when_membership_remains(): void
    {
        $student = $this->student();
        $course = $this->course('group-expired-course');
        $organization = Organization::create(['name' => 'Северный участок']);
        $group = Group::create(['name' => 'Вахта', 'is_active' => true, 'organization_id' => $organization->id]);
        $student->groups()->attach($group->id);
        CourseAssignment::create([
            'course_id' => $course->id, 'group_id' => $group->id, 'type' => 'group',
            'start_date' => '2026-11-01', 'end_date' => '2026-11-19', 'is_active' => true,
        ]);

        $this->actingAs($student)->get(route('student.courses.show', $course))->assertForbidden();
        $this->actingAs($student)->get(route('student.courses.index'))->assertDontSee('group-expired-course');

        $this->travelTo(now()->subDays(2));
        $this->actingAs($student)->get(route('student.courses.show', $course))->assertOk();
    }

    public function test_expired_assignment_blocks_private_attachments(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $course = $this->course('private-date-test');
        $this->assign($course, $student, '2026-11-01', '2026-11-19');
        $module = $course->modules()->create(['title' => 'Лекция', 'type' => 'lesson']);
        $file = $course->attachments()->create([
            'module_id' => $module->id,
            'uploaded_by' => $student->id,
            'original_name' => 'manual.pdf',
            'storage_path' => 'course-attachments/test.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 4,
        ]);
        Storage::disk('local')->put($file->storage_path, 'data');

        $this->actingAs($student)->get(route('course.attachments.download', [$course, $file]))
            ->assertForbidden();
        $this->travelTo(now()->subDays(2));
        $this->actingAs($student)->get(route('course.attachments.download', [$course, $file]))
            ->assertOk();
    }
}
