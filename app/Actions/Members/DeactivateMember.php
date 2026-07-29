<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Enums\MemberStatus;
use App\Models\Member;
use InvalidArgumentException;

/**
 * Named for its most common use, but this is genuinely the general
 * status-transition handler — pending->active (approve), active->suspended,
 * active/suspended->resigned, any->expired, and suspended->active
 * (reinstate). See the state machine in docs/modules/M03-members.md; there is
 * no separate "ApproveMember" action in that doc, and this is where that
 * transition lives.
 *
 * `active` is the only status with portal access — everything else flips
 * `users.is_active = false`, which LoginRequest folds into the credential
 * lookup (see app/Http/Requests/Auth/LoginRequest.php). A suspension is not
 * necessarily permanent: already-issued documents stay valid until explicitly
 * revoked (M04), only portal login is affected.
 */
final class DeactivateMember
{
    public function handle(Member $member, MemberStatus $targetStatus, ?string $reason = null): Member
    {
        if (! $member->status->canTransitionTo($targetStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition a member from {$member->status->value} to {$targetStatus->value}."
            );
        }

        $member->update([
            'status' => $targetStatus->value,
            'notes' => $reason
                ? trim(($member->notes ?? '')."\n[".now()->toDateTimeString()."] {$targetStatus->label()}: {$reason}")
                : $member->notes,
        ]);

        // Direct assignment, not update(['is_active' => ...]) — `is_active` is
        // deliberately NOT in User's #[Fillable(...)] list (it's security-
        // sensitive; mass-assignment should never reach it from a public
        // form). This is trusted, internal, admin-triggered code, so it sets
        // the attribute directly and saves rather than widening the fillable
        // surface for every other caller of User::update().
        $member->user->is_active = $targetStatus === MemberStatus::Active;
        $member->user->save();

        return $member->fresh();
    }
}
