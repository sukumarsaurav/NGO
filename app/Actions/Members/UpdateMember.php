<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Models\Member;

/**
 * Updates profile fields. Deliberately does not touch `status` or
 * `designation_id` — those go through DeactivateMember and AssignDesignation
 * respectively, which fire the events and transition checks those changes
 * require. Field changes are logged automatically via Member::LogsActivity's
 * allow-list, not manually here. See docs/modules/M03-members.md.
 */
final class UpdateMember
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Member $member, array $data): Member
    {
        $member->fill(collect($data)->except(['status', 'designation_id', 'user_id', 'member_code'])->all());
        $member->save();

        if (array_key_exists('name', $data) || array_key_exists('email', $data) || array_key_exists('phone', $data)) {
            $member->user->fill(collect($data)->only(['name', 'email', 'phone'])->all());
            $member->user->save();
        }

        return $member->fresh();
    }
}
