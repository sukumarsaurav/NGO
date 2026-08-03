<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

/**
 * A gateway webhook, parsed into the fields RecordSuccessfulDonation /
 * RecordFailedDonation actually need. Not to be confused with
 * App\Models\WebhookEvent, the `webhook_events` idempotency-log row — this is
 * the parsed *content* of one, before it's matched back to a `donations` row.
 */
final class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly ?string $orderId,
        public readonly ?string $paymentId,
        public readonly array $payload,
        public readonly ?string $subscriptionId = null,
    ) {}
}
