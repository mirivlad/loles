<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\CourseAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Получаем тесты из доступных курсов
        $courseIds = CourseAssignment::where('is_active', true)
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
            })->pluck('course_id');
        
        $tests = Test::with(['course'])
            ->whereIn('course_id', $courseIds)
            ->where('is_active', true)
            ->get();
        
        return view('student.tests.index', compact('tests'));
    }

    public function show(Test $test)
    {
        $user = Auth::user();
        
        // Проверяем доступ к тесту через курс
        $hasAccess = CourseAssignment::where('course_id', $test->course_id)
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
            abort(403, 'У вас нет доступа к этому тесту');
        }
        
        // НЕ загружаем вопросы и ответы - только общую информацию
        $test->load(['course']);
        
        return view('student.tests.show', compact('test'));
    }
}
