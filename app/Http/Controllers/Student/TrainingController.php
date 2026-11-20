<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TrainingEnrollment;
use App\Services\TrainingOutcomeService;
use App\Services\CourseAccess;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index(Request $request, TrainingOutcomeService $service)
    {
        $enrollments = TrainingEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->with(['intake.program', 'intake.courses.course', 'intake.exams.test', 'group'])
            ->orderByDesc('enrolled_at')
            ->paginate(15);
        // Intake rules and curricula differ; evaluate a student's enrollments
        // in their respective intakes rather than merging unlike programs.
        $outcomes = collect();
        foreach ($enrollments->getCollection()->groupBy('training_intake_id') as $group) {
            $groupOutcomes = $service->summarize($group->first()->intake, $group);
            $outcomes = $outcomes->union($groupOutcomes);
        }
        return view('student.trainings.index', compact('enrollments', 'outcomes'));
    }
}
