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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\StudentCredentials;
use App\Rules\LoginName;
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

        $query = User::query()->with(['organization', 'roles'])->withExists('issuedCredential');

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->query('organization_id'));
        }

        if ($request->filled('role')) {
            $query->role((string) $request->query('role'));
        }
        if ($request->filled('group_id')) {
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $request->integer('group_id')));
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
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
        $groups = Group::orderBy('name')->pluck('name', 'id');
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $unassignedUsers = User::whereNull('organization_id')->count();

        return view('admin.users.index',
            compact('users', 'organizations', 'groups', 'roles', 'totalUsers', 'activeUsers', 'unassignedUsers'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', User::class);

        $roles = Role::whereIn('name', $this->assignableRoleNames())->pluck('name', 'name');
        $initialOrganization = Organization::find($request->integer('organization_id'));
        $allGroups = Group::with('organization')->orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'initialOrganization', 'allGroups'));
    }

    public function store(Request $request, StudentCredentials $credentials)
    {
        Gate::authorize('create', User::class);

        // Normalize before uniqueness validation and before saving.
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username', '')))]);

        $role = (string) $request->input('role', 'Student');
        $isStudent = $role === 'Student';
        $auto = $request->boolean('generate_password');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', new LoginName(), Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'confirmed'],
            'generate_password' => ['nullable', 'boolean'],
            'generated_password_length' => ['nullable', Rule::in([6, 8])],
            'phone' => ['nullable', 'string', 'max:20'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'role' => ['nullable', Rule::in($this->assignableRoleNames())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($auto && !$isStudent) {
            throw ValidationException::withMessages(['role' => 'Автоматическая выдача короткого пароля доступна только учащимся.']);
        }

        $password = $auto
            ? $credentials->generate((int) ($validated['generated_password_length'] ?? 6))
            : (string) ($validated['password'] ?? '');
        if (strlen($password) < ($isStudent ? 6 : 12)) {
            throw ValidationException::withMessages(['password' => $isStudent
                ? 'Минимальная длина пароля учащегося — 6 символов.'
                : 'Пароль сотрудника должен содержать не менее 12 символов.']);
        }

        $group = isset($validated['group_id']) ? Group::find($validated['group_id']) : null;
        if ($group && $group->organization_id !== null
            && (int) $group->organization_id !== (int) ($validated['organization_id'] ?? 0)) {
            throw ValidationException::withMessages(['group_id' =>
                'Учебная группа должна принадлежать выбранному предприятию.']);
        }

        $username = strtolower(trim((string) ($validated['username'] ?? '')));
        $user = DB::transaction(function () use ($request, $validated, $role, $username, $password, $isStudent, $credentials, $group) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $username ?: 'new_' . Str::lower(Str::random(24)),
                'email' => $validated['email'] ?? null,
                'password' => $password,
                'phone' => $validated['phone'] ?? null,
                'organization_id' => $validated['organization_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
            if ($username === '') {
                $candidate = 's' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT);
                if (User::where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                    $candidate = 'u_' . $user->id;
                }
                $user->update(['username' => $candidate]);
            }
            $user->assignRole($role);
            if ($group) {
                $user->groups()->syncWithoutDetaching([$group->id]);
            }
            if ($isStudent) {
                $credentials->issue($user, $password, $request->user());
            }

            return $user;
        });

        return redirect()->route('admin.users.index', ['search' => $user->username])
            ->with('success', 'Пользователь создан. Реквизиты учащегося доступны через кнопку «Показать пароли».');
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

    public function update(Request $request, User $user, StudentCredentials $credentials)
    {
        Gate::authorize('update', $user);
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username', '')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', new LoginName(),
                Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'role' => ['required', Rule::in($this->assignableRoleNames())],
            'groups' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['password']) && strlen($validated['password']) < ($validated['role'] === 'Student' ? 6 : 12)) {
            throw ValidationException::withMessages(['password' => 'Минимум 6 символов для учащихся, 12 — для сотрудников.']);
        }

        DB::transaction(function () use ($user, $request, $validated, $credentials) {
            $user->update([
                'name' => $validated['name'],
                'username' => strtolower($validated['username']),
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'organization_id' => $validated['organization_id'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);
            $user->syncRoles([$validated['role']]);

            if (!empty($validated['password'])) {
                $user->update([
                    'password' => $validated['password'],
                    'remember_token' => Str::random(60),
                ]);
                if ($validated['role'] === 'Student') {
                    $credentials->issue($user, $validated['password'], $request->user());
                }
            }
            if ($validated['role'] !== 'Student') {
                $credentials->clear($user);
            }

            if (isset($validated['groups'])) {
                $groupIds = array_unique(array_map('intval', array_filter(explode(',', $validated['groups']))));
                $allowed = Group::whereIn('id', $groupIds)
                    ->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $user->organization_id))
                    ->pluck('id')->all();
                if (count($allowed) !== count($groupIds)) {
                    throw ValidationException::withMessages(['groups' => 'Одна из групп не принадлежит выбранному предприятию.']);
                }
                $user->groups()->sync($allowed);
            }
        });

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
