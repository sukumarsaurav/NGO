<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_settings');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('manage_settings');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->can('manage_settings');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return false;
    }
}
