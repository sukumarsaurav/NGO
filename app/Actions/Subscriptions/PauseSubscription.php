<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Payment\PaymentGateway;
use InvalidArgumentException;

/**
 * Pause is resumable — the donor's mandate stays live at the gateway but
 * stops charging. See docs/modules/M06-recurring-autopay.md's donor
 * self-service section.
 */
final class PauseSubscription
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function handle(Subscription $subscription): Subscription
    {
        $this->guardTransition($subscription, SubscriptionStatus::Paused);

        $this->gateway->pauseMandate((string) $subscription->provider_subscription_id);

        $subscription->update(['status' => SubscriptionStatus::Paused->value]);

        return $subscription->fresh();
    }

    public function resume(Subscription $subscription): Subscription
    {
        $this->guardTransition($subscription, SubscriptionStatus::Active);

        $this->gateway->resumeMandate((string) $subscription->provider_subscription_id);

        $subscription->update(['status' => SubscriptionStatus::Active->value]);

        return $subscription->fresh();
    }

    private function guardTransition(Subscription $subscription, SubscriptionStatus $target): void
    {
        if (! $subscription->status->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Cannot transition a subscription from {$subscription->status->value} to {$target->value}."
            );
        }
    }
}
