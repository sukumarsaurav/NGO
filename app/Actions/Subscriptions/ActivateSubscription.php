<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionActivatedMail;
use App\Models\Subscription;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Mail;

/**
 * webhook: subscription.activated — donor completed bank/UPI authorisation.
 * See docs/modules/M06-recurring-autopay.md step 5. Idempotent: a duplicate
 * webhook finds the subscription already active and no-ops, same shape as
 * RecordSuccessfulDonation.
 */
final class ActivateSubscription
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function handle(string $providerSubscriptionId): Subscription
    {
        return $this->db->transaction(function () use ($providerSubscriptionId) {
            $subscription = Subscription::query()
                ->where('provider_subscription_id', $providerSubscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($subscription->status === SubscriptionStatus::Active) {
                return $subscription;
            }

            $now = now();

            $subscription->update([
                'status' => SubscriptionStatus::Active->value,
                'started_at' => $subscription->started_at ?? $now,
                'next_charge_at' => $subscription->interval->addTo($now),
            ]);

            $subscription = $subscription->fresh();

            Mail::to($subscription->donor->email)->queue(new SubscriptionActivatedMail($subscription));

            return $subscription;
        });
    }
}
