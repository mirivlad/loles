<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAttachment;
use App\Models\CourseModule;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseReaderAndAttachmentsTest extends TestCase
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

    private function course(string $suffix = 'sample', bool $active = true): Course
    {
        return Course::create([
            'title' => 'Работы на высоте',
            'slug' => 'course-' . $suffix,
            'is_active' => $active,
            'description' => 'Практическое обучение',
        ]);
    }

    private function assign(Course $course, User $student): void
    {
        CourseAssignment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'type' => 'individual',
            'start_date' => today()->toDateString(),
            'is_active' => true,
        ]);
    }

    public function test_course_opens_with_contents_and_separate_sequential_material_pages(): void
    {
        $student = $this->actor('Student');
        $course = $this->course();
        $this->assign($course, $student);

        $section = $course->modules()->create([
            'title' => 'Безопасные работы',
            'type' => 'section', 'sort_order' => 10,
        ]);
        $first = $course->modules()->create([
            'title' => 'Вводная лекция',
            'type' => 'lesson', 'sort_order' => 10,
            'parent_id' => $section->id, 'content' => '<p>Первый материал</p>',
        ]);
        $hidden = $course->modules()->create([
            'title' => 'Черновик инструкции',
            'type' => 'lesson', 'sort_order' => 20, 'is_active' => false,
        ]);
        $second = $course->modules()->create([
            'title' => 'Работа со страховкой',
            'type' => 'lesson', 'sort_order' => 30,
            'content' => '<p>Второй материал</p>',
        ]);
        $test = Test::create([
            'course_id' => $course->id, 'title' => 'Промежуточный тест',
            'type' => 'final', 'is_active' => true, 'passing_score' => 70,
        ]);
        $third = $course->modules()->create([
            'title' => 'Проверка знаний', 'type' => 'test',
            'test_id' => $test->id, 'sort_order' => 40,
        ]);

        $this->actingAs($student)->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertSee('Содержание курса')
            ->assertSee('Вводная лекция')
            ->assertSee('Безопасные работы')
            ->assertSee('Работа со страховкой')
            ->assertDontSee('Черновик инструкции')
            ->assertSee(route('student.courses.material', [$course, $first]));

        $this->actingAs($student)->get(route('student.courses.material', [$course, $first]))
            ->assertOk()
            ->assertSee('Первый материал')
            ->assertSee('Материал 1 из 3')
            ->assertSee('Список материалов')
            ->assertSee('Следующий')
            ->assertSee(route('student.courses.material', [$course, $second]));

        $this->actingAs($student)->get(route('student.courses.material', [$course, $second]))
            ->assertOk()
            ->assertSee('Материал 2 из 3')
            ->assertSee(route('student.courses.material', [$course, $first]))
            ->assertSee(route('student.courses.material', [$course, $third]));

        $this->actingAs($student)->get(route('student.courses.material', [$course, $third]))
            ->assertOk()->assertSee('Промежуточный тест')
            ->assertSee('Вернуться к содержанию');

        $this->actingAs($student)
            ->get(route('student.courses.material', [$course, $section]))->assertNotFound();
        $this->actingAs($student)
            ->get(route('student.courses.material', [$course, $hidden]))->assertNotFound();
    }

    public function test_an_unenrolled_student_cannot_open_materials(): void
    {
        $student = $this->actor('Student');
        $course = $this->course();
        $lesson = $course->modules()->create(['title' => 'Защита', 'type' => 'lesson']);

        $this->actingAs($student)->get(route('student.courses.show', $course))->assertForbidden();
        $this->actingAs($student)->get(route('student.courses.material', [$course, $lesson]))
            ->assertForbidden();
    }

    public function test_manager_can_preview_draft_material_without_enrollment(): void
    {
        $manager = $this->actor('Manager');
        $course = $this->course('draft', false);
        $lesson = $course->modules()->create([
            'title' => 'Неопубликованный урок', 'type' => 'lesson',
            'is_active' => false, 'content' => '<p>Черновой текст</p>',
        ]);

        $this->actingAs($manager)->get(route('admin.courses.preview', $course))
            ->assertOk()->assertSee('Предпросмотр');
        $this->actingAs($manager)->get(route('admin.courses.preview.material', [$course, $lesson]))
            ->assertOk()->assertSee('Черновой текст')->assertSee('Предпросмотр');
    }

    public function test_editor_has_full_page_with_attachment_picker_and_large_textarea(): void
    {
        $manager = $this->actor('Manager');
        $course = $this->course();

        $this->actingAs($manager)->get(route('admin.courses.modules.create', $course))
            ->assertOk()
            ->assertSee('course-attachment-manager')
            ->assertSee('editorAttachmentsDialog')
            ->assertSee('course-rich-editor')
            ->assertSee('rows="30"', false)
            ->assertSee('tinymce.min.js');

        $this->actingAs($manager)->get(route('admin.courses.show', $course))
            ->assertOk()
            ->assertSee(route('admin.courses.modules.create', $course));
    }

    public function test_uploaded_image_in_draft_is_claimed_and_only_available_to_enrolled_student(): void
    {
        Storage::fake('local');
        $manager = $this->actor('Manager');
        $student = $this->actor('Student');
        $stranger = $this->actor('Student');
        $course = $this->course();
        $this->assign($course, $student);

        $draftToken = (string) Str::uuid();
        $response = $this->actingAs($manager)->postJson(
            route('admin.courses.attachments.store', $course), [
                'draft_token' => $draftToken,
                'file' => UploadedFile::fake()->image('photo.png', 60, 40),
            ]);
        $response->assertCreated()
            ->assertJsonPath('name', 'photo.png')
            ->assertJsonPath('image', true);
        $id = $response->json('id');
        $attachment = CourseAttachment::findOrFail($id);
        $this->assertNull($attachment->module_id);
        Storage::disk('local')->assertExists($attachment->storage_path);

        $this->actingAs($student)->get(route('course.attachments.view', [$course, $attachment]))
            ->assertForbidden();

        $this->actingAs($manager)->post(route('admin.courses.modules.store', $course), [
            'title' => 'Инструкция с картинкой',
            'type' => 'lesson',
            'draft_token' => $draftToken,
            'content' => '<h2>Проверка СИЗ</h2>'
                . '<p><img src="' . route('course.attachments.view', [$course, $attachment]) . '" alt="СИЗ"></p>',
        ])->assertRedirect();

        $lesson = $course->modules()->firstOrFail();
        $attachment->refresh();
        $this->assertSame($lesson->id, $attachment->module_id);
        $this->assertNull($attachment->draft_token);

        $this->actingAs($student)
            ->get(route('student.courses.material', [$course, $lesson]))
            ->assertOk()->assertSee('Проверка СИЗ');

        $this->actingAs($student)->get(route('course.attachments.view', [$course, $attachment]))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($student)->get(route('course.attachments.download', [$course, $attachment]))
            ->assertOk();

        $this->actingAs($stranger)->get(route('course.attachments.view', [$course, $attachment]))
            ->assertForbidden();
        $another = $this->course('different');
        $this->actingAs($student)->get(route('course.attachments.view', [$another, $attachment]))
            ->assertNotFound();
    }

    public function test_manager_can_list_and_remove_attachments(): void
    {
        Storage::fake('local');
        $manager = $this->actor('Manager');
        $course = $this->course();
        $lesson = $course->modules()->create(['title' => 'Документы', 'type' => 'lesson']);

        $upload = $this->actingAs($manager)->postJson(
            route('admin.courses.attachments.store', $course), [
                'module_id' => $lesson->id,
                'file' => UploadedFile::fake()->image('schematic.png'),
            ]);
        $upload->assertCreated();
        $attachment = CourseAttachment::findOrFail($upload->json('id'));

        $this->actingAs($manager)->getJson(route('admin.courses.attachments.index', [
            'course' => $course, 'module_id' => $lesson->id,
        ]))->assertOk()->assertJsonCount(1, 'items');

        $this->actingAs($manager)->deleteJson(
            route('admin.courses.attachments.destroy', [$course, $attachment])
        )->assertOk();
        Storage::disk('local')->assertMissing($attachment->storage_path);
        $this->assertDatabaseCount('course_attachments', 0);
    }
}
