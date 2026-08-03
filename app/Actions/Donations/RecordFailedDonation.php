<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Events\DonationFailed;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use App\Services\Payment\DTOs\PaymentResult;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Same idempotency shape as RecordSuccessfulDonation. A donation that has
 * already succeeded is never downgraded to failed by a late/duplicate
 * webhook — succeeded is a one-way door.
 */
final class RecordFailedDonation
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(string $orderId, PaymentResult $result): Donation
    {
        return $this->db->transaction(function () use ($orderId, $result) {
            $transaction = PaymentTransaction::query()
                ->where('provider_order_id', $orderId)
                ->first();

            if (! $transaction) {
                throw new RuntimeException("No payment_transactions row for order '{$orderId}'.");
            }

            $donation = Donation::query()->whereKey($transaction->donation_id)->lockForUpdate()->firstOrFail();

            if (in_array($donation->status, [DonationStatus::Succeeded, DonationStatus::Failed], true)) {
                return $donation;
            }

            $donation->update(['status' => DonationStatus::Failed->value]);

            $transaction->update([
                'status' => 'failed',
                'error_code' => $result->errorCode,
                'error_description' => $result->errorDescription,
                'raw_response' => $result->raw,
            ]);

            $donation = $donation->fresh();

            DonationFailed::dispatch($donation, $result->errorCode, $result->errorDescription);

            return $donation;
        });
    }
}
