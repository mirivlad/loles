<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function manager(): User
    {
        $manager = User::factory()->create(['name' => 'Менеджер каталога']);
        $manager->assignRole('Manager');

        return $manager;
    }

    public function test_manager_can_filter_users_by_organization_status_and_name(): void
    {
        $manager = $this->manager();
        $organization = Organization::create([
            'name' => 'Первая компания',
            'is_active' => true,
        ]);
        $another = Organization::create([
            'name' => 'Вторая компания',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Алиса Соколова',
            'email' => 'alisa@example.test',
            'is_active' => true,
            'organization_id' => $organization->id,
        ]);
        User::factory()->create([
            'name' => 'Борис Котов',
            'email' => 'boris@example.test',
            'is_active' => false,
            'organization_id' => $organization->id,
        ]);
        User::factory()->create([
            'name' => 'Виктор Петров',
            'email' => 'viktor@example.test',
            'is_active' => true,
            'organization_id' => $another->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.users.index', [
                'search' => 'Алиса',
                'status' => 'active',
                'organization_id' => $organization->id,
            ]))
            ->assertOk()
            ->assertSee('Алиса Соколова')
            ->assertDontSee('Борис Котов')
            ->assertDontSee('Виктор Петров');
    }

    public function test_manager_can_find_active_organizations_by_name_and_inn(): void
    {
        $manager = $this->manager();
        Organization::create(['name' => 'Иркутский комбинат', 'inn' => '1234567890', 'is_active' => true]);
        Organization::create(['name' => 'Томский завод', 'inn' => '9876543210', 'is_active' => false]);

        $this->actingAs($manager)
            ->get(route('admin.organizations.index', [
                'search' => '1234567890',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee('Иркутский комбинат')
            ->assertDontSee('Томский завод');
    }

    public function test_manager_can_remove_user_from_organization_via_ajax(): void
    {
        $manager = $this->manager();
        $organization = Organization::create(['name' => 'Городское предприятие']);
        $student = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($manager)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->deleteJson(route('admin.organizations.users.remove', [$organization, $student]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($student->fresh()->organization_id);
    }

    public function test_manager_cannot_create_an_administrator(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Незаконный администратор',
            'email' => 'bad-admin@example.test',
            'password' => 'safe-password-12345',
            'password_confirmation' => 'safe-password-12345',
            'role' => 'Administrator',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'bad-admin@example.test']);
    }

    public function test_manager_cannot_edit_administrators(): void
    {
        $manager = $this->manager();
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $this->actingAs($manager)
            ->get(route('admin.users.edit', $admin))
            ->assertForbidden();
    }

    public function test_last_administrator_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
