<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Mirrors Razorpay's own subscription state machine exactly — see
 * docs/modules/M06-recurring-autopay.md: "Do not invent a simplified status
 * set. When Razorpay says halted and our DB says active, the admin sees
 * phantom revenue and the donor gets emails about charges that aren't
 * happening."
 *
 *   created --auth--> pending_authentication --webhook: activated--> active
 *   active --> completed | paused <-> active | halted <-> active | cancelled
 *   created | pending_authentication --48h timeout--> expired
 */
enum SubscriptionStatus: string
{
    case Created = 'created';
    case PendingAuthentication = 'pending_authentication';
    case Active = 'active';
    case Paused = 'paused';
    case Halted = 'halted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::PendingAuthentication => 'Awaiting authentication',
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Halted => 'Halted',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Created, self::PendingAuthentication, self::Paused => 'warning',
            self::Halted, self::Expired => 'danger',
            self::Completed, self::Cancelled => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Created => [self::PendingAuthentication, self::Active, self::Expired, self::Cancelled],
            self::PendingAuthentication => [self::Active, self::Expired, self::Cancelled],
            self::Active => [self::Paused, self::Halted, self::Completed, self::Cancelled],
            self::Paused => [self::Active, self::Cancelled],
            self::Halted => [self::Active, self::Cancelled],
            self::Completed, self::Cancelled, self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
