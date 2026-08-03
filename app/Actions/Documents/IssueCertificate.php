<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\DocumentTemplate;
use App\Models\IssuedDocument;
use App\Models\Member;

final class IssueCertificate
{
    public function __construct(
        private readonly IssueDocument $issueDocument,
    ) {}

    public function handle(Member $member, string $title, int $issuedByUserId, ?DocumentTemplate $template = null): IssuedDocument
    {
        return $this->issueDocument->handle(
            member: $member,
            type: DocumentType::Certificate,
            title: $title,
            issuedByUserId: $issuedByUserId,
            template: $template,
        );
    }
}
