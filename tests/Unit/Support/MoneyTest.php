<?php

declare(strict_types=1);

use App\Support\Money;

it('round-trips rupees to paise exactly', function () {
    expect(Money::fromRupees(1500.50)->toPaise())->toBe(150050);
});

it('round-trips paise back to rupees', function () {
    expect(Money::fromPaise(150050)->toRupees())->toBe(1500.50);
});

it('parses a rupee amount given as a string without float precision loss', function () {
    // The classic float trap: 0.1 + 0.2 !== 0.3 in binary floating point.
    // Money must never go through float arithmetic for this reason.
    expect(Money::fromRupees('19.99')->toPaise())->toBe(1999);
    expect(Money::fromRupees('0.10')->add(Money::fromRupees('0.20'))->toPaise())->toBe(30);
});

it('rejects a negative amount', function () {
    Money::fromPaise(-100);
})->throws(InvalidArgumentException::class);

it('adds and subtracts correctly', function () {
    $a = Money::fromPaise(50000);
    $b = Money::fromPaise(25000);

    expect($a->add($b)->toPaise())->toBe(75000)
        ->and($a->subtract($b)->toPaise())->toBe(25000);
});

it('compares amounts correctly', function () {
    $small = Money::fromPaise(100);
    $large = Money::fromPaise(200);

    expect($large->greaterThan($small))->toBeTrue()
        ->and($small->lessThan($large))->toBeTrue()
        ->and($small->equals(Money::fromPaise(100)))->toBeTrue();
});

it('formats with Indian digit grouping, not international grouping', function () {
    // 12,34,567.89 — not 1,234,567.89
    expect(Money::fromPaise(123456789)->formatIndian())->toBe('₹12,34,567.89');
});

it('formats small amounts without a leading group separator', function () {
    expect(Money::fromPaise(50000)->formatIndian())->toBe('₹500.00');
    expect(Money::fromPaise(99)->formatIndian())->toBe('₹0.99');
});

it('formats exactly one lakh correctly', function () {
    expect(Money::fromPaise(100_00_000_00)->formatIndian())->toBe('₹1,00,00,000.00');
});

it('treats zero as valid, not an error', function () {
    expect(Money::zero()->isZero())->toBeTrue()
        ->and(Money::zero()->formatIndian())->toBe('₹0.00');
});
