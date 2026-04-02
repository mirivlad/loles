<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Получаем назначенные курсы с датами
        $courseAssignments = CourseAssignment::with(['course', 'group', 'organization'])
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
            });
        
        // Фильтры
        if ($request->filled('filter')) {
            if ($request->filter === 'today') {
                $courseAssignments->whereDate('start_date', today());
            } elseif ($request->filter === 'week') {
                $courseAssignments->whereBetween('start_date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($request->filter === 'month') {
                $courseAssignments->whereMonth('start_date', now()->month);
            }
        }
        
        $assignments = $courseAssignments->orderBy('start_date')->get();
        
        // Получаем тесты с дедлайнами
        $courseIds = $assignments->pluck('course_id');
        $tests = Test::with(['course'])
            ->whereIn('course_id', $courseIds)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();
        
        // Группируем по датам для календаря
        $schedule = $this->buildSchedule($assignments, $tests);
        
        // Статистика
        $stats = [
            'courses_count' => $assignments->unique('course_id')->count(),
            'tests_count' => $tests->count(),
            'upcoming_tests' => $tests->where('created_at', '>=', now())->count(),
        ];
        
        return view('student.schedule.index', compact('schedule', 'stats', 'tests'));
    }

    public function calendar()
    {
        $user = Auth::user();
        
        // Получаем все назначения для календаря
        $assignments = CourseAssignment::with(['course'])
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
            })
            ->get();
        
        // Формируем события для календаря
        $events = [];
        
        foreach ($assignments as $assignment) {
            $events[] = [
                'title' => $assignment->course->title,
                'start' => $assignment->start_date->format('Y-m-d'),
                'end' => $assignment->end_date?->format('Y-m-d'),
                'color' => '#0d6efd',
                'url' => route('student.courses.show', $assignment->course),
            ];
        }
        
        return view('student.schedule.calendar', compact('events'));
    }

    private function buildSchedule($assignments, $tests)
    {
        $schedule = [];
        
        // Добавляем курсы
        foreach ($assignments as $assignment) {
            $date = $assignment->start_date->format('Y-m-d');
            if (!isset($schedule[$date])) {
                $schedule[$date] = [];
            }
            
            $schedule[$date][] = [
                'type' => 'course',
                'title' => $assignment->course->title,
                'description' => 'Начало курса',
                'color' => 'primary',
                'icon' => 'bi-book',
                'url' => route('student.courses.show', $assignment->course),
            ];
        }
        
        // Добавляем тесты
        foreach ($tests as $test) {
            $date = $test->created_at->format('Y-m-d');
            if (!isset($schedule[$date])) {
                $schedule[$date] = [];
            }
            
            $schedule[$date][] = [
                'type' => 'test',
                'title' => $test->title,
                'description' => $test->course->title,
                'color' => 'success',
                'icon' => 'bi-file-earmark-text',
                'url' => route('student.tests.show', $test),
            ];
        }
        
        // Сортируем по датам
        ksort($schedule);
        
        return $schedule;
    }
}
