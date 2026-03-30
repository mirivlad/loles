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
        
        $groups = Group::query()
            ->with('organization')
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
