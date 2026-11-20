<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingIntake;
use App\Services\TrainingReportingAccess;
use Illuminate\Http\Request;

class CuratorTrainingController extends Controller
{
    public function index(Request $request, TrainingReportingAccess $access)
    {
        abort_unless($request->user()->hasRole('Curator'), 403);
        $ids = $access->organizationIds($request->user());
        $intakes = TrainingIntake::query()
            ->whereHas('enrollments', fn ($q) => $q->whereIn('organization_id', $ids))
            ->with('program')
            ->withCount(['enrollments' => fn ($q) => $q->whereIn('organization_id', $ids)])
            ->orderByDesc('starts_on')->paginate(20);

        return view('admin.training.curator.index', compact('intakes'));
    }
}
