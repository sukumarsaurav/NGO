<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_contact_messages');
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('view_contact_messages');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('view_contact_messages');
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return $user->can('view_contact_messages');
    }
}
