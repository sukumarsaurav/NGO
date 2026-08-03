<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_subscriptions');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->can('view_subscriptions');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $user->can('cancel_subscriptions');
    }
}
