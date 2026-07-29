<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * The Indian financial year: 1 April to 31 March. Decides which receipt series a
 * donation lands in (docs/modules/M07-receipts-80g.md) — get this wrong and a
 * donation gets a receipt number from the wrong year's sequence, which is a
 * compliance failure discovered a year later at audit time.
 *
 * All comparisons happen in Asia/Kolkata. A UTC-stored `donated_at` rendered
 * naively can push a late-evening donation into the wrong FY.
 */
final class FinancialYear
{
    private const TIMEZONE = 'Asia/Kolkata';

    private function __construct(
        private readonly int $startYear,
    ) {}

    public static function for(DateTimeInterface|string $date): self
    {
        $carbon = $date instanceof DateTimeInterface
            ? CarbonImmutable::instance($date)->setTimezone(self::TIMEZONE)
            : CarbonImmutable::parse($date, self::TIMEZONE);

        // April (month 4) onward belongs to the FY starting this calendar year.
        // January-March belongs to the FY that started the previous calendar year.
        $startYear = $carbon->month >= 4 ? $carbon->year : $carbon->year - 1;

        return new self($startYear);
    }

    public static function current(): self
    {
        return self::for(CarbonImmutable::now(self::TIMEZONE));
    }

    /** e.g. "2026-27" */
    public function toString(): string
    {
        $endYearShort = str_pad((string) (($this->startYear + 1) % 100), 2, '0', STR_PAD_LEFT);

        return sprintf('%d-%s', $this->startYear, $endYearShort);
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::create($this->startYear, 4, 1, 0, 0, 0, self::TIMEZONE);
    }

    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::create($this->startYear + 1, 3, 31, 23, 59, 59, self::TIMEZONE);
    }

    public function contains(DateTimeInterface|string $date): bool
    {
        $other = self::for($date);

        return $other->startYear === $this->startYear;
    }

    public function equals(self $other): bool
    {
        return $this->startYear === $other->startYear;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
