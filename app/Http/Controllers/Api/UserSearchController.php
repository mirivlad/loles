<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->get('q', '');
        $organizationId = $request->get('organization_id', null);
        $excludeOrganizationId = $request->get('exclude_organization_id', null);
        
        $usersQuery = User::query()->with('organization');
        
        // Исключаем пользователей из организации (для добавления в организацию)
        if ($excludeOrganizationId) {
            $usersQuery->where(function($q) use ($excludeOrganizationId) {
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', '!=', $excludeOrganizationId);
            });
        }
        
        // Если указан organization_id - фильтруем
        if ($organizationId) {
            $usersQuery->where('organization_id', $organizationId);
        }
        
        // Фильтр по имени И email (правильная группировка)
        $usersQuery->where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        });
        
        $users = $usersQuery
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name . ($user->organization ? " ({$user->organization->name})" : ' (Без организации)'),
                ];
            });
        
        return response()->json($users);
    }
}
