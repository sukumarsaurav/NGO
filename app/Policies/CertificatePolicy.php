<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_certificates');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->can('manage_certificates');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_certificates');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->can('manage_certificates');
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->can('manage_certificates');
    }
}
