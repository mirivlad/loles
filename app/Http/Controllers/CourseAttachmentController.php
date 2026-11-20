<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseModule;
use App\Services\CourseAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseAttachmentController extends Controller
{
    private function selected(Request $request, Course $course)
    {
        $data = $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:course_modules,id'],
            'draft_token' => ['nullable', 'uuid'],
        ]);

        $module = !empty($data['module_id'])
            ? CourseModule::where('course_id', $course->id)->findOrFail($data['module_id'])
            : null;

        if (!$module && empty($data['draft_token'])) {
            throw ValidationException::withMessages([
                'draft_token' => 'Не найден идентификатор черновика.',
            ]);
        }

        return [$module, $data['draft_token'] ?? null];
    }

    private function details(Course $course, CourseAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'mime' => $attachment->mime_type,
            'size' => $attachment->size_bytes,
            'image' => $attachment->isImage(),
            'url' => $attachment->isImage()
                ? route('course.attachments.view', [$course, $attachment])
                : route('course.attachments.download', [$course, $attachment]),
            'download_url' => route('course.attachments.download', [$course, $attachment]),
        ];
    }

    public function index(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        [$module, $token] = $this->selected($request, $course);

        $query = $course->attachments()->orderBy('id');
        if ($module) {
            $query->where('module_id', $module->id);
        } else {
            $query->whereNull('module_id')
                ->where('draft_token', $token)
                ->where('uploaded_by', $request->user()->id);
        }

        return response()->json([
            'items' => $query->get()->map(fn ($file) => $this->details($course, $file))->all(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        $request->validate([
            'file' => ['required', 'file', 'max:51200',
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp4,webm,zip'],
        ]);
        [$module, $token] = $this->selected($request, $course);

        $query = $course->attachments();
        if ($module) {
            $query->where('module_id', $module->id);
        } else {
            $query->whereNull('module_id')->where('draft_token', $token)
                ->where('uploaded_by', $request->user()->id);
        }
        if ((clone $query)->count() >= 80) {
            throw ValidationException::withMessages([
                'file' => 'Максимум 80 вложений на один материал.',
            ]);
        }

        $file = $request->file('file');
        $extension = strtolower((string) ($file->guessExtension() ?: $file->extension() ?: 'bin'));
        $path = $file->storeAs('course-attachments/' . $course->id,
            Str::uuid() . '.' . $extension, 'local');

        try {
            $attachment = $course->attachments()->create([
                'module_id' => $module?->id,
                'uploaded_by' => $request->user()->id,
                'draft_token' => $module ? null : $token,
                'storage_path' => $path,
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return response()->json($this->details($course, $attachment), 201)
            ->header('Cache-Control', 'private, no-store');
    }

    private function checkPermission(Request $request, Course $course, CourseAttachment $attachment, CourseAccess $access): void
    {
        abort_unless($attachment->course_id === $course->id, 404);

        if (!$attachment->module_id) {
            abort_unless($request->user()->id === $attachment->uploaded_by
                && $request->user()->hasRole(['Administrator', 'Manager', 'Curator']), 403);
            return;
        }

        $module = $attachment->module;
        abort_unless($module && $module->course_id === $course->id, 404);

        if ($request->user()->hasRole(['Administrator', 'Manager', 'Curator'])) {
            return;
        }

        abort_unless($module->is_active, 404);
        if ($module->parent_id) {
            abort_unless($module->parent && $module->parent->is_active, 404);
        }
        $access->authorizeStudy($course, $request->user());
    }

    public function view(Request $request, Course $course, CourseAttachment $attachment, CourseAccess $access)
    {
        $this->checkPermission($request, $course, $attachment, $access);
        abort_unless($attachment->isImage(), 404);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->response($attachment->storage_path, null, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    public function download(Request $request, Course $course, CourseAttachment $attachment, CourseAccess $access)
    {
        $this->checkPermission($request, $course, $attachment, $access);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, Course $course, CourseAttachment $attachment)
    {
        Gate::authorize('update', $course);
        abort_unless($attachment->course_id === $course->id, 404);

        $path = $attachment->storage_path;
        $attachment->delete();
        Storage::disk('local')->delete($path);

        return response()->json(['deleted' => true]);
    }
}
