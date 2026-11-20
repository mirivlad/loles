<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TrainingEnrollment;
use App\Services\CourseAccess;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $enrollments = TrainingEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->with(['intake.program', 'intake.courses.course', 'group'])
            ->orderByDesc('enrolled_at')
            ->paginate(15);
        return view('student.trainings.index', compact('enrollments'));
    }
}
