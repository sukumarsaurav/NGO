<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\NoticeAudience;
use App\Models\Notice;
use App\Models\User;

/**
 * `notices` has no `department_id` column — it has `audience` +
 * `audience_filter` JSON. A manager may view a notice they created, or a
 * department-targeted notice aimed at a department they manage. See
 * docs/modules/M11-manager-panel.md's NoticeResource scope example — this
 * policy mirrors that same rule as the record-level check (Layer 2).
 */
class NoticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_notices');
    }

    public function view(User $user, Notice $notice): bool
    {
        if (! $user->can('view_notices')) {
            return false;
        }

        if ($notice->created_by_user_id === $user->id) {
            return true;
        }

        if ($notice->audience !== NoticeAudience::Department) {
            return false;
        }

        $departmentIds = $notice->audience_filter['department_ids'] ?? [];

        return array_intersect($departmentIds, $user->managedDepartmentIds()) !== [];
    }

    public function create(User $user): bool
    {
        return $user->can('publish_notices');
    }

    public function update(User $user, Notice $notice): bool
    {
        return $user->can('publish_notices') && $notice->created_by_user_id === $user->id;
    }

    public function delete(User $user, Notice $notice): bool
    {
        return $user->can('publish_notices') && $notice->created_by_user_id === $user->id;
    }
}
