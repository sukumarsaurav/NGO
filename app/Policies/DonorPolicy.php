<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Donor;
use App\Models\User;

class DonorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_donors');
    }

    public function view(User $user, Donor $donor): bool
    {
        return $user->can('view_donors');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Donor $donor): bool
    {
        return $user->can('update_donors');
    }

    public function delete(User $user, Donor $donor): bool
    {
        return false;
    }
}
