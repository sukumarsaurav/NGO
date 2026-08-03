<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\DocumentTemplate;
use App\Models\IssuedDocument;
use App\Models\Member;

final class IssueIdCard
{
    public function __construct(
        private readonly IssueDocument $issueDocument,
    ) {}

    public function handle(Member $member, int $issuedByUserId, ?DocumentTemplate $template = null): IssuedDocument
    {
        return $this->issueDocument->handle(
            member: $member,
            type: DocumentType::IdCard,
            title: "Member ID card — {$member->member_code}",
            issuedByUserId: $issuedByUserId,
            template: $template,
            validUntil: $member->valid_until?->toDateString(),
        );
    }
}
