<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImpactStat;
use App\Models\User;

class ImpactStatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_impact_stats');
    }

    public function view(User $user, ImpactStat $impactStat): bool
    {
        return $user->can('manage_impact_stats');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_impact_stats');
    }

    public function update(User $user, ImpactStat $impactStat): bool
    {
        return $user->can('manage_impact_stats');
    }

    public function delete(User $user, ImpactStat $impactStat): bool
    {
        return $user->can('manage_impact_stats');
    }
}
