<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CampaignCategory;
use App\Models\User;

/**
 * Not in docs/modules/M11-manager-panel.md's named list, but the model has
 * its own admin resource (`CampaignCategoryResource`) — the same "no
 * exceptions" rule from that doc applies to any model reachable through a
 * Filament resource.
 */
class CampaignCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_campaigns');
    }

    public function view(User $user, CampaignCategory $campaignCategory): bool
    {
        return $user->can('view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_campaign_categories');
    }

    public function update(User $user, CampaignCategory $campaignCategory): bool
    {
        return $user->can('manage_campaign_categories');
    }

    public function delete(User $user, CampaignCategory $campaignCategory): bool
    {
        return $user->can('manage_campaign_categories');
    }
}
