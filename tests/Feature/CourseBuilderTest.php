<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class CourseBuilderTest extends TestCase
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

    private function course(): Course
    {
        return Course::create([
            'title' => 'Тестовое обучение',
            'slug' => 'test-course-' . bin2hex(random_bytes(4)),
            'is_active' => true,
        ]);
    }

    private function archive(array $files, string $extension = '.zip'): array
    {
        $path = tempnam(sys_get_temp_dir(), 'loles-course-test-') . $extension;
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $filename => $contents) {
            $zip->addFromString($filename, $contents);
        }
        $this->assertTrue($zip->close());

        return [$path, new UploadedFile($path, 'training' . $extension, 'application/zip', null, true)];
    }

    private function sampleScorm(): array
    {
        return [
            'imsmanifest.xml' => '<?xml version="1.0" encoding="UTF-8"?>'
                . '<manifest identifier="course-test">'
                . '<metadata><schema>ADL SCORM</schema><schemaversion>1.2</schemaversion></metadata>'
                . '<organizations default="ORG1"><organization identifier="ORG1">'
                . '<title>Вводный курс</title><item identifier="I1" identifierref="R1"><title>Введение</title></item>'
                . '</organization></organizations>'
                . '<resources><resource identifier="R1" type="webcontent" href="index.html"/></resources>'
                . '</manifest>',
            'index.html' => '<html><body>Training</body></html>',
        ];
    }

    public function test_manager_can_create_simple_draft_course_without_technical_type(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('admin.courses.store'), [
            'title' => 'Охрана труда',
            'description' => 'Основы безопасной работы',
        ])->assertRedirect();

        $course = Course::where('title', 'Охрана труда')->firstOrFail();
        $this->assertSame('standard', $course->type);
        $this->assertFalse($course->is_active);

        $this->actingAs($manager)->get(route('admin.courses.show', $course))
            ->assertOk()->assertSee('Содержание курса');
        $this->actingAs($manager)->get(route('admin.courses.preview', $course))
            ->assertOk()->assertSee('Предпросмотр');
    }

    public function test_manager_can_add_video_and_section_and_reorder_without_numeric_sort_fields(): void
    {
        $manager = $this->manager();
        $course = $this->course();

        $this->actingAs($manager)->post(route('admin.courses.modules.store', $course), [
            'title' => 'Видеоурок',
            'type' => 'video',
            'video_url' => 'https://example.org/video',
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('admin.courses.modules.store', $course), [
            'title' => 'Вводный раздел',
            'type' => 'section',
        ])->assertRedirect();

        $video = $course->modules()->where('type', 'video')->firstOrFail();
        $section = $course->modules()->where('type', 'section')->firstOrFail();
        $this->assertSame('https://example.org/video', $video->video_url);
        $this->assertTrue($video->sort_order < $section->sort_order);

        $this->actingAs($manager)->post(route('admin.courses.modules.move', [$course, $section]), [
            'direction' => 'up',
        ])->assertRedirect();

        $this->assertTrue($section->fresh()->sort_order < $video->fresh()->sort_order);
    }

    public function test_cannot_modify_another_courses_material_or_attach_foreign_section(): void
    {
        $manager = $this->manager();
        $first = $this->course();
        $second = $this->course();
        $section = $first->modules()->create(['title' => 'Раздел', 'type' => 'section']);
        $item = $first->modules()->create(['title' => 'Урок', 'type' => 'lesson']);

        $this->actingAs($manager)->put(route('admin.courses.modules.update', [$second, $item]), [
            'title' => 'Взлом',
            'type' => 'lesson',
        ])->assertNotFound();

        $this->actingAs($manager)->post(route('admin.courses.modules.store', $second), [
            'title' => 'Чужая вложенность',
            'type' => 'lesson',
            'parent_id' => $section->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_scorm_import_and_download_preserves_identical_original_bytes(): void
    {
        Storage::fake('local');
        $manager = $this->manager();
        $course = $this->course();
        [$path, $upload] = $this->archive($this->sampleScorm());
        $expectedHash = hash_file('sha256', $path);

        try {
            $this->actingAs($manager)->post(route('admin.courses.packages.store', $course), [
                'format' => 'scorm',
                'package' => $upload,
            ])->assertRedirect(route('admin.courses.show', $course));

            $package = CoursePackage::firstOrFail();
            $this->assertSame('SCORM 1.2', $package->standard_version);
            $this->assertSame('Вводный курс', $package->title);
            $this->assertSame($expectedHash, $package->sha256);
            Storage::disk('local')->assertExists($package->storage_path);

            $response = $this->actingAs($manager)->get(
                route('admin.courses.packages.download', [$course, $package])
            );
            $response->assertOk();
            $this->assertSame($expectedHash, hash('sha256', $response->streamedContent()));
        } finally {
            @unlink($path);
        }
    }

    public function test_h5p_import_keeps_original_for_export(): void
    {
        Storage::fake('local');
        $manager = $this->manager();
        $course = $this->course();
        [$path, $upload] = $this->archive([
            'h5p.json' => json_encode([
                'title' => 'Тренировка',
                'mainLibrary' => 'H5P.MultiChoice',
                'preloadedDependencies' => [['machineName' => 'H5P.MultiChoice', 'majorVersion' => 1, 'minorVersion' => 16]],
            ], JSON_UNESCAPED_UNICODE),
            'content/content.json' => '{}',
        ], '.h5p');
        try {
            $this->actingAs($manager)->post(route('admin.courses.packages.store', $course), [
                'format' => 'h5p',
                'package' => $upload,
            ])->assertRedirect();

            $package = CoursePackage::firstOrFail();
            $this->assertSame('h5p', $package->format);
            $this->assertSame('Тренировка', $package->title);
            $this->assertSame(hash_file('sha256', $path), hash_file('sha256', Storage::disk('local')->path($package->storage_path)));
        } finally {
            @unlink($path);
        }
    }

    public function test_zip_path_traversal_is_blocked(): void
    {
        Storage::fake('local');
        $manager = $this->manager();
        $course = $this->course();
        [$path, $upload] = $this->archive(array_merge($this->sampleScorm(), [
            '../../public/evil.html' => '<script>alert(1)</script>',
        ]));
        try {
            $this->actingAs($manager)->post(route('admin.courses.packages.store', $course), [
                'format' => 'scorm',
                'package' => $upload,
            ])->assertSessionHasErrors('package');

            $this->assertDatabaseCount('course_packages', 0);
        } finally {
            @unlink($path);
        }
    }

    public function test_student_cannot_import_or_download_scorm_package(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $student->assignRole('Student');
        $course = $this->course();
        [$path, $upload] = $this->archive($this->sampleScorm());
        try {
            $this->actingAs($student)->post(route('admin.courses.packages.store', $course), [
                'format' => 'scorm',
                'package' => $upload,
            ])->assertForbidden();
        } finally {
            @unlink($path);
        }
    }
}
