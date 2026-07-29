<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Every monetary amount in the system is paise, as a BIGINT. This class is the
 * only place rupee <-> paise conversion and formatting happen.
 *
 * docs/01-ARCHITECTURE.md §5: "Money is stored in paise as BIGINT. Never FLOAT,
 * never DECIMAL in application code." Floating-point rupees eventually produce a
 * receipt that says ₹999.9999999, and that receipt is a legal document.
 */
final class Money
{
    private function __construct(
        private readonly int $paise,
    ) {
        if ($this->paise < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function fromPaise(int $paise): self
    {
        return new self($paise);
    }

    /**
     * Accepts a rupee amount as a string or float and converts it to paise via
     * string manipulation — never float arithmetic, which would reintroduce the
     * precision loss this class exists to prevent.
     */
    public static function fromRupees(string|float $rupees): self
    {
        $normalised = number_format((float) $rupees, 2, '.', '');
        [$whole, $fraction] = explode('.', $normalised);

        $paise = ((int) $whole) * 100 + (int) $fraction;

        return new self($paise);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function toPaise(): int
    {
        return $this->paise;
    }

    public function toRupees(): float
    {
        return $this->paise / 100;
    }

    public function add(self $other): self
    {
        return new self($this->paise + $other->paise);
    }

    public function subtract(self $other): self
    {
        return new self($this->paise - $other->paise);
    }

    public function isZero(): bool
    {
        return $this->paise === 0;
    }

    public function greaterThan(self $other): bool
    {
        return $this->paise > $other->paise;
    }

    public function lessThan(self $other): bool
    {
        return $this->paise < $other->paise;
    }

    public function equals(self $other): bool
    {
        return $this->paise === $other->paise;
    }

    /**
     * ₹12,34,567.89 — Indian digit grouping (2-2-3 after the first three digits),
     * not the international 3-3-3 grouping `number_format` produces by default.
     */
    public function formatIndian(): string
    {
        $rupees = intdiv($this->paise, 100);
        $paisePart = $this->paise % 100;

        $sign = '';
        if ($rupees < 0) {
            $sign = '-';
            $rupees = abs($rupees);
        }

        $rupeeString = (string) $rupees;
        $lastThree = substr($rupeeString, -3);
        $remaining = substr($rupeeString, 0, -3);

        if ($remaining !== '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $formatted = $remaining.','.$lastThree;
        } else {
            $formatted = $lastThree;
        }

        return sprintf('%s₹%s.%02d', $sign, $formatted, $paisePart);
    }
}
