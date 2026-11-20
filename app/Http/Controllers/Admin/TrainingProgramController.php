<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TrainingProgramController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TrainingProgram::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $programs = TrainingProgram::query()
            ->withCount(['courses', 'intakes'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%' .
                str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], trim($term)) . '%'))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.training.programs.index', compact('programs'));
    }

    public function create()
    {
        Gate::authorize('create', TrainingProgram::class);
        return view('admin.training.programs.form', ['program' => new TrainingProgram(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', TrainingProgram::class);
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $program = TrainingProgram::create($data);
        return redirect()->route('admin.training.programs.show', $program)
            ->with('success', 'Программа создана. Добавьте в неё курсы.');
    }

    public function show(TrainingProgram $program)
    {
        Gate::authorize('view', $program);
        $program->load(['courses.course']);
        $intakes = $program->intakes()->latest('starts_on')->paginate(10);
        return view('admin.training.programs.show', compact('program', 'intakes'));
    }

    public function edit(TrainingProgram $program)
    {
        Gate::authorize('update', $program);
        return view('admin.training.programs.form', compact('program'));
    }

    public function update(Request $request, TrainingProgram $program)
    {
        Gate::authorize('update', $program);
        $program->update($this->validated($request));
        return redirect()->route('admin.training.programs.show', $program)->with('success', 'Программа обновлена.');
    }

    public function addCourse(Request $request, TrainingProgram $program)
    {
        Gate::authorize('update', $program);
        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('is_active', true)],
        ]);
        if ($program->courses()->where('course_id', $validated['course_id'])->exists()) {
            return back()->withErrors(['course_id' => 'Этот курс уже добавлен.']);
        }
        $program->courses()->create([
            'course_id' => $validated['course_id'],
            'sort_order' => ((int) $program->courses()->max('sort_order')) + 1,
        ]);
        return back()->with('success', 'Курс добавлен в программу.');
    }

    public function removeCourse(TrainingProgram $program, Course $course)
    {
        Gate::authorize('update', $program);
        $program->courses()->where('course_id', $course->id)->delete();
        return back()->with('success', 'Курс удалён из программы. Уже созданные наборы не изменились.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
