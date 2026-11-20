<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Services\CourseOutline;
use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function generateThumbnail($imagePath, $width = 400, $height = 300)
    {
        $manager = new ImageManager(new Driver());
        $fullPath = storage_path('app/public/' . $imagePath);
        $image = $manager->read($fullPath);
        
        // Пропорциональное уменьшение с crop по центру
        $image->cover($width, $height, 'center');
        
        // Сохраняем как _thumb версию
        $thumbPath = str_replace('.', '_thumb.', $imagePath);
        $image->save(storage_path('app/public/' . $thumbPath));
        
        return $thumbPath;
    }

    private function deleteThumbnails($path)
    {
        if (!$path) return;
        
        $thumbPath = str_replace('.', '_thumb.', $path);
        Storage::disk('public')->delete($thumbPath);
        Storage::disk('public')->delete($path);
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Course::class);
        
        $query = Course::with(['category', 'creator'])->withCount(['modules', 'tests', 'packages']);
        
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
        
        $courses = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
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
            'type' => 'nullable|in:standard,scorm,h5p',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:min_width=400,min_height=300',
            'duration_minutes' => 'nullable|integer',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'has_certificate' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'thumbnail.dimensions' => 'Изображение должно быть не менее 400x300 пикселей. Ваш размер: :width x :height px',
            'thumbnail.mimes' => 'Допустимые форматы: JPEG, PNG, WebP',
            'thumbnail.max' => 'Максимальный размер файла: 10MB',
        ]);
        
        $slug = trim((string) ($validated['slug'] ?? ''));
        $base = Str::slug($slug ?: $validated['title']) ?: 'course-' . Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        $validated['slug'] = $slug;
        $validated['type'] = 'standard';
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active');
        $validated['has_certificate'] = $request->boolean('has_certificate');
        
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            $validated['thumbnail'] = $this->generateThumbnail($path, 400, 300);
        }
        
        $course = Course::create($validated);
        
        return redirect()->route('admin.courses.show', $course)->with('success', 'Курс успешно создан.');
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);
        
        $course->load(['category', 'creator', 'modules' => fn ($query) => $query
            ->with(['children' => fn ($child) => $child->orderBy('sort_order'), 'test', 'coursePackage', 'attachments'])
            ->orderBy('sort_order'), 'tests', 'packages']);
        
        return view('admin.courses.show', compact('course'));
    }

    public function preview(Course $course, CourseOutline $outliner)
    {
        Gate::authorize('view', $course);
        $course->load(['category']);
        $outline = $outliner->entries($course, true);
        $steps = $outliner->steps($outline);

        return view('student.courses.show', [
            'course' => $course,
            'outline' => $outline,
            'steps' => $steps,
            'preview' => true,
        ]);
    }

    public function previewMaterial(Course $course, CourseModule $module, CourseOutline $outliner)
    {
        Gate::authorize('view', $course);
        abort_unless($module->course_id === $course->id && $module->type !== 'section', 404);

        $outline = $outliner->entries($course, true);
        $steps = $outliner->steps($outline);
        $index = $steps->search(fn ($entry) => $entry['item']->id === $module->id);
        abort_if($index === false, 404);

        return view('student.courses.material', [
            'course' => $course,
            'module' => $steps[$index]['item'],
            'outline' => $outline,
            'steps' => $steps,
            'index' => $index,
            'previous' => $steps->get($index - 1),
            'next' => $steps->get($index + 1),
            'preview' => true,
        ]);
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
            'type' => 'nullable|in:standard,scorm,h5p',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240|dimensions:min_width=400,min_height=300',
            'duration_minutes' => 'nullable|integer',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'has_certificate' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'thumbnail.dimensions' => 'Изображение должно быть не менее 400x300 пикселей. Ваш размер: :width x :height px',
            'thumbnail.mimes' => 'Допустимые форматы: JPEG, PNG, WebP',
            'thumbnail.max' => 'Максимальный размер файла: 10MB',
        ]);
        
        $validated['slug'] = trim((string) ($validated['slug'] ?? '')) ?: $course->slug;
        $validated['type'] = $course->type; // Preserve legacy course type without exposing it.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['has_certificate'] = $request->boolean('has_certificate');
        
        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) $this->deleteThumbnails($course->thumbnail);
            $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            $validated['thumbnail'] = $this->generateThumbnail($path, 400, 300);
        }
        
        $course->update($validated);
        
        return redirect()->route('admin.courses.show', $course)->with('success', 'Курс успешно обновлён.');
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);
        
        $this->deleteThumbnails($course->thumbnail);
        
        $course->delete();
        
        return redirect()->route('admin.courses.index')->with('success', 'Курс успешно удалён.');
    }
}
