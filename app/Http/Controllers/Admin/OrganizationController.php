<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Organization::class);

        $search = trim(mb_substr((string) $request->query('search', ''), 0, 150));
        $status = (string) $request->query('status', '');

        $organizations = Organization::query()
            ->withCount(['users', 'groups'])
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('inn', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(in_array($status, ['active', 'inactive'], true),
                fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $totalOrganizations = Organization::count();
        $activeOrganizations = Organization::where('is_active', true)->count();
        $totalCompanyUsers = User::whereNotNull('organization_id')->count();

        return view('admin.organizations.index',
            compact('organizations', 'totalOrganizations', 'activeOrganizations', 'totalCompanyUsers'));
    }

    public function create()
    {
        Gate::authorize('create', Organization::class);
        
        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Organization::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:organizations',
            'inn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        
        Organization::create($validated);
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'Организация успешно создана.');
    }

    public function show(Organization $organization)
    {
        Gate::authorize('view', $organization);
        
        $organization->load(['users', 'groups', 'courseRequests']);
        
        return view('admin.organizations.show', compact('organization'));
    }
    
    /**
     * Добавить пользователя в организацию
     */
    public function addUser(Organization $organization, Request $request)
    {
        Gate::authorize('update', $organization);
        
        $validated = $request->validate([
            'user_ids' => 'nullable|string',
        ]);
        
        if (!empty($validated['user_ids'])) {
            $userIds = array_map('intval', array_filter(explode(',', $validated['user_ids'])));
            
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if (!$user) continue;
                
                // Проверка: не состоит ли уже в организации
                if ($user->organization_id === $organization->id) {
                    continue;
                }
                
                $user->update(['organization_id' => $organization->id]);
            }
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Пользователи добавлены в организацию.');
    }
    
    /**
     * Удалить пользователя из организации
     */
    public function removeUser(Request $request, Organization $organization, User $user)
    {
        Gate::authorize('update', $organization);
        
        if ($user->organization_id === $organization->id) {
            $user->update(['organization_id' => null]);
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Пользователь удалён из организации.');
    }
    
    /**
     * Добавить группу в организацию
     */
    public function addGroup(Organization $organization, Request $request)
    {
        Gate::authorize('update', $organization);
        
        $validated = $request->validate([
            'group_ids' => 'nullable|string',
        ]);
        
        if (!empty($validated['group_ids'])) {
            $groupIds = array_map('intval', array_filter(explode(',', $validated['group_ids'])));
            
            foreach ($groupIds as $groupId) {
                $group = Group::find($groupId);
                if (!$group) continue;
                
                // Проверка: не состоит ли уже в организации
                if ($group->organization_id === $organization->id) {
                    continue;
                }
                
                $group->update(['organization_id' => $organization->id]);
            }
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Группы добавлены в организацию.');
    }
    
    /**
     * Удалить группу из организации
     */
    public function removeGroup(Request $request, Organization $organization, Group $group)
    {
        Gate::authorize('update', $organization);
        
        if ($group->organization_id === $organization->id) {
            $group->update(['organization_id' => null]);
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('success', 'Группа удалена из организации.');
    }
    
    /**
     * Редактирование организации (modal)
     */
    public function edit(Organization $organization)
    {
        Gate::authorize('update', $organization);
        
        return redirect()->route('admin.organizations.show', $organization)->with('edit', true);
    }
    
    /**
     * Обновление организации
     */
    public function update(Request $request, Organization $organization)
    {
        Gate::authorize('update', $organization);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:organizations,name,' . $organization->id,
            'inn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $organization->update($validated);
        
        // Для AJAX запросов
        if ($request->ajax()) {
            return response()->json(['success' => true, 'organization' => $organization]);
        }
        
        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Организация успешно обновлена.');
    }

    public function destroy(Organization $organization)
    {
        Gate::authorize('delete', $organization);
        
        if ($organization->users()->count() > 0) {
            return back()->with('error', 'Невозможно удалить организацию с пользователями.');
        }
        
        $organization->delete();
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'Организация успешно удалена.');
    }
}
