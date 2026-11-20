<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseLibrary
{
    public function normalizeTags(?string $tags): array
    {
        $names = preg_split('/[,;\n]+/u', (string) $tags);
        $normalized = [];
        foreach ($names as $name) {
            $tag = mb_substr(trim(preg_replace('/\s+/u', ' ', $name)), 0, 48);
            if ($tag !== '' && !in_array($tag, $normalized, true)) {
                $normalized[] = $tag;
            }
            if (count($normalized) >= 12) break;
        }
        return $normalized;
    }

    public function upload(UploadedFile $upload, string $format, ?string $tags, Course $source, User $actor): CoursePackage
    {
        $hash = hash_file('sha256', $upload->getRealPath());

        $existing = CoursePackage::where('format', $format)->where('sha256', $hash)->first();
        if ($existing && Storage::disk('local')->exists($existing->storage_path)) {
            $mergedTags = array_values(array_unique(array_merge($existing->tags ?? [], $this->normalizeTags($tags))));
            $mergedTags = array_slice($mergedTags, 0, 12);
            $existing->update(['tags' => $mergedTags, 'tags_search' => implode(' ', $mergedTags)]);
            return $existing;
        }

        $inspection = app(CoursePackageInspector::class)->inspect($upload->getRealPath(), $format);
        $filename = (string) Str::uuid() . ($format === 'h5p' ? '.h5p' : '.zip');
        $path = $upload->storeAs('course-packages/library', $filename, 'local');

        try {
            return DB::transaction(fn () => CoursePackage::create([
                'course_id' => $source->id,
                'uploaded_by' => $actor->id,
                'format' => $format,
                'standard_version' => $inspection['standard_version'],
                'title' => $inspection['title'],
                'original_filename' => mb_substr(basename($upload->getClientOriginalName()), 0, 255),
                'storage_path' => $path,
                'sha256' => $hash,
                'size_bytes' => $upload->getSize(),
                'tags' => $this->normalizeTags($tags),
                'tags_search' => implode(' ', $this->normalizeTags($tags)),
                'metadata' => $inspection['metadata'],
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
