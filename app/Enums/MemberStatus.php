<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * See docs/modules/M03-members.md — member lifecycle state machine:
 *
 *   pending --approve--> active --+--suspend--> suspended --reinstate--> active
 *                                 +--resign---> resigned
 *                                 +--expire---> expired (valid_until passed)
 *
 * Only `active` members get documents issued.
 */
enum MemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Resigned = 'resigned';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Resigned => 'Resigned',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Suspended, self::Expired => 'danger',
            self::Resigned => 'gray',
        };
    }

    /**
     * Only these transitions are valid. Anything else (e.g. resigned -> active)
     * must go through a fresh CreateMember, not a status flip.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Active],
            self::Active => [self::Suspended, self::Resigned, self::Expired],
            self::Suspended => [self::Active, self::Resigned],
            self::Resigned, self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
