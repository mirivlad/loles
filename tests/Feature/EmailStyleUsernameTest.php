<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailStyleUsernameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        return $admin;
    }

    public function test_admin_creates_manager_with_login_equal_to_email_and_manager_can_sign_in(): void
    {
        $admin = $this->administrator();
        $login = 'manager.ivanov+shift@company.example';
        $password = 'strongpassword1';

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Иван Иванов',
            'username' => 'Manager.Ivanov+Shift@Company.Example',
            'email' => $login,
            'role' => 'Manager',
            'password' => $password,
            'password_confirmation' => $password,
            'is_active' => 1,
        ])->assertRedirect();

        $manager = User::where('username', $login)->firstOrFail();
        $this->assertSame($login, $manager->email);
        $this->assertTrue($manager->hasRole('Manager'));
        $this->assertTrue(Hash::check($password, $manager->password));

        $this->post(route('logout'))->assertRedirect('/');
        $this->post('/login', [
            'username' => 'Manager.Ivanov+Shift@Company.Example',
            'password' => $password,
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($manager);
    }

    public function test_staff_can_use_email_shaped_login_without_contact_email(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Менеджер без почты',
            'username' => 'shift.manager@company.example',
            'role' => 'Manager',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
            'is_active' => 1,
        ])->assertRedirect();

        $manager = User::where('username', 'shift.manager@company.example')->firstOrFail();
        $this->assertNull($manager->email);
        $this->assertTrue($manager->hasRole('Manager'));
    }

    public function test_administrator_can_change_existing_manager_login_to_email_without_changing_password(): void
    {
        $admin = $this->administrator();
        $manager = User::factory()->create([
            'username' => 'shiftmanager',
            'email' => 'manager@company.example',
            'password' => 'original-password-123',
        ]);
        $manager->assignRole('Manager');

        $this->actingAs($admin)->put(route('admin.users.update', $manager), [
            'name' => $manager->name,
            'username' => 'MANAGER@COMPANY.EXAMPLE',
            'email' => $manager->email,
            'role' => 'Manager',
            'is_active' => 1,
        ])->assertRedirect();

        $manager->refresh();
        $this->assertSame('manager@company.example', $manager->username);
        $this->assertTrue(Hash::check('original-password-123', $manager->password));
    }

    public function test_duplicate_email_style_login_is_rejected_case_insensitively(): void
    {
        $admin = $this->administrator();
        $manager = User::factory()->create(['username' => 'shift@company.example']);
        $manager->assignRole('Manager');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Повтор логина',
            'username' => 'SHIFT@COMPANY.EXAMPLE',
            'role' => 'Manager',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
        ])->assertSessionHasErrors('username');
    }

    public function test_invalid_email_style_login_is_rejected(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Ошибочный логин',
            'username' => 'invalid@',
            'role' => 'Manager',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
        ])->assertSessionHasErrors('username');
    }
}
