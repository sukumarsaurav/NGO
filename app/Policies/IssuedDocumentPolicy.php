<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IssuedDocument;
use App\Models\User;

/**
 * `issued_documents` has `member_id`, not `department_id` — the scope has to
 * join through `member`. See docs/modules/M11-manager-panel.md: "only
 * `members` carries a `department_id`."
 *
 * Revocation is scoped narrower than view/issue: "only ones they issued",
 * not the whole managed department — see the capability table.
 */
class IssuedDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_documents');
    }

    public function view(User $user, IssuedDocument $document): bool
    {
        return $user->can('view_documents') && $this->inManagedDepartment($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->can('issue_documents');
    }

    /**
     * Issued documents are immutable once issued (see docs/02-DATABASE-SCHEMA.md
     * §4) — reissuing creates a new row rather than editing this one.
     */
    public function update(User $user, IssuedDocument $document): bool
    {
        return false;
    }

    public function delete(User $user, IssuedDocument $document): bool
    {
        return false;
    }

    public function revoke(User $user, IssuedDocument $document): bool
    {
        if (! $user->can('revoke_documents')) {
            return false;
        }

        return $document->issued_by_user_id === $user->id || $this->inManagedDepartment($user, $document);
    }

    private function inManagedDepartment(User $user, IssuedDocument $document): bool
    {
        $departmentId = $document->member?->department_id;

        if ($departmentId === null) {
            return false;
        }

        return in_array($departmentId, $user->managedDepartmentIds(), true);
    }
}
