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
use Illuminate\Support\Str;
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
            'content' => ['nullable', 'string', 'max:1000000'],
            'video_url' => ['nullable', 'url:http,https', 'max:2048'],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'test_id' => ['nullable', 'integer', Rule::exists('tests', 'id')->where('course_id', $course->id)],
            'course_package_id' => ['nullable', 'integer', Rule::exists('course_packages', 'id')
                ->where('format', $request->input('type'))],
            'package_upload' => ['nullable', 'file', 'max:102400'],
            'package_tags' => ['nullable', 'string', 'max:300'],

            'file' => ['nullable', 'file', 'max:51200',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,png,jpg,jpeg,webp,gif,mp4,webm'],
            'draft_token' => ['nullable', 'uuid'],
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
        if ($data['type'] === 'file' && !$request->hasFile('file')
            && (!$module || (!$module->file_path && !$module->attachments()->exists()))) {
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

    public function create(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        $course->load(['tests', 'modules']);
        $allowedTypes = self::TYPES;
        $initialType = in_array($request->query('type'), $allowedTypes, true)
            ? $request->query('type') : 'lesson';

        return view('admin.courses.material-editor', [
            'course' => $course,
            'item' => null,
            'draftToken' => (string) Str::uuid(),
            'initialType' => $initialType,
        ]);
    }

    public function edit(Course $course, CourseModule $module)
    {
        Gate::authorize('update', $course);
        abort_unless($module->course_id === $course->id, 404);
        $course->load(['tests', 'modules']);

        return view('admin.courses.material-editor', [
            'course' => $course,
            'item' => $module,
            'draftToken' => (string) Str::uuid(),
            'initialType' => $module->type,
        ]);
    }

    private function claimDrafts(Request $request, Course $course, CourseModule $module): void
    {
        if (!$request->filled('draft_token')) {
            return;
        }

        $course->attachments()
            ->whereNull('module_id')
            ->where('uploaded_by', $request->user()->id)
            ->where('draft_token', $request->input('draft_token'))
            ->update(['module_id' => $module->id, 'draft_token' => null]);
    }

    private function attachPrimaryFile(Request $request, Course $course, CourseModule $module): void
    {
        if (!$request->hasFile('file')) {
            return;
        }

        $file = $request->file('file');
        $extension = strtolower((string) ($file->guessExtension() ?: $file->extension() ?: 'bin'));
        $path = $file->storeAs('course-attachments/' . $course->id,
            Str::uuid() . '.' . $extension, 'local');

        try {
            $course->attachments()->create([
                'module_id' => $module->id,
                'uploaded_by' => $request->user()->id,
                'storage_path' => $path,
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
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

        $module = CourseModule::create($data);
        $this->claimDrafts($request, $course, $module);
        $this->attachPrimaryFile($request, $course, $module);

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
        $module->update($data);
        $this->claimDrafts($request, $course, $module);
        $this->attachPrimaryFile($request, $course, $module);

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

        $moduleIds = array_merge([$module->id], $module->children()->pluck('id')->all());
        $files = $course->attachments()->whereIn('module_id', $moduleIds)
            ->pluck('storage_path')->all();
        $module->delete();

        if ($files) {
            Storage::disk('local')->delete($files);
        }

        return to_route('admin.courses.show', $course)->with('success', 'Материал удалён.');
    }
}
