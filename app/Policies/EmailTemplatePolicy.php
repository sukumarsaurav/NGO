<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_email_templates');
    }

    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->can('manage_email_templates');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_email_templates');
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->can('manage_email_templates');
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return false;
    }
}
