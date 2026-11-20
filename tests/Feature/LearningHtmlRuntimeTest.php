<?php

namespace Tests\Feature;

use App\Services\LearningHtml;
use Tests\TestCase;

class LearningHtmlRuntimeTest extends TestCase
{
    public function test_rich_lesson_can_render_without_writing_to_vendor_directory(): void
    {
        $service = app(LearningHtml::class);
        $result = $service->clean('<h2>Инструкция</h2><p><strong>Текст</strong>'
            . '<a href="https://example.org/file.pdf">Скачать PDF</a></p>');

        $this->assertStringContainsString('<strong>Текст</strong>', $result);
        $this->assertStringContainsString('https://example.org/file.pdf', $result);
        $this->assertDirectoryExists(storage_path('framework/cache/htmlpurifier'));
    }

    public function test_untrusted_script_is_removed_from_formatted_lesson(): void
    {
        $result = app(LearningHtml::class)->clean('<p>Учебный текст</p><script>alert(1)</script>');
        $this->assertStringContainsString('Учебный текст', $result);
        $this->assertStringNotContainsString('<script', $result);
    }
}
