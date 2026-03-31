<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class GroupUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Group::class);
        
        $query = Group::with(['organization', 'users']);
        
        if ($request->filled('organization_id')) {
            if ($request->organization_id === 'general') {
                $query->whereNull('organization_id');
            } else {
                $query->where('organization_id', $request->organization_id);
            }
        }
        
        if ($request->filled('user_id')) {
            $query->whereHas('users', function($q) use ($request) {
                $q->where('users.id', $request->user_id);
            });
        }
        
        $groups = $query->orderBy('name')->paginate(20);
        $organizations = Organization::pluck('name', 'id');
        
        return view('admin.groups.index', compact('groups', 'organizations'));
    }

    public function create()
    {
        Gate::authorize('create', Group::class);
        
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Group::class);
        
        $validated = $request->validate([
            'group_type' => 'required|in:organization,general',
            'organization_id' => 'required_if:group_type,organization|nullable|exists:organizations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        Group::create([
            'organization_id' => $validated['group_type'] === 'organization' ? $validated['organization_id'] : null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);
        
        return redirect()->route('admin.groups.index')
            ->with('success', 'Группа успешно создана.');
    }

    public function show(Group $group)
    {
        Gate::authorize('view', $group);
        
        $group->load(['organization', 'users']);
        
        // Получаем доступных пользователей для этой группы
        if ($group->organization_id) {
            // Группа организации — только пользователи этой организации
            $users = User::where('organization_id', $group->organization_id)
                ->whereDoesntHave('groups', function($q) use ($group) {
                    $q->where('groups.id', $group->id);
                })
                ->get();
        } else {
            // Общая группа — все пользователи которые ещё не в группе
            $users = User::whereDoesntHave('groups', function($q) use ($group) {
                $q->where('groups.id', $group->id);
            })
            ->get();
        }

        return view('admin.groups.show', compact('group', 'users'));
    }

    public function destroy(Group $group)
    {
        Gate::authorize('delete', $group);
        
        if ($group->users()->count() > 0) {
            return back()->with('error', 'Нельзя удалить группу с пользователями. Сначала удалите пользователей из группы.');
        }
        
        $group->delete();
        
        return redirect()->route('admin.groups.index')
            ->with('success', 'Группа успешно удалена.');
    }
    
    /**
     * Добавить пользователя в группу
     */
    public function addUser(User $user, Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'user_ids' => 'nullable|string',
        ]);
        
        $group = Group::findOrFail($validated['group_id']);
        
        // Если переданы user_ids (из tags-input)
        if (!empty($validated['user_ids'])) {
            $userIds = array_map('intval', array_filter(explode(',', $validated['user_ids'])));
            
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if (!$user) continue;
                
                // Проверка доступа
                if ($group->organization_id && $user->organization_id !== $group->organization_id) {
                    continue; // Пропускаем пользователей из других организаций
                }
                
                $group->users()->attach($userId);
            }
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Пользователи добавлены в группу.');
    }
    
    /**
     * Удалить пользователя из группы
     */
    public function removeUser(Group $group, User $user)
    {
        Gate::authorize('update', $group);
        
        $group->users()->detach($user->id);
        
        // Для AJAX запросов
        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Пользователь удалён из группы.');
    }
    
    /**
     * Редактирование группы (modal)
     */
    public function edit(Group $group)
    {
        Gate::authorize('update', $group);
        
        return redirect()->route('admin.groups.show', $group)->with('edit', true);
    }
    
    /**
     * Обновление группы
     */
    public function update(Request $request, Group $group)
    {
        Gate::authorize('update', $group);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $group->update($validated);
        
        // Для AJAX запросов
        if ($request->ajax()) {
            return response()->json(['success' => true, 'group' => $group]);
        }
        
        return redirect()->route('admin.groups.show', $group)
            ->with('success', 'Группа успешно обновлена.');
    }
}
