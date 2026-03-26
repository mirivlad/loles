<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Organization $organization)
    {
        Gate::authorize('view', $organization);
        
        $groups = $organization->groups()->withCount('users')->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.groups.index', compact('organization', 'groups'));
    }

    public function create(Organization $organization)
    {
        Gate::authorize('create', Group::class);
        
        return view('admin.groups.create', compact('organization'));
    }

    public function store(Request $request, Organization $organization)
    {
        Gate::authorize('create', Group::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        
        $organization->groups()->create($validated);
        
        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Группа успешно создана.');
    }

    public function edit(Organization $organization, Group $group)
    {
        Gate::authorize('update', $group);
        
        return view('admin.groups.edit', compact('organization', 'group'));
    }

    public function update(Request $request, Organization $organization, Group $group)
    {
        Gate::authorize('update', $group);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        
        $group->update($validated);
        
        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Группа успешно обновлена.');
    }

    public function destroy(Organization $organization, Group $group)
    {
        Gate::authorize('delete', $group);
        
        if ($group->users()->count() > 0) {
            return back()->with('error', 'Невозможно удалить группу с пользователями.');
        }
        
        $group->delete();
        
        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Группа успешно удалена.');
    }
}
