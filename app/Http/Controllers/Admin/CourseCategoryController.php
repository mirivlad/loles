<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CourseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        Gate::authorize('viewAny', CourseCategory::class);
        
        $categories = CourseCategory::tree();
        
        return view('admin.course-categories.index', compact('categories'));
    }

    public function create()
    {
        Gate::authorize('create', CourseCategory::class);
        
        $parentCategories = CourseCategory::whereNull('parent_id')
            ->orderBy('name')
            ->get();
        
        return view('admin.course-categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CourseCategory::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:course_categories',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:course_categories,id',
            'sort_order' => 'integer',
        ]);
        
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        
        CourseCategory::create($validated);
        
        return redirect()->route('admin.course-categories.index')
            ->with('success', 'Категория успешно создана.');
    }

    public function show(CourseCategory $category)
    {
        Gate::authorize('view', $category);
        
        $category->load(['parent', 'children', 'courses']);
        
        return view('admin.course-categories.show', compact('category'));
    }

    public function edit(CourseCategory $category)
    {
        Gate::authorize('update', $category);
        
        $parentCategories = CourseCategory::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();
        
        return view('admin.course-categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, CourseCategory $category)
    {
        Gate::authorize('update', $category);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:course_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:course_categories,id',
            'sort_order' => 'integer',
        ]);
        
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        
        $category->update($validated);
        
        return redirect()->route('admin.course-categories.index')
            ->with('success', 'Категория успешно обновлена.');
    }

    public function destroy(CourseCategory $category)
    {
        Gate::authorize('delete', $category);
        
        if ($category->courses()->count() > 0) {
            return back()->with('error', 'Невозможно удалить категорию с курсами.');
        }
        
        if ($category->children()->count() > 0) {
            return back()->with('error', 'Невозможно удалить категорию с подкатегориями.');
        }
        
        $category->delete();
        
        return redirect()->route('admin.course-categories.index')
            ->with('success', 'Категория успешно удалена.');
    }
}
