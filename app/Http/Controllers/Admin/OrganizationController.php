<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        Gate::authorize('viewAny', Organization::class);
        
        $organizations = Organization::withCount(['users', 'groups'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.organizations.index', compact('organizations'));
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

    public function edit(Organization $organization)
    {
        Gate::authorize('update', $organization);
        
        return view('admin.organizations.edit', compact('organization'));
    }

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
        ]);
        
        $validated['is_active'] = $request->boolean('is_active');
        
        $organization->update($validated);
        
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
