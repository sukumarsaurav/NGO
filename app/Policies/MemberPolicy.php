<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

/**
 * super-admin and admin bypass every check via Gate::before (AppServiceProvider)
 * — this class only has to get manager/member/donor right.
 *
 * manager: scoped to their own department(s) — see User::managedDepartmentIds().
 *   Checking BOTH the permission AND the department membership matters: the
 *   permission says a manager role can view members at all, the department
 *   check is what stops a manager from viewing another department's member by
 *   guessing an ID. See docs/modules/M11-manager-panel.md — relying on a query
 *   scope alone (hiding rows from a list) without this policy is an IDOR.
 * member: can view/update only their own record. Cannot delete.
 * donor: no access — members and donors are separate profile types even when
 *   the same person holds both roles (docs/modules/M03-members.md edge case).
 */
class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_members');
    }

    public function view(User $user, Member $member): bool
    {
        if ($this->isOwnRecord($user, $member)) {
            return true;
        }

        return $user->can('view_members') && $this->inManagedDepartment($user, $member);
    }

    public function create(User $user): bool
    {
        return $user->can('create_members');
    }

    public function update(User $user, Member $member): bool
    {
        if ($this->isOwnRecord($user, $member)) {
            // Self-service editing is allowed at the policy level; which
            // fields are editable (never department/designation/status) is
            // enforced by the portal form, not here — see docs/modules/M03-members.md.
            return true;
        }

        return $user->can('update_members') && $this->inManagedDepartment($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return false;
    }

    public function restore(User $user, Member $member): bool
    {
        return false;
    }

    public function forceDelete(User $user, Member $member): bool
    {
        return false;
    }

    private function isOwnRecord(User $user, Member $member): bool
    {
        return $member->user_id === $user->id;
    }

    private function inManagedDepartment(User $user, Member $member): bool
    {
        if ($member->department_id === null) {
            return false;
        }

        return in_array($member->department_id, $user->managedDepartmentIds(), true);
    }
}
