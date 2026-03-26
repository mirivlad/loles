<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Course::class);
        
        $query = Course::with(['category', 'creator']);
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        $courses = $query->orderBy('created_at', 'desc')->paginate(20);
        $categories = CourseCategory::pluck('name', 'id');
        
        return view('admin.courses.index', compact('courses', 'categories'));
    }

    public function create()
    {
        Gate::authorize('create', Course::class);
        
        $categories = CourseCategory::pluck('name', 'id');
        
        return view('admin.courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Course::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'type' => 'required|in:standard,scorm,h5p',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:min_width=400,min_height=300',
            'duration_minutes' => 'nullable|integer',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'has_certificate' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'thumbnail.dimensions' => 'Изображение должно быть не менее 400x300 пикселей. Ваш размер: :width x :height px',
            'thumbnail.mimes' => 'Допустимые форматы: JPEG, PNG, WebP',
            'thumbnail.max' => 'Максимальный размер файла: 2MB',
        ]);
        
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active');
        $validated['has_certificate'] = $request->boolean('has_certificate');
        
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }
        
        $course = Course::create($validated);
        
        return redirect()->route('admin.courses.show', $course)->with('success', 'Курс успешно создан.');
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);
        
        $course->load(['category', 'creator', 'modules', 'tests']);
        
        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        Gate::authorize('update', $course);
        
        $categories = CourseCategory::pluck('name', 'id');
        
        return view('admin.courses.edit', compact('course', 'categories'));
    }

    public function update(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug,' . $course->id,
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'category_id' => 'nullable|exists:course_categories,id',
            'type' => 'required|in:standard,scorm,h5p',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:min_width=400,min_height=300',
            'duration_minutes' => 'nullable|integer',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'has_certificate' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'thumbnail.dimensions' => 'Изображение должно быть не менее 400x300 пикселей. Ваш размер: :width x :height px',
            'thumbnail.mimes' => 'Допустимые форматы: JPEG, PNG, WebP',
            'thumbnail.max' => 'Максимальный размер файла: 2MB',
        ]);
        
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['has_certificate'] = $request->boolean('has_certificate');
        
        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) Storage::disk('public')->delete($course->thumbnail);
            $validated['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
        }
        
        $course->update($validated);
        
        return redirect()->route('admin.courses.show', $course)->with('success', 'Курс успешно обновлён.');
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);
        
        if ($course->thumbnail) Storage::disk('public')->delete($course->thumbnail);
        
        $course->delete();
        
        return redirect()->route('admin.courses.index')->with('success', 'Курс успешно удалён.');
    }
}
