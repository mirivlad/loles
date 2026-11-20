<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Получаем назначения для пользователя
        $query = CourseAssignment::with(['course', 'group', 'organization'])
            ->where('is_active', true)
            ->where(function($q) use ($user) {
                // Индивидуальные назначения
                $q->where('type', 'individual')
                  ->where('user_id', $user->id);
                
                // Или назначения группе пользователя
                if ($user->groups->count() > 0) {
                    $q->orWhere(function($sub) use ($user) {
                        $sub->where('type', 'group')
                            ->whereIn('group_id', $user->groups->pluck('id'));
                    });
                }
                
                // Или назначения организации пользователя
                if ($user->organization_id) {
                    $q->orWhere(function($sub) use ($user) {
                        $sub->where('type', 'organization')
                            ->where('organization_id', $user->organization_id);
                    });
                }
            });
        
        $assignments = $query->get();
        
        // Группируем по курсам
        $courses = $assignments->unique('course_id')->map(function($assignment) {
            return $assignment->course;
        });
        
        return view('student.courses.index', compact('courses', 'assignments'));
    }

    public function show(Course $course)
    {
        abort_unless($course->is_active, 404);
        $user = Auth::user();
        
        // Проверяем доступ к курсу
        $hasAccess = CourseAssignment::where('course_id', $course->id)
            ->where('is_active', true)
            ->where(function($q) use ($user) {
                $q->where('type', 'individual')->where('user_id', $user->id)
                  ->orWhere(function($sub) use ($user) {
                      $sub->where('type', 'group')
                          ->whereIn('group_id', $user->groups->pluck('id'));
                  })
                  ->orWhere(function($sub) use ($user) {
                      $sub->where('type', 'organization')
                          ->where('organization_id', $user->organization_id);
                  });
            })->exists();
        
        if (!$hasAccess) {
            abort(403, 'У вас нет доступа к этому курсу');
        }
        
        $course->load([
            'tests',
            'category',
            'modules' => fn ($query) => $query
                ->with(['children' => fn ($children) => $children->orderBy('sort_order'), 'test'])
                ->orderBy('sort_order'),
            'packages',
        ]);
        
        return view('student.courses.show', compact('course'));
    }
}
