<?php

declare(strict_types=1);

use App\Support\NumberToWords;

it('matches the exact acceptance-criteria example from the roadmap', function () {
    expect(NumberToWords::rupees(150050))
        ->toBe('One Thousand Five Hundred Rupees and Fifty Paise Only');
});

it('converts a round rupee amount with no paise', function () {
    expect(NumberToWords::rupees(100_00))->toBe('One Hundred Rupees Only');
});

it('converts zero rupees', function () {
    expect(NumberToWords::rupees(0))->toBe('Zero Rupees Only');
});

it('converts teens correctly, which is the classic off-by-one spot in these converters', function () {
    expect(NumberToWords::rupees(15_00))->toBe('Fifteen Rupees Only');
    expect(NumberToWords::rupees(19_00))->toBe('Nineteen Rupees Only');
});

it('converts a value crossing the hundred boundary', function () {
    expect(NumberToWords::rupees(101_00))->toBe('One Hundred One Rupees Only');
});

it('converts a value in thousands', function () {
    expect(NumberToWords::rupees(5_432_00))->toBe('Five Thousand Four Hundred Thirty Two Rupees Only');
});

it('converts a value in lakhs using Indian grouping, not international', function () {
    // 1,50,000 rupees — one lakh fifty thousand, not "one hundred fifty thousand"
    expect(NumberToWords::rupees(1_50_000_00))->toBe('One Lakh Fifty Thousand Rupees Only');
});

it('converts exactly one lakh', function () {
    expect(NumberToWords::rupees(10_000_000))->toBe('One Lakh Rupees Only');
});

it('converts a value in crores', function () {
    expect(NumberToWords::rupees(2_50_00_000_00))->toBe('Two Crore Fifty Lakh Rupees Only');
});

it('handles paise-only amounts', function () {
    expect(NumberToWords::rupees(50))->toBe('Zero Rupees and Fifty Paise Only');
});

it('handles single-digit paise without dropping the leading context', function () {
    expect(NumberToWords::rupees(100_05))->toBe('One Hundred Rupees and Five Paise Only');
});

it('rejects a negative amount', function () {
    NumberToWords::rupees(-1);
})->throws(InvalidArgumentException::class);

it('rejects an amount beyond the supported range', function () {
    NumberToWords::rupees(1_000_00_00_00_000);
})->throws(InvalidArgumentException::class);
