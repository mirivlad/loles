<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Support\Collection;

class CourseOutline
{
    /**
     * Visible, sequential course outline. Sections are headings, never
     * standalone lesson pages. Their children occupy their own steps.
     *
     * @return Collection<int, array{kind: string, title: string, item: CourseModule|null}>
     */
    public function entries(Course $course, bool $includeHidden = false): Collection
    {
        $modules = $course->modules()
            ->with(['test', 'coursePackage', 'attachments'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $ordered = collect();

        $append = function (CourseModule $item) use (&$ordered, $includeHidden): void {
            if (!$includeHidden && !$item->is_active) {
                return;
            }

            $ordered->push([
                'kind' => $item->type === 'section' ? 'section' : 'material',
                'title' => $item->title,
                'item' => $item,
            ]);
        };

        foreach ($modules->whereNull('parent_id') as $item) {
            if (!$includeHidden && !$item->is_active) {
                continue;
            }
            $append($item);

            if ($item->type === 'section') {
                foreach ($modules->where('parent_id', $item->id) as $child) {
                    $append($child);
                }
            }
        }

        return $ordered->values();
    }

    public function steps(Collection $entries): Collection
    {
        return $entries->filter(fn ($entry) => $entry['kind'] === 'material')
            ->values();
    }
}
