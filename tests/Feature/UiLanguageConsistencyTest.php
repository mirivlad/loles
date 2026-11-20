<?php

namespace Tests\Feature;

use App\Models\CourseCategory;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiLanguageConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_visible_roles_are_russian_but_stored_role_names_remain_unchanged(): void
    {
        $manager = User::factory()->create(['name' => 'Менеджер обучения']);
        $manager->assignRole('Manager');
        $student = User::factory()->create(['name' => 'Студент Петров']);
        $student->assignRole('Student');

        $this->actingAs($manager)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Менеджер')
            ->assertSee('Учащийся')
            ->assertSee('Предприятия');

        $this->actingAs($manager)->get(route('admin.users.show', $student))
            ->assertOk()->assertSee('Учащийся')->assertSee('Предприятие');

        $this->actingAs($manager)->get(route('admin.users.edit', $student))
            ->assertOk()->assertSee('Учащийся');
    }

    public function test_category_slug_is_not_shown_to_manager_in_general_list(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        CourseCategory::create([
            'name' => 'Обучение персонала',
            'slug' => 'technical-internal-category-id',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->get(route('admin.course-categories.index'))
            ->assertOk()
            ->assertSee('Обучение персонала')
            ->assertDontSee('technical-internal-category-id')
            ->assertDontSee('<th>Slug</th>', false);
    }
}
