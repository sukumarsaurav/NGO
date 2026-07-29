<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Converts a paise amount into the words that appear on every 80G receipt and
 * donation receipt (docs/modules/M07-receipts-80g.md). Uses the Indian numbering
 * system — lakh (10^5) and crore (10^7), not the international million/billion
 * grouping — because that is what an Indian donor and an Indian auditor expect
 * on a legal document.
 *
 * "The three Support classes look trivial and are not." — docs/03-ROADMAP.md,
 * Sprint 1. This one appears on every legal receipt the system ever issues.
 */
final class NumberToWords
{
    /** @var string[] */
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    /** @var string[] */
    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    private const MAX_RUPEES = 999_99_99_999; // just under 1000 crore — sane upper bound for a receipt

    /**
     * @param  int  $paise  A non-negative amount in paise, as everywhere else in the system.
     */
    public static function rupees(int $paise): string
    {
        if ($paise < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }

        $wholeRupees = intdiv($paise, 100);
        $paisePart = $paise % 100;

        if ($wholeRupees > self::MAX_RUPEES) {
            throw new InvalidArgumentException('Amount exceeds the supported range for words conversion.');
        }

        $rupeeWords = $wholeRupees === 0 ? 'Zero' : self::convertIndianGrouping($wholeRupees);

        $result = "{$rupeeWords} Rupees";

        if ($paisePart > 0) {
            $result .= ' and '.self::convertBelowHundred($paisePart).' Paise';
        }

        return $result.' Only';
    }

    /**
     * Splits into crore / lakh / thousand / hundred groups per the Indian
     * numbering system: ##,##,##,###  (not the international ###,###,###).
     */
    private static function convertIndianGrouping(int $number): string
    {
        $parts = [];

        $crore = intdiv($number, 1_00_00_000);
        $number %= 1_00_00_000;

        $lakh = intdiv($number, 1_00_000);
        $number %= 1_00_000;

        $thousand = intdiv($number, 1_000);
        $number %= 1_000;

        $hundred = intdiv($number, 100);
        $remainder = $number % 100;

        if ($crore > 0) {
            $parts[] = self::convertBelowHundred($crore).' Crore';
        }

        if ($lakh > 0) {
            $parts[] = self::convertBelowHundred($lakh).' Lakh';
        }

        if ($thousand > 0) {
            $parts[] = self::convertBelowHundred($thousand).' Thousand';
        }

        if ($hundred > 0) {
            $parts[] = self::ONES[$hundred].' Hundred';
        }

        if ($remainder > 0) {
            $parts[] = self::convertBelowHundred($remainder);
        }

        return implode(' ', $parts);
    }

    private static function convertBelowHundred(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        $tens = intdiv($number, 10);
        $ones = $number % 10;

        return $ones > 0
            ? self::TENS[$tens].' '.self::ONES[$ones]
            : self::TENS[$tens];
    }
}
