<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Redirect;
use App\Models\User;

class RedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_redirects');
    }

    public function view(User $user, Redirect $redirect): bool
    {
        return $user->can('manage_redirects');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_redirects');
    }

    public function update(User $user, Redirect $redirect): bool
    {
        return $user->can('manage_redirects');
    }

    public function delete(User $user, Redirect $redirect): bool
    {
        return $user->can('manage_redirects');
    }
}
