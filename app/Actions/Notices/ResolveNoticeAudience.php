<?php

declare(strict_types=1);

namespace App\Actions\Notices;

use App\Enums\MemberStatus;
use App\Enums\NoticeAudience;
use App\Models\Department;
use App\Models\Donor;
use App\Models\Member;

/**
 * Resolves an audience to a deduplicated list of `users.id`. Shared by
 * `PublishNotice` (resolves for real, at publish time) and the compose UI's
 * live recipient count (resolves the same filter as an estimate) — see
 * docs/modules/M09-notices-communication.md: "Nobody should discover the
 * audience size after hitting send."
 *
 * `notice_recipients.user_id` references `users`. Every `Member` has a
 * required, unique `user_id` (see `members` migration), so member audiences
 * never need a null check. `Donor.user_id` is nullable — a donor without a
 * linked user account (never registered) has no portal to read a notice in
 * and is excluded from `all_donors` even if `marketing_opt_in` is true.
 */
final class ResolveNoticeAudience
{
    /**
     * @param  array<string, mixed>  $filter
     * @return list<int>
     */
    public function handle(NoticeAudience $audience, array $filter): array
    {
        $userIds = match ($audience) {
            NoticeAudience::AllMembers => $this->activeMemberUserIds(),
            NoticeAudience::Department => $this->byDepartment($filter['department_ids'] ?? []),
            NoticeAudience::Designation => $this->byDesignation($filter['designation_ids'] ?? []),
            NoticeAudience::Specific => array_map(intval(...), $filter['user_ids'] ?? []),
            NoticeAudience::AllDonors => $this->optedInDonorUserIds(),
        };

        return array_values(array_unique($userIds));
    }

    /**
     * @return list<int>
     */
    private function activeMemberUserIds(): array
    {
        return Member::query()
            ->where('status', MemberStatus::Active->value)
            ->pluck('user_id')
            ->all();
    }

    /**
     * @param  array<int, int>  $departmentIds
     * @return list<int>
     */
    private function byDepartment(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        $allIds = Department::query()
            ->whereIn('id', $departmentIds)
            ->get()
            ->flatMap(fn (Department $department) => $department->selfAndDescendantIds())
            ->all();

        return Member::query()
            ->where('status', MemberStatus::Active->value)
            ->whereIn('department_id', $allIds)
            ->pluck('user_id')
            ->all();
    }

    /**
     * @param  array<int, int>  $designationIds
     * @return list<int>
     */
    private function byDesignation(array $designationIds): array
    {
        if ($designationIds === []) {
            return [];
        }

        return Member::query()
            ->where('status', MemberStatus::Active->value)
            ->whereIn('designation_id', $designationIds)
            ->pluck('user_id')
            ->all();
    }

    /**
     * @return list<int>
     */
    private function optedInDonorUserIds(): array
    {
        return Donor::query()
            ->whereNotNull('user_id')
            ->where('marketing_opt_in', true)
            ->pluck('user_id')
            ->all();
    }
}
