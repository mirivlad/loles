<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->get('q', '');
        
        $organizations = Organization::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('inn', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function($org) {
                return [
                    'id' => $org->id,
                    'text' => $org->name . ($org->inn ? " (ИНН: {$org->inn})" : ''),
                ];
            });
        
        return response()->json($organizations);
    }
}
