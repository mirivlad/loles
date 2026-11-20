<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingExamRule;
use App\Models\TrainingIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingExamController extends Controller
{
    public function create(TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $tests = Test::with('course')->where('is_active', true)
            ->whereIn('course_id', $intake->courses()->select('course_id'))
            ->orderBy('title')->get();
        return view('admin.training.exams.form', [
            'intake' => $intake, 'exam' => new TrainingExamRule([
                'kind' => 'official', 'pass_mode' => 'allowed_errors',
                'allowed_errors' => 0, 'max_attempts' => 1,
                'time_limit_minutes' => 30, 'is_required' => true,
                'opens_on' => $intake->starts_on, 'closes_on' => $intake->ends_on,
            ]),
            'tests' => $tests,
        ]);
    }

    public function store(Request $request, TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $data = $this->validated($request, $intake);
        if ($intake->exams()->where('test_id', $data['test_id'])->where('kind', $data['kind'])->exists()) {
            throw ValidationException::withMessages(['test_id' => 'Для этого теста уже настроен такой вид экзамена.']);
        }
        $data['created_by'] = $request->user()->id;
        $exam = $intake->exams()->create($data);
        return redirect()->route('admin.training.exams.show', [$intake, $exam])
            ->with('success', 'Правило экзамена создано.');
    }

    public function show(TrainingIntake $intake, TrainingExamRule $exam)
    {
        Gate::authorize('view', $intake);
        $this->verifyParent($intake, $exam);
        $exam->load(['test.course']);
        $attempts = $exam->attempts()->with(['user', 'voider'])->latest('id')->paginate(30);
        return view('admin.training.exams.show', compact('intake', 'exam', 'attempts'));
    }

    public function edit(TrainingIntake $intake, TrainingExamRule $exam)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $exam);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        abort_if($exam->attempts()->exists(), 409, 'Нельзя редактировать правила после запуска первой попытки.');
        $tests = Test::with('course')->whereKey($exam->test_id)->get();
        return view('admin.training.exams.form', compact('intake', 'exam', 'tests'));
    }

    public function update(Request $request, TrainingIntake $intake, TrainingExamRule $exam)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $exam);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $data = $this->validated($request, $intake);
        DB::transaction(function () use ($exam, $data) {
            $locked = TrainingExamRule::lockForUpdate()->findOrFail($exam->id);
            if ($locked->attempts()->exists()) {
                throw ValidationException::withMessages(['exam' => 'После первой попытки правила нельзя менять.']);
            }
            $locked->update($data);
        });
        return redirect()->route('admin.training.exams.show', [$intake, $exam])
            ->with('success', 'Правило обновлено.');
    }

    public function toggle(Request $request, TrainingIntake $intake, TrainingExamRule $exam)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $exam);
        abort_if(in_array($intake->status, ['completed', 'archived'], true), 409);
        $data = $request->validate(['is_enabled' => ['required', 'boolean']]);
        $exam->update(['is_enabled' => $data['is_enabled']]);
        return back()->with('success', $exam->is_enabled ? 'Экзамен открыт по расписанию.' : 'Начало новых попыток приостановлено.');
    }

    public function attempt(TrainingIntake $intake, TrainingExamRule $exam,
        TrainingExamAttempt $attempt)
    {
        Gate::authorize('view', $intake);
        $this->verifyParent($intake, $exam);
        abort_unless($attempt->training_exam_rule_id === $exam->id, 404);
        $attempt->load(['user', 'voider', 'questions']);
        $exam->load('test');
        return view('admin.training.exams.attempt', compact('intake', 'exam', 'attempt'));
    }

    public function void(Request $request, TrainingIntake $intake,
        TrainingExamRule $exam, TrainingExamAttempt $attempt)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $exam);
        abort_unless($attempt->training_exam_rule_id === $exam->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        DB::transaction(function () use ($attempt, $request, $data) {
            $locked = TrainingExamAttempt::lockForUpdate()->findOrFail($attempt->id);
            if (!in_array($locked->status, ['submitted', 'expired'], true) || $locked->voided_at) {
                throw ValidationException::withMessages(['reason' => 'Можно аннулировать только закрытую действующую попытку.']);
            }
            // Original score and answers remain intact for the audit trail.
            $locked->update([
                'voided_at' => now(), 'voided_by' => $request->user()->id,
                'void_reason' => $data['reason'],
            ]);
        });
        return back()->with('success', 'Попытка аннулирована. Оригинальные ответы и оценка сохранены.');
    }

    private function verifyParent(TrainingIntake $intake, TrainingExamRule $exam): void
    {
        abort_unless($exam->training_intake_id === $intake->id, 404);
    }

    private function validated(Request $request, TrainingIntake $intake): array
    {
        $data = $request->validate([
            'test_id' => ['required', 'integer', 'exists:tests,id'],
            'kind' => ['required', Rule::in(['trial', 'official'])],
            'pass_mode' => ['required', Rule::in(['allowed_errors', 'percent'])],
            'allowed_errors' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'passing_percent' => ['nullable', 'integer', 'between:0,100'],
            'opens_on' => ['required', 'date', 'before_or_equal:closes_on'],
            'closes_on' => ['required', 'date', 'after_or_equal:opens_on'],
            'time_limit_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'is_required' => ['required', 'boolean'],
        ]);
        $test = Test::findOrFail($data['test_id']);
        if (!$test->is_active || !$intake->courses()->where('course_id', $test->course_id)->exists()) {
            throw ValidationException::withMessages(['test_id' => 'Тест должен относиться к действующему курсу этого набора.']);
        }
        if ($data['pass_mode'] === 'allowed_errors') {
            if (($data['allowed_errors'] ?? null) === null) {
                throw ValidationException::withMessages(['allowed_errors' => 'Укажите допустимое число ошибок.']);
            }
            $data['passing_percent'] = null;
        } else {
            if (($data['passing_percent'] ?? null) === null) {
                throw ValidationException::withMessages(['passing_percent' => 'Укажите проходной процент.']);
            }
            $data['allowed_errors'] = null;
        }
        if ($data['kind'] === 'trial') {
            $data['is_required'] = false;
        }
        if ($data['opens_on'] < $intake->starts_on->toDateString()
            || $data['closes_on'] > $intake->ends_on->toDateString()) {
            throw ValidationException::withMessages(['opens_on' => 'Экзамен должен проходить в период обучения.']);
        }
        return $data;
    }
}
