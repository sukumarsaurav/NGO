<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * draft ──► pending_review ──► active ──┬──► completed  (goal reached or end date passed)
 *                                       ├──► paused     (temporarily stopped)
 *                                       └──► closed     (stopped permanently)
 *
 * Only `active` accepts donations. `completed` stays publicly visible — see
 * docs/modules/M08-campaigns-crowdfunding.md.
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending review',
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft, self::PendingReview => 'gray',
            self::Active => 'success',
            self::Paused => 'warning',
            self::Completed => 'info',
            self::Closed => 'danger',
        };
    }

    public function acceptsDonations(): bool
    {
        return $this === self::Active;
    }

    /**
     * Publicly visible even though it no longer accepts donations. Draft and
     * pending_review are the only statuses that 404 publicly — everything
     * that was ever live keeps returning 200. See docs/07-SEO.md §6: "Deleted
     * or closed campaigns keep returning 200 with a closing update."
     */
    public function isPubliclyVisible(): bool
    {
        return in_array($this, [self::Active, self::Paused, self::Completed, self::Closed], true);
    }
}
