<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);
        
        $query = User::with(['organization', 'roles']);
        
        // Фильтры
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }
        
        if ($request->filled('role')) {
            $query->role($request->role);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        $organizations = Organization::pluck('name', 'id');
        $roles = Role::pluck('name', 'name');
        
        return view('admin.users.index', compact('users', 'organizations', 'roles'));
    }

    public function create()
    {
        Gate::authorize('create', User::class);
        
        $organizations = Organization::pluck('name', 'id');
        $roles = Role::pluck('name', 'name');
        
        return view('admin.users.create', compact('organizations', 'roles'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'organization_id' => 'nullable|exists:organizations,id',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);
        
        $user->assignRole($validated['role']);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно создан.');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load(['organization', 'roles', 'groups']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        $user->load('groups');
        $organizations = Organization::pluck('name', 'id');
        $roles = Role::pluck('name', 'name');
        $userGroups = $user->groups->pluck('id')->toArray();

        // Показываем все доступные группы: общие + группы организации пользователя
        if ($user->organization_id) {
            $allGroups = Group::whereNull('organization_id')
                ->orWhere('organization_id', $user->organization_id)
                ->get();
        } else {
            $allGroups = Group::whereNull('organization_id')->get();
        }

        return view('admin.users.edit', compact('user', 'organizations', 'roles', 'userGroups', 'allGroups'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'organization_id' => 'nullable|exists:organizations,id',
            'role' => 'required|exists:roles,name',
            'groups' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Обновление пароля
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->save();
        }

        // Обновление роли
        $user->syncRoles([$validated['role']]);

        // Обновление групп (строка "1,2,3" → массив)
        if (!empty(trim($validated['groups'] ?? ''))) {
            $groupIds = array_map('intval', array_filter(explode(',', $validated['groups'])));
            $user->groups()->sync($groupIds);
        } else {
            // Если поле пустое - не трогаем группы (или можно сделать sync([]) для очистки)
            // $user->groups()->detach();
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Пользователь успешно обновлён.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        
        if ($user->isAdministrator()) {
            return back()->with('error', 'Невозможно удалить последнего администратора.');
        }
        
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно удалён.');
    }
}
