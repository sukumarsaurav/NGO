<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CampaignFaq;
use App\Models\User;

class CampaignFaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_campaigns');
    }

    public function view(User $user, CampaignFaq $campaignFaq): bool
    {
        return $user->can('view_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('update_campaigns');
    }

    public function update(User $user, CampaignFaq $campaignFaq): bool
    {
        return $user->can('update_campaigns');
    }

    public function delete(User $user, CampaignFaq $campaignFaq): bool
    {
        return $user->can('update_campaigns');
    }
}
