<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\TrainingEnrollment;
use App\Services\TrainingStudyProgress;
use Illuminate\Http\Request;

class TrainingProgressController extends Controller
{
    public function complete(Request $request, TrainingEnrollment $enrollment,
        Course $course, CourseModule $module, TrainingStudyProgress $progress)
    {
        $progress->mark($enrollment, $course, $module, $request->user());
        return back()->with('success', 'Материал отмечен как изученный для этого набора.');
    }
}
