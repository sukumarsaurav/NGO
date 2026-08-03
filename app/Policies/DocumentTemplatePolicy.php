<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentTemplate;
use App\Models\User;

/**
 * Admin-only — no manager permission grants `manage_document_templates`. See
 * docs/modules/M11-manager-panel.md's capability table: "Settings, users,
 * roles, receipts, templates — No access at all."
 */
class DocumentTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_document_templates');
    }

    public function view(User $user, DocumentTemplate $template): bool
    {
        return $user->can('manage_document_templates');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_document_templates');
    }

    public function update(User $user, DocumentTemplate $template): bool
    {
        return $user->can('manage_document_templates');
    }

    public function delete(User $user, DocumentTemplate $template): bool
    {
        return $user->can('manage_document_templates');
    }
}
