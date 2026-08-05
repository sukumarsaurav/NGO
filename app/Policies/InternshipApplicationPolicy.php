<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InternshipApplication;
use App\Models\User;

class InternshipApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_internship_applications');
    }

    public function view(User $user, InternshipApplication $internshipApplication): bool
    {
        return $user->can('manage_internship_applications');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_internship_applications');
    }

    public function update(User $user, InternshipApplication $internshipApplication): bool
    {
        return $user->can('manage_internship_applications');
    }

    public function delete(User $user, InternshipApplication $internshipApplication): bool
    {
        return $user->can('manage_internship_applications');
    }
}
