<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscriber;
use App\Models\User;

class SubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_subscribers');
    }

    public function view(User $user, Subscriber $subscriber): bool
    {
        return $user->can('manage_subscribers');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Subscriber $subscriber): bool
    {
        return $user->can('manage_subscribers');
    }

    public function delete(User $user, Subscriber $subscriber): bool
    {
        return $user->can('manage_subscribers');
    }
}
