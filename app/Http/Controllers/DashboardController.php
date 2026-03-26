<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\UserCourseProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('Administrator') || $user->hasRole('Manager')) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('Curator')) {
            return $this->curatorDashboard();
        }

        return $this->studentDashboard();
    }

    private function adminDashboard()
    {
        $stats = [
            'users_count' => \App\Models\User::count(),
            'courses_count' => Course::count(),
            'organizations_count' => \App\Models\Organization::count(),
            'active_assignments' => CourseAssignment::where('is_active', true)->count(),
        ];

        return view('dashboard.admin', compact('stats'));
    }

    private function curatorDashboard()
    {
        $user = Auth::user();
        $organization = $user->organization;

        $stats = [
            'users_count' => $organization ? $organization->users()->count() : 0,
            'courses_count' => Course::count(),
            'groups_count' => $organization ? $organization->groups()->count() : 0,
        ];

        return view('dashboard.curator', compact('stats', 'organization'));
    }

    private function studentDashboard()
    {
        $user = Auth::user();
        
        // Получаем назначенные курсы
        $assignments = CourseAssignment::where('user_id', $user->id)
            ->orWhere('organization_id', $user->organization_id)
            ->orWhereIn('group_id', $user->groups->pluck('id'))
            ->where('is_active', true)
            ->with('course')
            ->get();

        $courses = $assignments->pluck('course')->unique('id');

        // Прогресс по курсам
        $progress = [];
        foreach ($courses as $course) {
            $userProgress = UserCourseProgress::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            $progress[$course->id] = $userProgress ? $userProgress->completion_percentage : 0;
        }

        return view('dashboard.student', compact('courses', 'progress'));
    }
}
