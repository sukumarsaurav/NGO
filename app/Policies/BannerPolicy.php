<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_banners');
    }

    public function view(User $user, Banner $banner): bool
    {
        return $user->can('manage_banners');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_banners');
    }

    public function update(User $user, Banner $banner): bool
    {
        return $user->can('manage_banners');
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $user->can('manage_banners');
    }
}
