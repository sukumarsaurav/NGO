<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PressMention;
use App\Models\User;

class PressMentionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_press_mentions');
    }

    public function view(User $user, PressMention $pressMention): bool
    {
        return $user->can('manage_press_mentions');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_press_mentions');
    }

    public function update(User $user, PressMention $pressMention): bool
    {
        return $user->can('manage_press_mentions');
    }

    public function delete(User $user, PressMention $pressMention): bool
    {
        return $user->can('manage_press_mentions');
    }
}
