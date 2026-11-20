<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportNavigationTest extends TestCase
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

    public function test_manager_quick_actions_and_report_link_have_real_destinations(): void
    {
        $manager = $this->actor('Manager');
        $this->actingAs($manager)->get(route('dashboard'))
            ->assertOk()->assertSee(route('admin.users.create'))
            ->assertSee(route('admin.courses.create'))
            ->assertSee(route('admin.organizations.create'))
            ->assertSee(route('admin.reports.index'));

        $this->actingAs($manager)->get(route('admin.reports.index'))
            ->assertOk()->assertSee('Отчёты по обучению')
            ->assertSee('Скачать CSV')
            ->assertSee('Назначений по этим условиям не найдено');
    }

    public function test_reports_can_be_filtered_and_exported_without_formula_injection(): void
    {
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $course = Course::create([
            'title' => '=EXPLOIT', 'slug' => 'report-test', 'is_active' => true,
        ]);
        $assignment = CourseAssignment::create([
            'course_id' => $course->id,
            'type' => 'individual',
            'user_id' => $student->id,
            'is_active' => true,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addWeek()->toDateString(),
        ]);

        $this->actingAs($manager)->get(route('admin.reports.index', [
            'q' => 'EXPLOIT', 'status' => 'active',
        ]))->assertOk()->assertSee($course->title)
            ->assertSee($student->name)
            ->assertSee('Доступно сейчас');

        $this->actingAs($manager)->get(route('admin.reports.index', [
            'status' => 'expired',
        ]))->assertOk()->assertDontSee($student->name);

        $response = $this->actingAs($manager)->get(route('admin.reports.export', [
            'status' => 'active',
        ]))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=EXPLOIT", $csv);
        $this->assertStringContainsString($student->name, $csv);
        $this->assertStringContainsString('Доступно сейчас', $csv);
    }

    public function test_student_cannot_open_or_export_admin_reports(): void
    {
        $student = $this->actor('Student');
        $this->actingAs($student)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.reports.export'))->assertForbidden();
    }
}
