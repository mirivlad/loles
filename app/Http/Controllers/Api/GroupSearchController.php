<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->get('q', '');
        $userId = $request->get('user_id', null);
        
        $groupsQuery = Group::query()->with('organization');
        
        // Если указан user_id - фильтруем по доступным группам
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                if ($user->organization_id) {
                    // Пользователь в организации - показываем общие + группы организации
                    $groupsQuery->where(function($q) use ($user) {
                        $q->whereNull('organization_id')
                          ->orWhere('organization_id', $user->organization_id);
                    });
                } else {
                    // Пользователь без организации - только общие группы
                    $groupsQuery->whereNull('organization_id');
                }
            }
        }
        
        $groups = $groupsQuery
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function($group) {
                return [
                    'id' => $group->id,
                    'text' => $group->name . ($group->organization ? " ({$group->organization->name})" : ' (Общая)'),
                ];
            });
        
        return response()->json($groups);
    }
}
