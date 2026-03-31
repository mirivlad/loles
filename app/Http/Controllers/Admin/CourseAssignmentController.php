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
        
        $query = CourseAssignment::with(['course', 'user', 'group', 'organization']);
        
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        // Группируем по курсу и датам
        $assignments = $query->selectRaw('
                course_id, start_date, end_date,
                COUNT(CASE WHEN type = "individual" THEN 1 END) as individual_count,
                COUNT(CASE WHEN type = "group" THEN 1 END) as group_count,
                COUNT(CASE WHEN type = "organization" THEN 1 END) as organization_count,
                GROUP_CONCAT(CASE WHEN type = "individual" THEN id END) as individual_ids,
                GROUP_CONCAT(CASE WHEN type = "group" THEN id END) as group_ids,
                GROUP_CONCAT(CASE WHEN type = "organization" THEN id END) as organization_ids
            ')
            ->groupBy('course_id', 'start_date', 'end_date')
            ->orderBy('start_date', 'desc')
            ->paginate(20);
        
        $courses = Course::pluck('title', 'id');
        
        return view('admin.course-assignments.index', compact('assignments', 'courses'));
    }

    public function create()
    {
        Gate::authorize('create', CourseAssignment::class);
        
        return view('admin.course-assignments.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CourseAssignment::class);
        
        $validated = $request->validate([
            'course_ids' => 'nullable|string',
            'type' => 'nullable|in:individual,group,organization',
            'user_ids' => 'nullable|string',
            'group_ids' => 'nullable|string',
            'organization_ids' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'note' => 'nullable|string',
            'is_active' => 'boolean',
            'assignment_ids' => 'nullable|string',  // Для обновления существующих
        ]);
        
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active');
        
        // Если это обновление существующих назначений
        if (!empty($validated['assignment_ids'])) {
            $assignmentIds = array_map('intval', array_filter(explode(',', $validated['assignment_ids'])));
            
            CourseAssignment::whereIn('id', $assignmentIds)->update([
                'course_id' => !empty($validated['course_ids']) 
                    ? explode(',', $validated['course_ids'])[0] 
                    : null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
            
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Обновлено']);
            }
            
            return redirect()->back()->with('success', 'Назначение обновлено');
        }
        
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
        
        // Получаем списки ID
        $courseIds = !empty($validated['course_ids']) ? array_map('intval', array_filter(explode(',', $validated['course_ids']))) : [];
        $userIds = !empty($validated['user_ids']) ? array_map('intval', array_filter(explode(',', $validated['user_ids']))) : [];
        $groupIds = !empty($validated['group_ids']) ? array_map('intval', array_filter(explode(',', $validated['group_ids']))) : [];
        $organizationIds = !empty($validated['organization_ids']) ? array_map('intval', array_filter(explode(',', $validated['organization_ids']))) : [];
        
        // Создаём назначения для каждой комбинации
        $created = 0;
        $createdIds = [];
        
        // Назначения пользователям
        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                foreach ($courseIds as $courseId) {
                    $assignment = CourseAssignment::create([
                        'course_id' => $courseId,
                        'user_id' => $userId,
                        'type' => 'individual',
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'] ?? null,
                        'note' => $validated['note'] ?? null,
                        'created_by' => $validated['created_by'],
                        'is_active' => $validated['is_active'],
                    ]);
                    $createdIds[] = $assignment->id;
                    $created++;
                }
            }
        }
        
        // Назначения группам
        if (!empty($groupIds)) {
            foreach ($groupIds as $groupId) {
                foreach ($courseIds as $courseId) {
                    $assignment = CourseAssignment::create([
                        'course_id' => $courseId,
                        'group_id' => $groupId,
                        'type' => 'group',
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'] ?? null,
                        'note' => $validated['note'] ?? null,
                        'created_by' => $validated['created_by'],
                        'is_active' => $validated['is_active'],
                    ]);
                    $createdIds[] = $assignment->id;
                    $created++;
                }
            }
        }
        
        // Назначения организациям
        if (!empty($organizationIds)) {
            foreach ($organizationIds as $organizationId) {
                foreach ($courseIds as $courseId) {
                    $assignment = CourseAssignment::create([
                        'course_id' => $courseId,
                        'organization_id' => $organizationId,
                        'type' => 'organization',
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'] ?? null,
                        'note' => $validated['note'] ?? null,
                        'created_by' => $validated['created_by'],
                        'is_active' => $validated['is_active'],
                    ]);
                    $createdIds[] = $assignment->id;
                    $created++;
                }
            }
        }
        
        // Для AJAX запросов возвращаем JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Добавлено: {$created}",
                'ids' => $createdIds
            ]);
        }
        
        return redirect()->route('admin.course-assignments.index')
            ->with('success', "Создано назначений: {$created}");
    }

    public function show(Request $request, $course)
    {
        Gate::authorize('viewAny', CourseAssignment::class);
        
        $start_date = $request->get('start');
        $end_date = $request->get('end') !== 'null' ? $request->get('end') : null;
        
        $query = CourseAssignment::where('course_id', $course)
            ->where('start_date', $start_date);
        
        if ($end_date) {
            $query->where('end_date', $end_date);
        } else {
            $query->whereNull('end_date');
        }
        
        $assignments = $query->with(['course', 'user', 'group', 'organization', 'creator'])->get();
        $courseModel = $assignments->first()?->course ?? Course::find($course);
        
        // Проверяем можно ли редактировать (нет ли начатых тестов)
        // Пока всегда разрешаем - таблица test_results будет создана позже
        $canEditCourse = true;
        
        // Группируем по типам
        $individual = $assignments->where('type', 'individual');
        $groups = $assignments->where('type', 'group');
        $organizations = $assignments->where('type', 'organization');
        
        return view('admin.course-assignments.show', compact('courseModel', 'assignments', 'individual', 'groups', 'organizations', 'start_date', 'end_date', 'canEditCourse'));
    }

    public function destroy(CourseAssignment $course_assignment)
    {
        Gate::authorize('delete', $course_assignment);

        $course_assignment->delete();
        
        // Для AJAX запросов
        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.course-assignments.index')
            ->with('success', 'Назначение успешно удалено.');
    }
}
