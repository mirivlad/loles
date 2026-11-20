<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserCredential;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkerAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function manager(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        return $manager;
    }

    private function student(string $username = 'worker01', string $password = 'abc123'): User
    {
        $student = User::factory()->create([
            'name' => 'Тестовый учащийся',
            'username' => $username,
            'email' => null,
            'password' => $password,
            'is_active' => true,
        ]);
        $student->assignRole('Student');
        return $student;
    }

    public function test_student_can_log_in_without_email_using_username_and_six_character_password(): void
    {
        $student = $this->student();

        $this->post('/login', [
            'username' => 'worker01',
            'password' => 'abc123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($student);
    }

    public function test_email_is_not_an_authentication_field(): void
    {
        $student = $this->student();

        $this->post('/login', [
            'email' => 'worker@example.test',
            'password' => 'abc123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_self_registration_is_disabled_by_default(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Тест',
            'username' => 'anyone',
            'password' => 'abcdef',
            'password_confirmation' => 'abcdef',
        ])->assertNotFound();
    }

    public function test_manager_creates_student_with_generated_login_and_password_without_email(): void
    {
        $manager = $this->manager();
        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Александр Рабочий',
            'role' => 'Student',
            'generate_password' => 1,
            'is_active' => 1,
        ]);
        $response->assertRedirect();

        $student = User::where('name', 'Александр Рабочий')->firstOrFail();
        $this->assertNull($student->email);
        $this->assertMatchesRegularExpression('/^s\d{5,}$/', $student->username);
        $this->assertTrue($student->hasRole('Student'));

        $credential = $student->issuedCredential()->firstOrFail();
        $this->assertSame(6, strlen($credential->secret));
        $this->assertTrue(Hash::check($credential->secret, $student->password));
        $this->assertNotSame($credential->secret, $credential->getRawOriginal('secret'));
    }

    public function test_manager_can_add_student_to_enterprise_group_in_one_form(): void
    {
        $manager = $this->manager();
        $organization = Organization::create(['name' => 'Северный учебный центр', 'is_active' => true]);
        $group = Group::create([
            'name' => 'Вахта — день', 'organization_id' => $organization->id, 'is_active' => true,
        ]);

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Пётр Монтажник',
            'role' => 'Student',
            'organization_id' => $organization->id,
            'group_id' => $group->id,
            'generate_password' => 1,
            'is_active' => 1,
        ])->assertRedirect();

        $student = User::where('name', 'Пётр Монтажник')->firstOrFail();
        $this->assertTrue($student->groups()->where('groups.id', $group->id)->exists());
        $this->assertTrue($student->hasRole('Student'));
    }

    public function test_manager_can_choose_longer_generated_password(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Учащийся с длинным паролем',
            'role' => 'Student',
            'generate_password' => 1,
            'generated_password_length' => 8,
            'is_active' => 1,
        ])->assertRedirect();

        $student = User::where('name', 'Учащийся с длинным паролем')->firstOrFail();
        $this->assertSame(8, strlen($student->issuedCredential->secret));
    }

    public function test_manager_can_set_manual_six_character_password_for_student(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Борис Монтажник',
            'username' => 'montazh07',
            'role' => 'Student',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
            'generate_password' => 0,
            'is_active' => 1,
        ])->assertRedirect();

        $student = User::where('username', 'montazh07')->firstOrFail();
        $this->assertTrue(Hash::check('abc123', $student->password));
        $this->assertSame('abc123', $student->issuedCredential->secret);
    }

    public function test_manager_cannot_issue_short_password_for_staff_account(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Тест Куратор',
            'username' => 'curator07',
            'role' => 'Curator',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
            'generate_password' => 0,
        ])->assertSessionHasErrors('password');
    }

    public function test_only_manager_or_admin_can_reveal_student_passwords(): void
    {
        $manager = $this->manager();
        $student = $this->student();
        app(\App\Services\StudentCredentials::class)->issue($student, 'abc123', $manager);

        $this->actingAs($manager)->postJson(route('admin.users.credentials.reveal'), [
            'user_ids' => [$student->id],
        ])->assertOk()->assertJsonPath('credentials.0.password', 'abc123');

        $this->actingAs($student)->postJson(route('admin.users.credentials.reveal'), [
            'user_ids' => [$student->id],
        ])->assertForbidden();
    }

    public function test_manager_can_export_group_credentials_as_csv(): void
    {
        $manager = $this->manager();
        $student = $this->student();
        $organization = Organization::create(['name' => 'Тестовый учебный участок', 'is_active' => true]);
        $student->update(['organization_id' => $organization->id]);
        $group = Group::create([
            'name' => 'Вахта 2',
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);
        $student->groups()->attach($group);
        app(\App\Services\StudentCredentials::class)->issue($student, 'abc123', $manager);

        $response = $this->actingAs($manager)->post(route('admin.users.credentials.export'), [
            'group_id' => $group->id,
        ]);
        $response->assertOk();
        $this->assertStringContainsString('worker01', $response->streamedContent());
        $this->assertStringContainsString('abc123', $response->streamedContent());

        $this->actingAs($student)->post(route('admin.users.credentials.export'), [
            'group_id' => $group->id,
        ])->assertForbidden();
    }

    public function test_manager_can_download_xlsx_credentials_as_text_cells(): void
    {
        $manager = $this->manager();
        $organization = Organization::create(['name' => 'Организация для Excel']);
        $student = $this->student('workerxlsx', 'abc123');
        $student->update(['organization_id' => $organization->id]);
        app(\App\Services\StudentCredentials::class)->issue($student, 'abc123', $manager);

        $response = $this->actingAs($manager)->post(route('admin.users.credentials.export'), [
            'organization_id' => $organization->id,
            'format' => 'xlsx',
        ]);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->baseResponse->getFile()->getPathname();
        try {
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $book->getActiveSheet();
            $this->assertSame('Логин', $sheet->getCell('B4')->getValue());
            $this->assertSame('workerxlsx', $sheet->getCell('B5')->getValue());
            $this->assertSame('abc123', $sheet->getCell('C5')->getValue());
            $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $sheet->getCell('C5')->getDataType());
            $book->disconnectWorksheets();
        } finally {
            @unlink($file);
        }
    }

    public function test_manager_can_download_printable_docx_credentials(): void
    {
        $manager = $this->manager();
        $organization = Organization::create(['name' => 'Организация для Word']);
        $student = $this->student('workerdocx', 'abc123');
        $student->update(['organization_id' => $organization->id]);
        app(\App\Services\StudentCredentials::class)->issue($student, 'abc123', $manager);

        $response = $this->actingAs($manager)->post(route('admin.users.credentials.export'), [
            'organization_id' => $organization->id,
            'format' => 'docx',
        ]);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $file = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        try {
            $this->assertTrue($zip->open($file));
            $xml = $zip->getFromName('word/document.xml');
            $this->assertIsString($xml);
            $this->assertStringContainsString('workerdocx', $xml);
            $this->assertStringContainsString('abc123', $xml);
            $this->assertStringContainsString('Ведомость выдачи', $xml);
            $zip->close();
        } finally {
            @unlink($file);
        }
    }

    public function test_credential_export_rejects_unknown_format(): void
    {
        $manager = $this->manager();
        $organization = Organization::create(['name' => 'Тестовый цех']);

        $this->actingAs($manager)->post(route('admin.users.credentials.export'), [
            'organization_id' => $organization->id,
            'format' => 'pdf',
        ])->assertSessionHasErrors('format');
    }

    public function test_demo_data_command_is_idempotent_and_credentials_are_not_fixed(): void
    {
        $this->artisan('loles:demo-data', ['--force' => true])->assertExitCode(0);
        $this->assertSame(6, User::where('username', 'like', 'demo%')->count());
        $this->assertSame(2, Organization::where('name', 'like', 'ДЕМО%')->count());
        $this->assertSame(1, Course::where('slug', 'loles-demo-introduction')->count());

        $credential = User::where('username', 'demo01')->firstOrFail()->issuedCredential()->firstOrFail();
        $this->assertSame(6, strlen($credential->secret));
        $this->assertNotSame('password', $credential->secret);

        $this->artisan('loles:demo-data', ['--force' => true])->assertExitCode(0);
        $this->assertSame(6, User::where('username', 'like', 'demo%')->count());
    }
}
