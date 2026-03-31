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
        
        $usersQuery = User::query()->with('organization');
        
        // Если указан organization_id - фильтруем
        if ($organizationId) {
            $usersQuery->where('organization_id', $organizationId);
        }
        
        $users = $usersQuery
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name . ($user->organization ? " ({$user->organization->name})" : ''),
                ];
            });
        
        return response()->json($users);
    }
}
