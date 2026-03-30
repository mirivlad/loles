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
            $query->where('organization_id', $request->organization_id);
        }
        
        $groups = $query->orderBy('name')->paginate(20);
        $organizations = Organization::pluck('name', 'id');
        
        return view('admin.groups.index', compact('groups', 'organizations'));
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
        $users = User::where('organization_id', $group->organization_id)->get();
        
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
}
