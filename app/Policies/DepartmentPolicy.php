<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Departments themselves are an admin-only workspace item — a manager runs
 * their department, they don't restructure the org chart. See
 * docs/modules/M11-manager-panel.md's capability table.
 */
class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_departments');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('manage_departments');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_departments');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('manage_departments');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('manage_departments');
    }
}
