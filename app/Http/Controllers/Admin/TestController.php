<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Course $course)
    {
        Gate::authorize('viewAny', Test::class);
        
        $tests = $course->tests()->withCount('questions')->orderBy('created_at', 'desc')->get();
        
        return view('admin.tests.index', compact('course', 'tests'));
    }

    public function create(Course $course)
    {
        Gate::authorize('create', Test::class);
        
        return view('admin.tests.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        Gate::authorize('create', Test::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:probationary,final,intermediate',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'shuffle_questions' => 'boolean',
            'show_correct_answers' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers');
        
        $course->tests()->create($validated);
        
        return redirect()->route('admin.courses.tests.index', $course)
            ->with('success', 'Тест успешно создан.');
    }

    public function show(Course $course, Test $test)
    {
        Gate::authorize('view', $test);
        
        $test->load(['questions.answers', 'questions.matchingPairs']);
        
        return view('admin.tests.show', compact('course', 'test'));
    }

    public function edit(Course $course, Test $test)
    {
        Gate::authorize('update', $test);
        
        return view('admin.tests.edit', compact('course', 'test'));
    }

    public function update(Request $request, Course $course, Test $test)
    {
        Gate::authorize('update', $test);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:probationary,final,intermediate',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'shuffle_questions' => 'boolean',
            'show_correct_answers' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers');
        
        $test->update($validated);
        
        return redirect()->route('admin.courses.tests.show', [$course, $test])
            ->with('success', 'Тест успешно обновлён.');
    }

    public function destroy(Course $course, Test $test)
    {
        Gate::authorize('delete', $test);
        
        $test->delete();
        
        return redirect()->route('admin.courses.tests.index', $course)
            ->with('success', 'Тест успешно удалён.');
    }
}
