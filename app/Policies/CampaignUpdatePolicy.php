<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CampaignUpdate;
use App\Models\User;

class CampaignUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_campaigns');
    }

    public function view(User $user, CampaignUpdate $campaignUpdate): bool
    {
        return $user->can('view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('update_campaigns');
    }

    public function update(User $user, CampaignUpdate $campaignUpdate): bool
    {
        return $user->can('update_campaigns');
    }

    public function delete(User $user, CampaignUpdate $campaignUpdate): bool
    {
        return $user->can('update_campaigns');
    }
}
