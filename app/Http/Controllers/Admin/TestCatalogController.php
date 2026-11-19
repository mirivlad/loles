<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TestCatalogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Test::class);

        $search = trim(mb_substr((string) $request->query('search', ''), 0, 150));
        $courseId = $request->integer('course_id');
        $status = (string) $request->query('status', '');

        $tests = Test::query()
            ->with('course')
            ->withCount('questions')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($courseId > 0, fn ($query) => $query->where('course_id', $courseId))
            ->when(in_array($status, ['active', 'inactive'], true),
                fn ($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $courses = Course::query()->orderBy('title')->pluck('title', 'id');
        $totalTests = Test::count();
        $activeTests = Test::where('is_active', true)->count();

        return view('admin.tests.catalog',
            compact('tests', 'courses', 'totalTests', 'activeTests'));
    }
}
