<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Donation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired only when a donation's refund is a *full* refund — i.e. the
 * donation's status has just flipped away from `succeeded` to `refunded`.
 * A partial refund leaves the donation `succeeded` and does not fire this
 * (see `App\Actions\Donations\RefundDonation`'s docblock). Consumed by
 * `App\Listeners\DecrementCampaignTotalsOnRefund` — see
 * docs/modules/M08-campaigns-crowdfunding.md's "Refund decrements totals".
 */
final class DonationRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Donation $donation,
    ) {}
}
