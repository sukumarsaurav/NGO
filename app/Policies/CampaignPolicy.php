<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

/**
 * A manager gets `view_campaigns` (read-only) — see
 * docs/modules/M11-manager-panel.md's capability table.
 */
class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_campaigns');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->can('view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('create_campaigns');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can('update_campaigns');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->can('delete_campaigns');
    }

    public function publish(User $user, Campaign $campaign): bool
    {
        return $user->can('publish_campaigns');
    }
}
