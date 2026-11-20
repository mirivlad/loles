<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Services\CourseAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(CourseAccess $access)
    {
        $courseIds = $access->accessibleCourseIds(Auth::user());

        $tests = Test::with('course')
            ->whereIn('course_id', $courseIds)
            ->where('is_active', true)
            ->get();

        return view('student.tests.index', compact('tests'));
    }

    public function show(Test $test, CourseAccess $access)
    {
        abort_unless($test->is_active, 404);
        $test->load('course');
        $access->authorizeStudy($test->course, Auth::user());
        return view('student.tests.show', compact('test'));
    }
}
