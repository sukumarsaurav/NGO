<?php

declare(strict_types=1);

use App\Support\FinancialYear;

it('resolves the last day of March to the ending financial year', function () {
    expect(FinancialYear::for('2026-03-31')->toString())->toBe('2025-26');
});

it('resolves the first day of April to the new financial year', function () {
    expect(FinancialYear::for('2026-04-01')->toString())->toBe('2026-27');
});

it('resolves a date well inside the year correctly', function () {
    expect(FinancialYear::for('2026-08-15')->toString())->toBe('2026-27');
    expect(FinancialYear::for('2027-01-15')->toString())->toBe('2026-27');
});

it('handles the exact FY boundary in Asia/Kolkata, not UTC', function () {
    // 2026-03-31 23:59 IST is still FY 2025-26.
    // The same instant in UTC (18:29) is also 2026-03-31, so this alone would not
    // catch a timezone bug — the point is the boundary itself is evaluated in IST.
    expect(FinancialYear::for('2026-03-31 23:59:00')->toString())->toBe('2025-26');

    // 2026-04-01 00:01 IST is FY 2026-27.
    expect(FinancialYear::for('2026-04-01 00:01:00')->toString())->toBe('2026-27');
});

it('converts a UTC timestamp that crosses midnight IST correctly', function () {
    // 2026-03-31 19:00 UTC = 2026-04-01 00:30 IST (UTC+5:30) — FY 2026-27,
    // even though the UTC calendar date is still 31 March.
    $utcDate = new DateTimeImmutable('2026-03-31 19:00:00', new DateTimeZone('UTC'));

    expect(FinancialYear::for($utcDate)->toString())->toBe('2026-27');
});

it('reports its own start and end dates', function () {
    $fy = FinancialYear::for('2026-08-15');

    expect($fy->startsAt()->toDateString())->toBe('2026-04-01')
        ->and($fy->endsAt()->toDateString())->toBe('2027-03-31');
});

it('knows whether it contains a given date', function () {
    $fy = FinancialYear::for('2026-08-15');

    expect($fy->contains('2027-03-31'))->toBeTrue()
        ->and($fy->contains('2027-04-01'))->toBeFalse()
        ->and($fy->contains('2026-03-31'))->toBeFalse();
});

it('compares two financial years for equality', function () {
    $a = FinancialYear::for('2026-05-01');
    $b = FinancialYear::for('2027-01-01');
    $c = FinancialYear::for('2027-05-01');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
