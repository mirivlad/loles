<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use App\Services\CourseAccess;
use App\Services\CourseOutline;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index(CourseAccess $access)
    {
        $assignments = $access->eligibleAssignments(Auth::user())
            ->with(['course.category'])
            ->orderByDesc('start_date')
            ->get();

        $courses = $assignments->pluck('course')->filter()->unique('id');

        return view('student.courses.index', compact('courses', 'assignments'));
    }

    public function show(Course $course, CourseAccess $access, CourseOutline $outliner)
    {
        $access->authorizeStudy($course, Auth::user());
        $course->load(['category']);
        $outline = $outliner->entries($course);
        $steps = $outliner->steps($outline);

        return view('student.courses.show', [
            'course' => $course,
            'outline' => $outline,
            'steps' => $steps,
            'preview' => false,
        ]);
    }

    public function material(Course $course, CourseModule $module, CourseAccess $access, CourseOutline $outliner)
    {
        $access->authorizeStudy($course, Auth::user());
        abort_unless($module->course_id === $course->id && $module->is_active
            && $module->type !== 'section', 404);

        $outline = $outliner->entries($course);
        $steps = $outliner->steps($outline);
        $index = $steps->search(fn ($entry) => $entry['item']->id === $module->id);
        abort_if($index === false, 404);

        $module = $steps[$index]['item'];
        $previous = $steps->get($index - 1);
        $next = $steps->get($index + 1);

        return view('student.courses.material', [
            'course' => $course,
            'module' => $module,
            'outline' => $outline,
            'steps' => $steps,
            'index' => $index,
            'previous' => $previous,
            'next' => $next,
            'preview' => false,
        ]);
    }
}
