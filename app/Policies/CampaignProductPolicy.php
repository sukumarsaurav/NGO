<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CampaignProduct;
use App\Models\User;

class CampaignProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_campaigns');
    }

    public function view(User $user, CampaignProduct $campaignProduct): bool
    {
        return $user->can('view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_campaign_products');
    }

    public function update(User $user, CampaignProduct $campaignProduct): bool
    {
        return $user->can('manage_campaign_products');
    }

    public function delete(User $user, CampaignProduct $campaignProduct): bool
    {
        return $user->can('manage_campaign_products');
    }
}
