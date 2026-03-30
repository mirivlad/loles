<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\User;
use App\Models\Group;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class CourseAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', CourseAssignment::class);
        
        $query = CourseAssignment::with(['course', 'user', 'group', 'organization', 'creator']);
        
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        $assignments = $query->orderBy('created_at', 'desc')->paginate(20);
        $courses = Course::pluck('title', 'id');
        
        return view('admin.course-assignments.index', compact('assignments', 'courses'));
    }

    public function create()
    {
        Gate::authorize('create', CourseAssignment::class);
        
        $courses = Course::pluck('title', 'id');
        $users = User::pluck('name', 'id');
        $groups = Group::pluck('name', 'id');
        $organizations = Organization::pluck('name', 'id');
        
        return view('admin.course-assignments.create', compact('courses', 'users', 'groups', 'organizations'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CourseAssignment::class);
        
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'type' => 'nullable|in:individual,group,organization',
            'user_ids' => 'nullable|string',
            'group_ids' => 'nullable|string',
            'organization_ids' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active');
        
        // Определяем тип назначения по выбранным элементам
        if (empty($validated['type'])) {
            if (!empty($validated['user_ids'])) {
                $validated['type'] = 'individual';
            } elseif (!empty($validated['group_ids'])) {
                $validated['type'] = 'group';
            } elseif (!empty($validated['organization_ids'])) {
                $validated['type'] = 'organization';
            }
        }
        
        // Создаём назначения для каждого выбранного элемента
        $created = 0;
        
        // Назначения пользователям
        if (!empty($validated['user_ids'])) {
            $userIds = array_map('intval', array_filter(explode(',', $validated['user_ids'])));
            foreach ($userIds as $userId) {
                CourseAssignment::create([
                    'course_id' => $validated['course_id'],
                    'user_id' => $userId,
                    'type' => 'individual',
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'created_by' => $validated['created_by'],
                    'is_active' => $validated['is_active'],
                ]);
                $created++;
            }
        }
        
        // Назначения группам
        if (!empty($validated['group_ids'])) {
            $groupIds = array_map('intval', array_filter(explode(',', $validated['group_ids'])));
            foreach ($groupIds as $groupId) {
                CourseAssignment::create([
                    'course_id' => $validated['course_id'],
                    'group_id' => $groupId,
                    'type' => 'group',
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'created_by' => $validated['created_by'],
                    'is_active' => $validated['is_active'],
                ]);
                $created++;
            }
        }
        
        // Назначения организациям
        if (!empty($validated['organization_ids'])) {
            $organizationIds = array_map('intval', array_filter(explode(',', $validated['organization_ids'])));
            foreach ($organizationIds as $organizationId) {
                CourseAssignment::create([
                    'course_id' => $validated['course_id'],
                    'organization_id' => $organizationId,
                    'type' => 'organization',
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'created_by' => $validated['created_by'],
                    'is_active' => $validated['is_active'],
                ]);
                $created++;
            }
        }
        
        return redirect()->route('admin.course-assignments.index')
            ->with('success', "Создано назначений: {$created}");
    }

    public function show(CourseAssignment $assignment)
    {
        Gate::authorize('view', $assignment);
        
        $assignment->load(['course', 'user', 'group', 'organization', 'creator']);
        
        return view('admin.course-assignments.show', compact('assignment'));
    }

    public function edit(CourseAssignment $assignment)
    {
        Gate::authorize('update', $assignment);
        
        $courses = Course::pluck('title', 'id');
        
        return view('admin.course-assignments.edit', compact('assignment', 'courses'));
    }

    public function update(Request $request, CourseAssignment $assignment)
    {
        Gate::authorize('update', $assignment);
        
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $assignment->update($validated);
        
        return redirect()->route('admin.course-assignments.show', $assignment)
            ->with('success', 'Назначение успешно обновлено.');
    }

    public function destroy(CourseAssignment $assignment)
    {
        Gate::authorize('delete', $assignment);
        
        $assignment->delete();
        
        return redirect()->route('admin.course-assignments.index')
            ->with('success', 'Назначение успешно удалено.');
    }
}
