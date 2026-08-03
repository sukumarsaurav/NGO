<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

/**
 * A manager gets `view_donations` (read-only, org-wide) but no edit or
 * refund ability — see docs/modules/M11-manager-panel.md: "managers manage
 * people, not money." Refunds are a distinct ability, not `update`, so a
 * view-only grant can never imply refund.
 */
class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_donations');
    }

    public function view(User $user, Donation $donation): bool
    {
        return $user->can('view_donations');
    }

    public function create(User $user): bool
    {
        return $user->can('create_offline_donations');
    }

    public function update(User $user, Donation $donation): bool
    {
        return false;
    }

    public function delete(User $user, Donation $donation): bool
    {
        return false;
    }

    public function refund(User $user, Donation $donation): bool
    {
        return $user->can('refund_donations');
    }
}
