<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CourseLibraryController extends Controller
{
    public function search(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'format' => ['required', Rule::in(['scorm', 'h5p'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = trim((string) ($data['q'] ?? ''));

        $entries = CoursePackage::query()
            ->where('format', $data['format'])
            ->when($query !== '', function ($builder) use ($query) {
                $words = preg_split('/\s+/u', $query);
                foreach ($words as $word) {
                    $word = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $word);
                    $builder->where(fn ($q) => $q
                        ->where('title', 'like', "%{$word}%")
                        ->orWhere('original_filename', 'like', "%{$word}%")
                        ->orWhere('tags_search', 'like', "%{$word}%"));
                }
            })
            ->orderBy('title')->limit(24)
            ->get(['id', 'title', 'original_filename', 'format', 'standard_version', 'tags', 'size_bytes']);

        return response()->json([
            'items' => $entries->map(fn ($entry) => [
                'id' => $entry->id,
                'title' => $entry->title ?: $entry->original_filename,
                'format' => $entry->format,
                'version' => $entry->standard_version,
                'tags' => $entry->tags ?: [],
                'size' => $entry->size_bytes,
            ])->all(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function download(Course $course, CoursePackage $package)
    {
        Gate::authorize('view', $course);
        // Never let somebody request a global file without this course referencing it.
        abort_unless($course->modules()->where('course_package_id', $package->id)->exists()
            || $package->course_id === $course->id, 404);
        abort_unless(Storage::disk('local')->exists($package->storage_path), 404);

        return Storage::disk('local')->download($package->storage_path, $package->original_filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
