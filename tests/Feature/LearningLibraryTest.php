<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\User;
use App\Services\LearningHtml;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class LearningLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    private function course(string $slug): Course
    {
        return Course::create(['title' => $slug, 'slug' => $slug, 'is_active' => true]);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Manager');
        return $user;
    }

    private function packageFile(string $format): array
    {
        $path = tempnam(sys_get_temp_dir(), 'loles-gallery-');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        if ($format === 'scorm') {
            $zip->addFromString('imsmanifest.xml', '<?xml version="1.0"?>'
                . '<manifest identifier="test"><metadata><schema>ADL SCORM</schema>'
                . '<schemaversion>1.2</schemaversion></metadata>'
                . '<organizations><organization identifier="o"><title>Охрана труда</title>'
                . '</organization></organizations>'
                . '<resources><resource identifier="r" type="webcontent" href="index.html"/>'
                . '</resources></manifest>');
            $zip->addFromString('index.html', '<html>test</html>');
        } else {
            $zip->addFromString('h5p.json', json_encode([
                'title' => 'Безопасность на вахте',
                'mainLibrary' => 'H5P.MultiChoice',
                'preloadedDependencies' => [['machineName' => 'H5P.MultiChoice', 'majorVersion' => 1, 'minorVersion' => 16]],
            ], JSON_UNESCAPED_UNICODE));
            $zip->addFromString('content/content.json', '{}');
        }
        $this->assertTrue($zip->close());

        return [$path, new UploadedFile($path,
            $format === 'scorm' ? 'training.zip' : 'training.h5p',
            'application/zip', null, true)];
    }

    public function test_editor_keeps_rich_formatting_but_sanitizes_script_and_javascript_urls(): void
    {
        $user = $this->manager();
        $course = $this->course('rich-html-course');

        $this->actingAs($user)->post(route('admin.courses.modules.store', $course), [
            'title' => 'Инструкция',
            'type' => 'lesson',
            'content' => '<h2>Охрана труда</h2><p><strong>Важно</strong>'
                . '<script>alert(1)</script><a href="javascript:alert(1)">Danger</a>'
                . '<table><tr><td>Пункт 1</td></tr></table></p>',
        ])->assertRedirect();

        $item = CourseModule::firstOrFail();
        $this->assertStringContainsString('<strong>', $item->content);
        $this->assertStringContainsString('<table', $item->content);
        $this->assertStringNotContainsString('<script', $item->content);
        $this->assertStringNotContainsString('javascript:', $item->content);

        $this->actingAs($user)->get(route('admin.courses.preview.material', [$course, $item]))
            ->assertOk()->assertSee('Охрана труда');
        $this->actingAs($user)->get(route('admin.courses.modules.create', $course))
            ->assertOk()->assertSee('tinymce.min.js')->assertSee('course-library-root');
    }

    public function test_plain_text_legacy_lesson_preserves_line_breaks_and_escapes_html(): void
    {
        $content = app(LearningHtml::class)->clean("Первая строка\nВторая < ненадёжная");
        $this->assertStringContainsString('<br', $content);
        $this->assertStringContainsString('&lt;', $content);
    }

    public function test_scorm_uploaded_as_material_is_found_by_title_and_tags_in_another_course(): void
    {
        $manager = $this->manager();
        $first = $this->course('north-course');
        $second = $this->course('south-course');
        [$path, $upload] = $this->packageFile('scorm');
        $hash = hash_file('sha256', $path);

        try {
            $this->actingAs($manager)->post(route('admin.courses.modules.store', $first), [
                'type' => 'scorm',
                'title' => '',
                'package_upload' => $upload,
                'package_tags' => 'охрана труда, Север, вахта',
            ])->assertRedirect();

            $package = CoursePackage::firstOrFail();
            $this->assertSame(['охрана труда', 'Север', 'вахта'], $package->tags);
            $this->assertSame('SCORM 1.2', $package->standard_version);
            $this->assertSame($hash, $package->sha256);
            $this->assertSame($package->id, $first->modules()->firstOrFail()->course_package_id);

            $search = $this->actingAs($manager)->getJson(route('admin.courses.library.search', [
                'course' => $second, 'format' => 'scorm', 'q' => 'Сев',
            ]));
            $search->assertOk()
                ->assertJsonCount(1, 'items')
                ->assertJsonPath('items.0.title', 'Охрана труда');

            $this->actingAs($manager)->post(route('admin.courses.modules.store', $second), [
                'type' => 'scorm',
                'course_package_id' => $package->id,
            ])->assertRedirect();

            $this->assertSame($package->id, $second->modules()->firstOrFail()->course_package_id);
            $this->assertDatabaseCount('course_packages', 1);

            $file = $this->actingAs($manager)->get(route('admin.courses.library.download', [$second, $package]));
            $file->assertOk();
            $this->assertSame($hash, hash('sha256', $file->streamedContent()));

            $first->delete();
            $this->assertDatabaseHas('course_packages', ['id' => $package->id]);
            Storage::disk('local')->assertExists($package->storage_path);

            $this->actingAs($manager)
                ->get(route('admin.courses.library.download', [$second, $package]))->assertOk();
        } finally {
            @unlink($path);
        }
    }

    public function test_h5p_upload_and_library_filter_are_separate_from_scorm(): void
    {
        $user = $this->manager();
        $course = $this->course('h5p-example');
        [$path, $upload] = $this->packageFile('h5p');

        try {
            $this->actingAs($user)->post(route('admin.courses.modules.store', $course), [
                'title' => '',
                'type' => 'h5p',
                'package_upload' => $upload,
                'package_tags' => 'проверка знаний, безопасность',
            ])->assertRedirect();

            $package = CoursePackage::firstOrFail();
            $this->assertSame('h5p', $package->format);
            $this->assertSame('Безопасность на вахте', $course->modules()->firstOrFail()->title);

            $this->actingAs($user)->getJson(route('admin.courses.library.search', [
                'course' => $course, 'format' => 'h5p', 'q' => 'проверка',
            ]))->assertOk()->assertJsonCount(1, 'items');

            $this->actingAs($user)->getJson(route('admin.courses.library.search', [
                'course' => $course, 'format' => 'scorm', 'q' => 'проверка',
            ]))->assertOk()->assertJsonCount(0, 'items');
        } finally {
            @unlink($path);
        }
    }

    public function test_package_library_can_only_be_accessed_by_course_editors(): void
    {
        $course = $this->course('protected-library');
        $student = User::factory()->create();
        $student->assignRole('Student');
        $this->actingAs($student)->getJson(route('admin.courses.library.search', [
            'course' => $course, 'format' => 'h5p',
        ]))->assertForbidden();
    }

    public function test_material_package_type_must_match_library_type(): void
    {
        $user = $this->manager();
        $course = $this->course('wrong-package');
        [$path, $upload] = $this->packageFile('scorm');
        try {
            $this->actingAs($user)->post(route('admin.courses.modules.store', $course), [
                'type' => 'scorm', 'package_upload' => $upload,
            ])->assertRedirect();
            $package = CoursePackage::firstOrFail();

            $this->actingAs($user)->post(route('admin.courses.modules.store', $course), [
                'type' => 'h5p', 'course_package_id' => $package->id,
            ])->assertSessionHasErrors('course_package_id');
        } finally {
            @unlink($path);
        }
    }
}
