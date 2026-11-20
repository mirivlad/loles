<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function actor(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_completing_group_preserves_students_assignments_and_history(): void
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $organization = Organization::create(['name' => 'Цех электриков']);
        $student->update(['organization_id' => $organization->id]);

        $course = Course::create([
            'title' => 'Работы с электрикой', 'slug' => 'electrical',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->post(route('admin.groups.store'), [
            'group_type' => 'organization',
            'organization_id' => $organization->id,
            'name' => 'Вахта №15',
            'status' => 'planned',
            'started_on' => today()->toDateString(),
        ]);
        $response->assertRedirect(route('admin.groups.index'));

        $group = Group::firstOrFail();
        $this->assertSame('planned', $group->status);
        $this->assertTrue($group->is_active);

        $group->users()->attach($student->id);
        CourseAssignment::create([
            'course_id' => $course->id, 'type' => 'group',
            'group_id' => $group->id, 'is_active' => true,
            'start_date' => today()->subDay()->toDateString(),
            'end_date' => today()->addWeek()->toDateString(),
        ]);

        $this->actingAs($manager)->put(route('admin.groups.update', $group), [
            'name' => 'Вахта №15',
            'description' => 'Отучились',
            'status' => 'completed',
            'started_on' => today()->subWeek()->toDateString(),
        ])->assertRedirect(route('admin.groups.show', $group));

        $group->refresh();
        $this->assertSame('completed', $group->status);
        $this->assertFalse($group->is_active);
        $this->assertNotNull($group->finished_on);
        $this->assertSame(1, $group->users()->count());
        $this->assertSame(1, $group->courseAssignments()->count());

        $this->actingAs($manager)->get(route('admin.groups.show', $group))
            ->assertOk()->assertSee('Завершена')->assertSee('Вахта №15')
            ->assertSee('Состав группы');

        $this->actingAs($manager)->delete(route('admin.groups.destroy', $group))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('groups', ['id' => $group->id]);

        $this->actingAs($manager)->post(route('admin.groups.users.add', $group), [
            'user_ids' => (string) $student->id,
        ])->assertStatus(409);

        $this->actingAs($student)->get(route('admin.groups.show', $group))
            ->assertForbidden();
        // Closing a group is not a way to silently revoke an assigned course.
        // Access is governed by the assignment's actual date window.
        $this->actingAs($student)->get(route('student.courses.show', $course))
            ->assertOk();
    }

    public function test_archived_group_cannot_be_edited_by_student(): void
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $organization = Organization::create(['name' => 'Склад']);
        $group = Group::create([
            'name' => 'Смена Б',
            'organization_id' => $organization->id,
            'is_active' => false,
            'status' => 'archived',
        ]);

        $this->actingAs($manager)->get(route('admin.groups.show', $group))
            ->assertOk()->assertSee('В архиве');
        $this->actingAs($student)->put(route('admin.groups.update', $group), [
            'name' => 'Смена Б', 'status' => 'active',
        ])->assertForbidden();
        $this->actingAs($student)->post(route('admin.groups.users.add', $group), [
            'user_ids' => (string) $student->id,
        ])->assertForbidden();
    }
}
