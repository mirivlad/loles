<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CoursePackage;
use App\Services\CourseLibrary;
use App\Services\LearningHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourseModuleController extends Controller
{
    private const TYPES = ['section', 'lesson', 'video', 'file', 'link', 'test', 'scorm', 'h5p'];

    private function validateItem(Request $request, Course $course, ?CourseModule $module = null): array
    {
        $types = self::TYPES;
        if ($module && !in_array($module->type, $types, true)) {
            // Preserve previously imported SCORM/H5P legacy lesson records.
            $types[] = $module->type;
        }

        $data = $request->validate([
            'title' => [in_array($request->input('type'), ['scorm', 'h5p'], true) ? 'nullable' : 'required', 'string', 'max:255'],
            'type' => ['required', Rule::in($types)],
            'parent_id' => ['nullable', 'integer',
                Rule::exists('course_modules', 'id')->where('course_id', $course->id)->where('type', 'section')],
            'content' => ['nullable', 'string', 'max:150000'],
            'video_url' => ['nullable', 'url:http,https', 'max:2048'],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'test_id' => ['nullable', 'integer', Rule::exists('tests', 'id')->where('course_id', $course->id)],
            'course_package_id' => ['nullable', 'integer', Rule::exists('course_packages', 'id')
                ->where('format', $request->input('type'))],
            'package_upload' => ['nullable', 'file', 'max:102400'],
            'package_tags' => ['nullable', 'string', 'max:300'],

            'file' => ['nullable', 'file', 'max:102400',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,png,jpg,jpeg,webp,mp4,webm'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:50000'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['type'] === 'section' && !empty($data['parent_id'])) {
            throw ValidationException::withMessages(['parent_id' => 'Раздел нельзя вложить в другой раздел.']);
        }
        if ($module && (int) ($data['parent_id'] ?? 0) === $module->id) {
            throw ValidationException::withMessages(['parent_id' => 'Материал не может быть собственным родителем.']);
        }

        if ($data['type'] === 'video' && empty($data['video_url'])) {
            throw ValidationException::withMessages(['video_url' => 'Укажите адрес видео.']);
        }
        if ($data['type'] === 'link' && empty($data['external_url'])) {
            throw ValidationException::withMessages(['external_url' => 'Укажите адрес страницы.']);
        }
        if ($data['type'] === 'test' && empty($data['test_id'])) {
            throw ValidationException::withMessages(['test_id' => 'Выберите тест курса.']);
        }
        if ($data['type'] === 'file' && !$request->hasFile('file') && !$module?->file_path) {
            throw ValidationException::withMessages(['file' => 'Прикрепите файл.']);
        }

        if (in_array($data['type'], ['scorm', 'h5p'], true)
            && !$request->hasFile('package_upload')
            && empty($data['course_package_id'])
            && (!$module || $module->type !== $data['type'] || !$module->course_package_id)) {
            throw ValidationException::withMessages(['course_package_id' =>
                'Выберите материал из библиотеки или загрузите новый пакет.']);
        }

        return $data;
    }

    private function applyPackage(Request $request, Course $course, array &$data, CourseLibrary $library, ?CourseModule $module = null): void
    {
        $format = $data['type'];
        if (in_array($format, ['scorm', 'h5p'], true)) {
            $package = $request->hasFile('package_upload')
                ? $library->upload($request->file('package_upload'), $format,
                    $data['package_tags'] ?? null, $course, $request->user())
                : (isset($data['course_package_id'])
                    ? CoursePackage::where('format', $format)->findOrFail($data['course_package_id'])
                    : $module?->coursePackage);

            if (!$package || $package->format !== $format) {
                throw ValidationException::withMessages(['course_package_id' => 'Выберите подходящий пакет.']);
            }
            $data['course_package_id'] = $package->id;
            $data['title'] = trim((string) ($data['title'] ?? '')) ?: ($package->title ?: $package->original_filename);
        } else {
            $data['course_package_id'] = null;
        }

        if ($format === 'lesson') {
            $data['content'] = app(LearningHtml::class)->clean($data['content'] ?? '');
        }
        unset($data['package_upload'], $data['package_tags']);
    }

    public function store(Request $request, Course $course, CourseLibrary $library)
    {
        Gate::authorize('update', $course);
        $data = $this->validateItem($request, $course);
        $this->applyPackage($request, $course, $data, $library);

        $data['course_id'] = $course->id;
        $data['is_required'] = $request->boolean('is_required', true);
        $data['is_active'] = $request->boolean('is_active', true);

        unset($data['file']);
        $parentId = $data['parent_id'] ?? null;
        $data['sort_order'] = (int) $course->modules()
            ->where('parent_id', $parentId)->max('sort_order') + 10;

        $file = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file')->store('course-files/' . $course->id, 'public');
            $data['file_path'] = $file;
        }

        try {
            CourseModule::create($data);
        } catch (\Throwable $exception) {
            if ($file) Storage::disk('public')->delete($file);
            throw $exception;
        }

        return to_route('admin.courses.show', $course)->with('success', 'Материал добавлен в курс.');
    }

    public function update(Request $request, Course $course, CourseModule $module, CourseLibrary $library)
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);
        $data = $this->validateItem($request, $course, $module);
        $this->applyPackage($request, $course, $data, $library, $module);

        // Do not allow turning a parent section into a plain lesson while it has children.
        if ($module->children()->exists() && $data['type'] !== 'section') {
            throw ValidationException::withMessages(['type' =>
                'Сначала переместите материалы из раздела, затем измените его тип.']);
        }
        unset($data['file']);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');
        $original = $module->file_path;

        $uploaded = null;
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file')->store('course-files/' . $course->id, 'public');
            $data['file_path'] = $uploaded;
        }

        try {
            $module->update($data);
        } catch (\Throwable $exception) {
            if ($uploaded) Storage::disk('public')->delete($uploaded);
            throw $exception;
        }

        if ($uploaded && $original) Storage::disk('public')->delete($original);

        return to_route('admin.courses.show', $course)->with('success', 'Материал обновлён.');
    }

    public function move(Request $request, Course $course, CourseModule $module)
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);

        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        DB::transaction(function () use ($course, $module, $data) {
            $siblings = $course->modules()->where('parent_id', $module->parent_id)
                ->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();

            $position = $siblings->search(fn ($entry) => $entry->id === $module->id);
            if ($position === false) return;

            $target = $position + ($data['direction'] === 'up' ? -1 : 1);
            if ($target < 0 || $target >= $siblings->count()) return;

            $ids = $siblings->pluck('id')->all();
            [$ids[$position], $ids[$target]] = [$ids[$target], $ids[$position]];

            foreach ($ids as $index => $id) {
                CourseModule::whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        return to_route('admin.courses.show', $course);
    }

    public function destroy(Course $course, CourseModule $module)
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);

        $files = $module->children()->whereNotNull('file_path')->pluck('file_path')->all();
        if ($module->file_path) $files[] = $module->file_path;
        $module->delete();

        if ($files) Storage::disk('public')->delete($files);

        return to_route('admin.courses.show', $course)->with('success', 'Материал удалён.');
    }
}
