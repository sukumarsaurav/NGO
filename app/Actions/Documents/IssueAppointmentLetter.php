<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\DocumentTemplate;
use App\Models\IssuedDocument;
use App\Models\Member;
use InvalidArgumentException;

final class IssueAppointmentLetter
{
    public function __construct(
        private readonly IssueDocument $issueDocument,
    ) {}

    public function handle(Member $member, int $issuedByUserId, ?DocumentTemplate $template = null): IssuedDocument
    {
        if (! $member->designation) {
            throw new InvalidArgumentException('Cannot issue an appointment letter for a member with no designation.');
        }

        return $this->issueDocument->handle(
            member: $member,
            type: DocumentType::AppointmentLetter,
            title: "Appointment letter — {$member->designation->title}",
            issuedByUserId: $issuedByUserId,
            template: $template ?? $member->designation->letterTemplate,
        );
    }
}
