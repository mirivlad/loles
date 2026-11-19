<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

        $search = trim(mb_substr((string) $request->query('search', ''), 0, 150));
        $status = (string) $request->query('status', '');

        $query = User::query()->with(['organization', 'roles']);

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->query('organization_id'));
        }

        if ($request->filled('role')) {
            $query->role((string) $request->query('role'));
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $organizations = Organization::orderBy('name')->pluck('name', 'id');
        $roles = Role::orderBy('name')->pluck('name', 'name');
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $unassignedUsers = User::whereNull('organization_id')->count();

        return view('admin.users.index',
            compact('users', 'organizations', 'roles', 'totalUsers', 'activeUsers', 'unassignedUsers'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', User::class);

        $roles = Role::whereIn('name', $this->assignableRoleNames())->pluck('name', 'name');
        $initialOrganization = Organization::find($request->integer('organization_id'));

        return view('admin.users.create', compact('roles', 'initialOrganization'));
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
            'role' => ['required', Rule::in($this->assignableRoleNames())],
            'is_active' => 'boolean',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
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
        $roles = Role::whereIn('name', $this->assignableRoleNames())->pluck('name', 'name');
        $userGroups = $user->groups->pluck('id')->toArray();

        // Показываем все доступные группы: общие + группы организации пользователя
        if ($user->organization_id) {
            $allGroups = Group::whereNull('organization_id')
                ->orWhere('organization_id', $user->organization_id)
                ->get();
        } else {
            $allGroups = Group::whereNull('organization_id')->get();
        }

        return view('admin.users.edit', compact('user', 'roles', 'userGroups', 'allGroups'));
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
            'role' => ['required', Rule::in($this->assignableRoleNames())],
            'groups' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'organization_id' => $validated['organization_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
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

    /**
     * Only administrators can grant administrative or manager-level roles.
     * Managers may onboard students/curators; curators may onboard students.
     */
    private function assignableRoleNames(): array
    {
        $actor = auth()->user();

        if ($actor?->hasRole('Administrator')) {
            return Role::pluck('name')->all();
        }
        if ($actor?->hasRole('Manager')) {
            return ['Student', 'Curator'];
        }

        return ['Student'];
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        
        if ($user->isAdministrator() && User::role('Administrator')->count() <= 1) {
            return back()->with('error', 'Невозможно удалить последнего администратора.');
        }
        
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно удалён.');
    }
}
