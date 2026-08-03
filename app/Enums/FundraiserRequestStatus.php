<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * new ──► under_review ──┬──► approved  (creates a draft campaign, notifies requester)
 *                        └──► rejected  (courteous email with the reason)
 *
 * See docs/modules/M08-campaigns-crowdfunding.md's "Fundraiser requests".
 */
enum FundraiserRequestStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::UnderReview => 'Under review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
