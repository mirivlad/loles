<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TrainingExamAttempt;
use App\Models\TrainingExamRule;
use App\Models\TrainingEnrollment;
use App\Services\TrainingExamService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('Student'), 403);
        $enrollmentIds = TrainingEnrollment::where('user_id', $request->user()->id)
            ->where('status', 'enrolled')->select('training_intake_id');
        $exams = TrainingExamRule::with(['test.course', 'intake'])
            ->whereIn('training_intake_id', $enrollmentIds)
            ->orderBy('opens_on')->get();

        $attemptCounts = TrainingExamAttempt::where('user_id', $request->user()->id)
            ->whereIn('training_exam_rule_id', $exams->pluck('id'))
            ->whereNull('voided_at')->selectRaw('training_exam_rule_id, count(*) as total')
            ->groupBy('training_exam_rule_id')->pluck('total', 'training_exam_rule_id');

        return view('student.exams.index', compact('exams', 'attemptCounts'));
    }

    public function show(Request $request, TrainingExamRule $exam)
    {
        $this->authorizeEnrollment($request, $exam);
        $exam->load(['test', 'intake']);
        $attempts = $exam->attempts()->where('user_id', $request->user()->id)
            ->latest('id')->get();
        return view('student.exams.show', compact('exam', 'attempts'));
    }

    public function start(Request $request, TrainingExamRule $exam, TrainingExamService $service)
    {
        $this->authorizeEnrollment($request, $exam);
        $attempt = $service->start($exam, $request->user());
        return redirect()->route('student.exams.attempt.show', [$exam, $attempt]);
    }

    public function attempt(Request $request, TrainingExamRule $exam,
        TrainingExamAttempt $attempt, TrainingExamService $service)
    {
        $this->authorizeAttempt($request, $exam, $attempt);
        $attempt = $service->expireIfNeeded($attempt);
        $attempt->load(['questions', 'rule.test']);
        return view('student.exams.attempt', compact('exam', 'attempt'));
    }

    public function submit(Request $request, TrainingExamRule $exam,
        TrainingExamAttempt $attempt, TrainingExamService $service)
    {
        $this->authorizeAttempt($request, $exam, $attempt);
        $result = $service->submit($attempt, $request->user(), $request->input('answers', []));
        return redirect()->route('student.exams.attempt.show', [$exam, $result])
            ->with('success', $result->status === 'submitted'
                ? 'Ответы сохранены. Проверка завершена.'
                : 'Время попытки истекло. Ответы после окончания не принимаются.');
    }

    private function authorizeEnrollment(Request $request, TrainingExamRule $exam): void
    {
        abort_unless($request->user()->hasRole('Student')
            && TrainingEnrollment::where('training_intake_id', $exam->training_intake_id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'enrolled')->exists(), 403);
    }

    private function authorizeAttempt(Request $request, TrainingExamRule $exam,
        TrainingExamAttempt $attempt): void
    {
        abort_unless($attempt->training_exam_rule_id === $exam->id, 404);
        abort_unless($request->user()->hasRole('Student')
            && $attempt->user_id === $request->user()->id, 403);
    }
}
