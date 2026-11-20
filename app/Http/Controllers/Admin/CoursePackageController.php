<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Services\CoursePackageInspector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CoursePackageController extends Controller
{
    public function store(Request $request, Course $course, CoursePackageInspector $inspector)
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'format' => ['required', Rule::in(['scorm', 'h5p'])],
            'package' => ['required', 'file', 'max:102400'],
        ]);

        $upload = $request->file('package');
        $inspection = $inspector->inspect($upload->getRealPath(), $data['format']);
        $hash = hash_file('sha256', $upload->getRealPath());
        $filename = Str::uuid() . ($data['format'] === 'h5p' ? '.h5p' : '.zip');
        $path = 'course-packages/' . $course->id . '/' . $filename;

        // Private disk. Untrusted HTML and JS inside an archive must never be
        // served from the main application origin or extracted into /public.
        Storage::disk('local')->putFileAs('course-packages/' . $course->id, $upload, $filename);

        try {
            DB::transaction(function () use ($request, $course, $data, $inspection, $upload, $hash, $path) {
                $course->packages()->create([
                    'uploaded_by' => $request->user()->id,
                    'format' => $data['format'],
                    'standard_version' => $inspection['standard_version'],
                    'title' => $inspection['title'],
                    'original_filename' => mb_substr(basename($upload->getClientOriginalName()), 0, 255),
                    'storage_path' => $path,
                    'sha256' => $hash,
                    'size_bytes' => $upload->getSize(),
                    'metadata' => $inspection['metadata'],
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return to_route('admin.courses.show', $course)
            ->with('success', 'Пакет проверен и сохранён. Оригинал доступен для экспорта.');
    }

    public function download(Course $course, CoursePackage $package)
    {
        Gate::authorize('view', $course);
        abort_unless($package->course_id === $course->id, 404);
        abort_unless(Storage::disk('local')->exists($package->storage_path), 404);

        return Storage::disk('local')->download($package->storage_path, $package->original_filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Course $course, CoursePackage $package)
    {
        Gate::authorize('update', $course);
        abort_unless($package->course_id === $course->id, 404);

        // Packages are reusable. Never delete a referenced archive, even if
        // another course owns the original association.
        abort_if(\App\Models\CourseModule::where('course_package_id', $package->id)->exists(),
            409, 'Пакет уже используется учебными курсами. Сначала удалите соответствующие материалы.');

        $file = $package->storage_path;
        $package->delete();
        Storage::disk('local')->delete($file);

        return to_route('admin.courses.show', $course)->with('success', 'Пакет удалён.');
    }
}
