<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingIntake;
use App\Models\TrainingEnrollment;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingIntakeController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TrainingIntake::class);
        $filter = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'open', 'closed', 'training', 'completed', 'archived'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $intakes = TrainingIntake::query()->with('program')
            ->withCount(['applications', 'enrollments'])
            ->when($filter['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filter['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%' .
                str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], trim($term)) . '%'))
            ->orderByDesc('starts_on')->paginate(20)->withQueryString();
        return view('admin.training.intakes.index', compact('intakes'));
    }

    public function create()
    {
        Gate::authorize('create', TrainingIntake::class);
        return view('admin.training.intakes.form', [
            'intake' => new TrainingIntake(['required_progress_percent' => 100, 'status' => 'draft']),
            'programs' => TrainingProgram::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', TrainingIntake::class);
        $data = $this->validated($request, true);
        $program = TrainingProgram::with('courses')->findOrFail($data['training_program_id']);
        if (!$program->is_active || $program->courses->isEmpty()) {
            throw ValidationException::withMessages(['training_program_id' => 'Выберите действующую программу с курсами.']);
        }
        $data['created_by'] = $request->user()->id;
        $intake = DB::transaction(function () use ($data, $program) {
            $intake = TrainingIntake::create($data);
            foreach ($program->courses as $course) {
                $intake->courses()->create(['course_id' => $course->course_id, 'sort_order' => $course->sort_order]);
            }
            return $intake;
        });
        return redirect()->route('admin.training.intakes.show', $intake)
            ->with('success', 'Набор создан. Состав курсов зафиксирован.');
    }

    public function show(TrainingIntake $intake)
    {
        Gate::authorize('view', $intake);
        $intake->load(['program', 'courses.course', 'groups', 'exams.test.course']);
        $intake->loadCount(['enrollments', 'applications']);
        $applications = $intake->applications()->with(['organization', 'students'])
            ->latest()->paginate(20, ['*'], 'applications_page');
        $enrollments = $intake->enrollments()->with(['user', 'organization', 'group'])
            ->orderBy('id')->paginate(20, ['*'], 'students_page');
        return view('admin.training.intakes.show', compact('intake', 'applications', 'enrollments'));
    }

    public function edit(TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        return view('admin.training.intakes.form', [
            'intake' => $intake, 'programs' => TrainingProgram::whereKey($intake->training_program_id)->get(),
        ]);
    }

    public function update(Request $request, TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $data = $this->validated($request, false);
        DB::transaction(function () use ($intake, $data) {
            $locked = TrainingIntake::lockForUpdate()->findOrFail($intake->id);
            $transitions = [
                'draft' => ['draft', 'open'],
                'open' => ['open', 'closed', 'training'],
                'closed' => ['closed', 'open', 'training'],
                'training' => ['training', 'completed'],
            ];
            if (!in_array($data['status'], $transitions[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Недопустимый переход состояния набора.']);
            }
            if ((int) $data['capacity'] < $locked->enrollments()->where('status', 'enrolled')->count()) {
                throw ValidationException::withMessages(['capacity' => 'Вместимость меньше числа уже зачисленных учащихся.']);
            }
            // This is a curriculum snapshot: changing the source program is never allowed.
            $locked->update($data);
        });
        return redirect()->route('admin.training.intakes.show', $intake)->with('success', 'Набор обновлён.');
    }

    public function addGroup(Request $request, TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('training_intake_groups')->where('training_intake_id', $intake->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $intake->groups()->create($data);
        return back()->with('success', 'Учебная группа создана.');
    }

    public function archive(TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        $updated = TrainingIntake::whereKey($intake->id)->where('status', 'completed')
            ->update(['status' => 'archived']);
        abort_unless($updated === 1, 409, 'В архив можно отправить только завершённый набор.');
        return back()->with('success', 'Набор перенесён в архив с сохранением истории.');
    }

    public function updateEnrollmentGroup(Request $request, TrainingIntake $intake, TrainingEnrollment $enrollment)
    {
        Gate::authorize('update', $intake);
        abort_unless($enrollment->training_intake_id === $intake->id, 404);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $data = $request->validate(['training_intake_group_id' => ['nullable', 'integer']]);
        $groupId = $data['training_intake_group_id'] ?? null;
        if ($groupId !== null && !$intake->groups()->whereKey($groupId)->exists()) {
            throw ValidationException::withMessages(['training_intake_group_id' => 'Группа не принадлежит этому набору.']);
        }
        $enrollment->update(['training_intake_group_id' => $groupId]);
        return back()->with('success', 'Учебная группа учащегося изменена.');
    }

    private function validated(Request $request, bool $create): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'application_deadline' => ['required', 'date', 'before_or_equal:starts_on'],
            'starts_on' => ['required', 'date', 'before_or_equal:ends_on'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'required_progress_percent' => ['required', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'training', 'completed', 'archived'])],
        ];
        if ($create) {
            $rules['training_program_id'] = ['required', Rule::exists('training_programs', 'id')];
        }
        return $request->validate($rules);
    }
}
