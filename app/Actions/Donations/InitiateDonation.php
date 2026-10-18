<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Models\Donation;
use App\Models\Donor;
use App\Services\Payment\DTOs\InitiateDonationResult;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\PaymentGateway;
use App\Support\Money;

/**
 * Step 3 of the online donation flow — see docs/modules/M05-donations-payments.md.
 * Resolves/creates the Donor, creates the Donation (status: pending), asks
 * the gateway for an order, and records the first PaymentTransaction attempt.
 *
 * The amount here must already be server-validated Money — this action
 * trusts its caller on that point, but re-checks the settings floor itself
 * as a second line of defence (see "server-side amount validation").
 *
 * `$amount` is the free-form (non-catalogue) portion of the gift. `$items`
 * carries the needs-catalogue selection as `[{product_id, quantity}, ...]` —
 * see docs/modules/M08-campaigns-crowdfunding.md's "The money rules".
 * **Quantities come from the client. Money never does**: `unit_price` is
 * re-read from `campaign_products` here, never trusted from the request, so
 * a tampered price or line total is simply ignored.
 */
final class InitiateDonation
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly CreatePendingDonation $createPending,
    ) {}

    /**
     * @param  array<string, string>  $utmData
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function handle(
        string $donorName,
        string $donorEmail,
        ?string $donorPhone,
        Money $amount,
        ?int $campaignId = null,
        bool $isAnonymous = false,
        ?string $message = null,
        ?string $dedicatedTo = null,
        ?string $source = null,
        array $utmData = [],
        ?string $ipAddress = null,
        array $items = [],
    ): InitiateDonationResult {
        [$donation, $droppedProductNames] = $this->createPending->handle(
            donorName: $donorName,
            donorEmail: $donorEmail,
            donorPhone: $donorPhone,
            amount: $amount,
            campaignId: $campaignId,
            isAnonymous: $isAnonymous,
            message: $message,
            dedicatedTo: $dedicatedTo,
            source: $source,
            utmData: $utmData,
            ipAddress: $ipAddress,
            items: $items,
        );

        $totalPaise = $donation->amount;

        $totalMoney = Money::fromPaise($totalPaise);

        $order = $this->gateway->createOrder(new PaymentIntent(
            receipt: (string) $donation->uuid,
            amount: $totalMoney,
            currency: $donation->currency,
            donorEmail: $donorEmail,
            donorName: $donorName,
            donorPhone: $donorPhone,
            notes: ['donation_uuid' => (string) $donation->uuid],
        ));

        $donation->transactions()->create([
            'provider' => 'razorpay',
            'provider_order_id' => $order->orderId,
            'amount' => $totalPaise,
            'status' => 'created',
        ]);

        return new InitiateDonationResult($donation, $order, $droppedProductNames);
    }
}
