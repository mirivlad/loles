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
        
        $organizations = Organization::pluck('name', 'id');
        
        return view('admin.groups.create', compact('organizations'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Group::class);
        
        $validated = $request->validate([
            'group_type' => 'required|in:organization,general',
            'organization_id' => 'nullable|exists:organizations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        // Для группы организации organization_id обязательна
        if ($validated['group_type'] === 'organization' && empty($validated['organization_id'])) {
            return back()->withErrors(['organization_id' => 'Выберите организацию для группы'])->withInput();
        }
        
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
        $users = User::where('organization_id', $group->organization_id)->get();
        
        return view('admin.groups.show', compact('group', 'users'));
    }

    public function edit(Group $group)
    {
        Gate::authorize('update', $group);
        
        $group->load(['organization', 'users']);
        
        // Получаем доступных пользователей для этой группы
        if ($group->organization_id) {
            // Группа организации — только пользователи этой организации
            $users = User::where('organization_id', $group->organization_id)->get();
        } else {
            // Общая группа — все пользователи
            $users = User::all();
        }
        
        return view('admin.groups.edit', compact('group', 'users'));
    }

    public function update(Request $request, Group $group)
    {
        Gate::authorize('update', $group);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
        ]);
        
        $group->update($validated);
        
        // Синхронизируем пользователей в группе
        if (isset($validated['users'])) {
            $group->users()->sync($validated['users']);
        }
        
        return redirect()->route('admin.groups.show', $group)
            ->with('success', 'Группа успешно обновлена.');
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
        ]);
        
        $group = Group::findOrFail($validated['group_id']);
        
        // Проверка доступа
        if ($group->organization_id && $user->organization_id !== $group->organization_id) {
            return back()->with('error', 'Нельзя добавить пользователя в группу другой организации.');
        }
        
        $group->users()->attach($user->id);
        
        return back()->with('success', 'Пользователь добавлен в группу.');
    }
    
    /**
     * Удалить пользователя из группы
     */
    public function removeUser(Group $group, User $user)
    {
        Gate::authorize('update', $group);
        
        $group->users()->detach($user->id);
        
        return back()->with('success', 'Пользователь удалён из группы.');
    }
}
