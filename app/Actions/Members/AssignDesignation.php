<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Events\DesignationAssigned;
use App\Models\Designation;
use App\Models\Member;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Sets `designation_id`, fires DesignationAssigned -> triggers an appointment
 * letter once M04's listener exists (Sprint 4). See docs/modules/M03-members.md.
 */
final class AssignDesignation
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function handle(Member $member, Designation $designation): Member
    {
        $member->update(['designation_id' => $designation->id]);

        $this->events->dispatch(new DesignationAssigned($member, $designation));

        return $member->fresh();
    }
}
