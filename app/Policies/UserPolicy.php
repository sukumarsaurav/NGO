<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * User/role/permission management is `super-admin` only (`manage_users`
 * isn't granted to `admin` — see RolePermissionSeeder). Everyone can view
 * and update their own account regardless.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->can('manage_users');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->can('manage_users');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->can('manage_users');
    }
}
