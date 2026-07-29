<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Designation;
use App\Models\Member;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Listener `IssueLetterOnDesignationAssigned` (M04, Sprint 4) hooks this to
 * trigger an appointment letter. No listener is registered yet.
 */
final class DesignationAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Member $member,
        public readonly Designation $designation,
    ) {}
}
