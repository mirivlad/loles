<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRequest;
use App\Models\CourseRequestItem;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\User;
use App\Models\Group;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class CourseRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', CourseRequest::class);
        
        $query = CourseRequest::with(['organization', 'requestedBy', 'approvedBy', 'items.course']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }
        
        $requests = $query->orderBy('created_at', 'desc')->paginate(20);
        $organizations = Organization::pluck('name', 'id');
        
        return view('admin.course-requests.index', compact('requests', 'organizations'));
    }

    public function create()
    {
        Gate::authorize('create', CourseRequest::class);
        
        $courses = Course::pluck('title', 'id');
        $users = User::pluck('name', 'id');
        $groups = Group::pluck('name', 'id');
        $organizations = Organization::pluck('name', 'id');
        
        return view('admin.course-requests.create', compact('courses', 'users', 'groups', 'organizations'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CourseRequest::class);
        
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'status' => 'nullable|in:pending,approved,rejected',
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.course_id' => 'required|exists:courses,id',
            'items.*.organization_id' => 'nullable|exists:organizations,id',
            'items.*.user_id' => 'nullable|exists:users,id',
            'items.*.user_ids' => 'nullable|string',
            'items.*.group_id' => 'nullable|exists:groups,id',
            'items.*.group_ids' => 'nullable|string',
            'items.*.start_date' => 'required|date',
            'items.*.end_date' => 'nullable|date|after:items.*.start_date',
        ]);
        
        // Определяем статус
        $user = auth()->user();
        if ($validated['status'] === 'approved' || $user->hasRole(['Administrator', 'Manager', 'Curator'])) {
            $status = 'approved';
        } else {
            $status = 'pending';
        }
        
        DB::beginTransaction();
        
        try {
            // Определяем организацию из первого элемента или общую
            $organizationId = $validated['organization_id'] ?? null;
            
            // Создаём заявку
            $courseRequest = CourseRequest::create([
                'organization_id' => $organizationId,
                'requested_by_user_id' => $user->id,
                'status' => $status,
                'note' => $validated['note'] ?? null,
                'approved_by' => $status === 'approved' ? $user->id : null,
                'approved_at' => $status === 'approved' ? now() : null,
            ]);
            
            // Создаём элементы заявки
            foreach ($validated['items'] as $itemData) {
                // Определяем тип назначения
                $hasUsers = !empty($itemData['user_ids']);
                $hasGroups = !empty($itemData['group_ids']);
                $hasOrganization = !empty($itemData['organization_id']);
                
                // Создаём отдельные записи для каждого пользователя
                if ($hasUsers) {
                    $userIds = array_map('intval', array_filter(explode(',', $itemData['user_ids'])));
                    foreach ($userIds as $userId) {
                        CourseRequestItem::create([
                            'course_request_id' => $courseRequest->id,
                            'course_id' => $itemData['course_id'],
                            'user_id' => $userId,
                            'group_id' => null,
                            'start_date' => $itemData['start_date'],
                            'end_date' => $itemData['end_date'] ?? null,
                        ]);
                    }
                }
                
                // Создаём отдельные записи для каждой группы
                if ($hasGroups) {
                    $groupIds = array_map('intval', array_filter(explode(',', $itemData['group_ids'])));
                    foreach ($groupIds as $groupId) {
                        CourseRequestItem::create([
                            'course_request_id' => $courseRequest->id,
                            'course_id' => $itemData['course_id'],
                            'user_id' => null,
                            'group_id' => $groupId,
                            'start_date' => $itemData['start_date'],
                            'end_date' => $itemData['end_date'] ?? null,
                        ]);
                    }
                }
                
                // Если только организация (без пользователей и групп)
                if (!$hasUsers && !$hasGroups && $hasOrganization) {
                    CourseRequestItem::create([
                        'course_request_id' => $courseRequest->id,
                        'course_id' => $itemData['course_id'],
                        'user_id' => null,
                        'group_id' => null,
                        'start_date' => $itemData['start_date'],
                        'end_date' => $itemData['end_date'] ?? null,
                    ]);
                }
                
                // Если ничего не указано - создаём запись без привязки (для организации заявки)
                if (!$hasUsers && !$hasGroups && !$hasOrganization) {
                    CourseRequestItem::create([
                        'course_request_id' => $courseRequest->id,
                        'course_id' => $itemData['course_id'],
                        'user_id' => null,
                        'group_id' => null,
                        'start_date' => $itemData['start_date'],
                        'end_date' => $itemData['end_date'] ?? null,
                    ]);
                }
            }
            
            // Если заявка одобрена - сразу создаём назначения
            if ($status === 'approved') {
                $this->createAssignments($courseRequest);
            }
            
            DB::commit();
            
            return redirect()->route('admin.course-requests.index')
                ->with('success', $status === 'approved' ? 'Заявка создана и одобрена. Назначения созданы.' : 'Заявка создана и ожидает подтверждения.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ошибка при создании заявки: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(CourseRequest $courseRequest)
    {
        Gate::authorize('view', $courseRequest);
        
        $courseRequest->load(['organization', 'requestedBy', 'approvedBy', 'items.course', 'items.user', 'items.group']);
        
        return view('admin.course-requests.show', compact('courseRequest'));
    }

    public function edit(CourseRequest $courseRequest)
    {
        Gate::authorize('update', $courseRequest);
        
        if ($courseRequest->isApproved() || $courseRequest->isRejected()) {
            return back()->with('error', 'Нельзя редактировать ' . ($courseRequest->isApproved() ? 'одобренную' : 'отклонённую') . ' заявку.');
        }
        
        $courses = Course::pluck('title', 'id');
        $users = User::pluck('name', 'id');
        $groups = Group::pluck('name', 'id');
        
        return view('admin.course-requests.edit', compact('courseRequest', 'courses', 'users', 'groups'));
    }

    public function update(Request $request, CourseRequest $courseRequest)
    {
        Gate::authorize('update', $courseRequest);
        
        if ($courseRequest->isApproved() || $courseRequest->isRejected()) {
            return back()->with('error', 'Нельзя редактировать ' . ($courseRequest->isApproved() ? 'одобренную' : 'отклонённую') . ' заявку.');
        }
        
        $validated = $request->validate([
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:course_request_items,id',
            'items.*.course_id' => 'required|exists:courses,id',
            'items.*.organization_id' => 'nullable|exists:organizations,id',
            'items.*.user_id' => 'nullable|exists:users,id',
            'items.*.group_id' => 'nullable|exists:groups,id',
            'items.*.start_date' => 'required|date',
            'items.*.end_date' => 'nullable|date|after:items.*.start_date',
        ]);
        
        DB::beginTransaction();
        
        try {
            $courseRequest->update(['note' => $validated['note'] ?? null]);
            
            // Обновляем элементы
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    // Обновляем существующий
                    $item = CourseRequestItem::find($itemData['id']);
                    $item->update([
                        'course_id' => $itemData['course_id'],
                        'organization_id' => $itemData['organization_id'] ?? null,
                        'user_id' => $itemData['user_id'] ?? null,
                        'group_id' => $itemData['group_id'] ?? null,
                        'start_date' => $itemData['start_date'],
                        'end_date' => $itemData['end_date'] ?? null,
                    ]);
                } else {
                    // Создаём новый
                    CourseRequestItem::create([
                        'course_request_id' => $courseRequest->id,
                        'course_id' => $itemData['course_id'],
                        'organization_id' => $itemData['organization_id'] ?? null,
                        'user_id' => $itemData['user_id'] ?? null,
                        'group_id' => $itemData['group_id'] ?? null,
                        'start_date' => $itemData['start_date'],
                        'end_date' => $itemData['end_date'] ?? null,
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.course-requests.show', $courseRequest)
                ->with('success', 'Заявка обновлена.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ошибка при обновлении: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(CourseRequest $courseRequest)
    {
        Gate::authorize('delete', $courseRequest);
        
        if ($courseRequest->isApproved()) {
            return back()->with('error', 'Нельзя удалить одобренную заявку.');
        }
        
        $courseRequest->delete();
        
        return redirect()->route('admin.course-requests.index')
            ->with('success', 'Заявка удалена.');
    }

    public function approve(CourseRequest $courseRequest, Request $request)
    {
        Gate::authorize('approve', $courseRequest);
        
        if (!$courseRequest->isPending()) {
            return back()->with('error', 'Можно одобрить только заявку в статусе ожидания.');
        }
        
        DB::beginTransaction();
        
        try {
            $courseRequest->approve(auth()->user());
            $this->createAssignments($courseRequest);
            
            DB::commit();
            
            return back()->with('success', 'Заявка одобрена. Назначения созданы.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ошибка при одобрении: ' . $e->getMessage()]);
        }
    }

    public function reject(CourseRequest $courseRequest, Request $request)
    {
        Gate::authorize('reject', $courseRequest);
        
        if (!$courseRequest->isPending()) {
            return back()->with('error', 'Можно отклонить только заявку в статусе ожидания.');
        }
        
        $validated = $request->validate([
            'reject_reason' => 'nullable|string',
        ]);
        
        $courseRequest->reject(auth()->user());
        
        return back()->with('success', 'Заявка отклонена.');
    }

    /**
     * Создаёт назначения из одобренной заявки
     */
    private function createAssignments(CourseRequest $courseRequest): void
    {
        foreach ($courseRequest->items as $item) {
            // Определяем тип назначения
            if ($item->user_id) {
                $type = 'individual';
            } elseif ($item->group_id) {
                $type = 'group';
            } elseif ($item->organization_id) {
                $type = 'organization';
            } else {
                $type = 'organization';  // По умолчанию на организацию заявки
            }
            
            CourseAssignment::create([
                'course_id' => $item->course_id,
                'organization_id' => $item->organization_id ?? ($type === 'organization' ? $courseRequest->organization_id : null),
                'group_id' => $item->group_id,
                'user_id' => $item->user_id,
                'type' => $type,
                'start_date' => $item->start_date,
                'end_date' => $item->end_date,
                'note' => $courseRequest->note,
                'created_by' => $courseRequest->approved_by,
                'is_active' => true,
            ]);
        }
    }
}
