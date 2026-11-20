<?php

namespace App\Services;

use App\Models\TrainingIntake;
use App\Models\User;

class TrainingReportingAccess
{
    /**
     * Null means unrestricted for administrators/managers. Curators are
     * restricted to their explicitly assigned enterprise (organization_id).
     * Never derive visibility from a learner's current employer.
     */
    public function organizationIds(User $user): ?array
    {
        if ($user->hasRole(['Administrator', 'Manager'])) {
            return null;
        }
        abort_unless($user->hasRole('Curator') && $user->organization_id, 403);
        return [(int) $user->organization_id];
    }

    public function authorizeIntake(User $user, TrainingIntake $intake): ?array
    {
        $ids = $this->organizationIds($user);
        if ($ids !== null) {
            abort_unless($intake->enrollments()
                ->whereIn('organization_id', $ids)->exists(), 403);
        }
        return $ids;
    }
}
