<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\MandateType;
use App\Enums\SubscriptionInterval;
use App\Models\Donor;
use App\Models\Subscription;
use App\Services\Payment\DTOs\MandateRequest;
use App\Services\Payment\PaymentGateway;
use App\Support\Money;
use InvalidArgumentException;

/**
 * Step 2 of the setup flow — see docs/modules/M06-recurring-autopay.md.
 * Resolves/creates the Donor, validates the per-cycle amount against the
 * mandate type's ceiling *before* the donor ever leaves the site, asks the
 * gateway for a mandate, and stores the subscription row (status:
 * pending_authentication — the donor hasn't completed bank/UPI auth yet).
 */
final class CreateSubscriptionMandate
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function handle(
        string $donorName,
        string $donorEmail,
        ?string $donorPhone,
        Money $amount,
        SubscriptionInterval $interval = SubscriptionInterval::Monthly,
        MandateType $mandateType = MandateType::UpiAutopay,
        ?int $totalCycles = null,
        ?int $campaignId = null,
    ): Subscription {
        $ceiling = $mandateType->perTransactionCeilingPaise();

        if ($ceiling !== null && $amount->toPaise() > $ceiling) {
            throw new InvalidArgumentException(
                'This amount exceeds the '.Money::fromPaise($ceiling)->toRupees()
                ." per-transaction ceiling for {$mandateType->label()}. Choose e-mandate for larger recurring gifts."
            );
        }

        $donor = Donor::findByEmail($donorEmail) ?? Donor::query()->create([
            'name' => $donorName,
            'email' => trim(mb_strtolower($donorEmail)),
            'phone' => $donorPhone,
        ]);

        $mandate = $this->gateway->createMandate(new MandateRequest(
            donorEmail: $donorEmail,
            donorName: $donorName,
            donorPhone: $donorPhone,
            amount: $amount,
            frequency: $interval->value,
            totalCount: $totalCycles ?? 120, // Razorpay requires a bound; 120 monthly cycles = 10 years, effectively "until cancelled"
        ));

        return Subscription::query()->create([
            'donor_id' => $donor->id,
            'campaign_id' => $campaignId,
            'provider' => 'razorpay',
            'provider_subscription_id' => $mandate->subscriptionId,
            'amount' => $amount->toPaise(),
            'interval' => $interval->value,
            'total_cycles' => $totalCycles,
            'status' => 'pending_authentication',
            'mandate_type' => $mandateType->value,
            'raw_response' => $mandate->raw,
        ]);
    }
}
