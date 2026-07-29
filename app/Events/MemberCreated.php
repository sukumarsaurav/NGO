<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Member;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Listener `IssueIdCardOnMemberCreated` (M04, Sprint 4) hooks this if
 * auto-issue is enabled in settings. No listener is registered yet — the
 * document engine doesn't exist until next sprint.
 */
final class MemberCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Member $member,
    ) {}
}
