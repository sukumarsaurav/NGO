<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Events\DonationRefunded;
use App\Models\Donation;
use App\Services\Payment\PaymentGateway;
use App\Support\Money;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * Full or partial refund of a succeeded donation. See the "partial refund" /
 * "full refund" edge cases in docs/modules/M05-donations-payments.md — a
 * partial refund leaves the donation `succeeded`, only a full refund flips
 * it to `refunded`. Receipt cancellation/reissue is M07's job, not this
 * action's; it only moves money and updates the ledger.
 */
final class RefundDonation
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(Donation $donation, Money $amount): Donation
    {
        if ($donation->status !== DonationStatus::Succeeded) {
            throw new InvalidArgumentException(
                "Only a succeeded donation can be refunded; this one is '{$donation->status->value}'."
            );
        }

        $transaction = $donation->transactions()->where('status', 'captured')->latest()->firstOrFail();

        if ($amount->toPaise() > ($transaction->amount - $transaction->refund_amount)) {
            throw new InvalidArgumentException('Refund amount exceeds the remaining refundable amount.');
        }

        $result = $this->gateway->refund((string) $transaction->provider_payment_id, $amount);

        return $this->db->transaction(function () use ($donation, $transaction, $amount, $result) {
            $locked = Donation::query()->whereKey($donation->id)->lockForUpdate()->firstOrFail();

            $newRefundTotal = $transaction->refund_amount + $amount->toPaise();
            $isFullRefund = $newRefundTotal >= $transaction->amount;

            $transaction->update([
                'status' => 'refunded',
                'refund_amount' => $newRefundTotal,
                'refunded_at' => now(),
                'raw_response' => $result->raw,
            ]);

            if ($isFullRefund) {
                $locked->update(['status' => DonationStatus::Refunded->value]);
                DonationRefunded::dispatch($locked->fresh());
            }

            return $locked->fresh();
        });
    }
}
