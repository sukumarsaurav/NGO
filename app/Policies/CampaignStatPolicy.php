<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CampaignStat;
use App\Models\User;

class CampaignStatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_campaigns');
    }

    public function view(User $user, CampaignStat $campaignStat): bool
    {
        return $user->can('view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('update_campaigns');
    }

    public function update(User $user, CampaignStat $campaignStat): bool
    {
        return $user->can('update_campaigns');
    }

    public function delete(User $user, CampaignStat $campaignStat): bool
    {
        return $user->can('update_campaigns');
    }
}
