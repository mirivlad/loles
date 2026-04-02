<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\Course;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class CourseModuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:course_modules,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:section,lesson,video,file,link,test',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'file_path' => 'nullable|string',
            'external_url' => 'nullable|url',
            'test_id' => 'nullable|exists:tests,id',
            'sort_order' => 'nullable|integer',
            'duration_minutes' => 'nullable|integer',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $validated['course_id'] = $course->id;
        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_active'] = $request->boolean('is_active');
        
        // Загрузка файла
        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('course-files', 'public');
        }
        
        CourseModule::create($validated);
        
        return back()->with('success', 'Модуль добавлен');
    }

    public function update(Request $request, Course $course, CourseModule $module)
    {
        Gate::authorize('update', $course);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:section,lesson,video,file,link,test',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'external_url' => 'nullable|url',
            'test_id' => 'nullable|exists:tests,id',
            'sort_order' => 'nullable|integer',
            'duration_minutes' => 'nullable|integer',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_active'] = $request->boolean('is_active');
        
        // Загрузка нового файла
        if ($request->hasFile('file')) {
            // Удаляем старый файл
            if ($module->file_path) {
                Storage::disk('public')->delete($module->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('course-files', 'public');
        }
        
        $module->update($validated);
        
        return back()->with('success', 'Модуль обновлён');
    }

    public function destroy(Course $course, CourseModule $module)
    {
        Gate::authorize('update', $course);
        
        // Удаляем файл если есть
        if ($module->file_path) {
            Storage::disk('public')->delete($module->file_path);
        }
        
        $module->delete();
        
        return back()->with('success', 'Модуль удалён');
    }
}
