<?php

declare(strict_types=1);

use App\Enums\ReceiptSeries;
use App\Services\Numbering\ReceiptNumberGenerator;
use Carbon\CarbonImmutable;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('resets the donation receipt sequence to 1 when the financial year rolls over on 1 April', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-31 23:00:00', 'Asia/Kolkata'));

    $generator = app(ReceiptNumberGenerator::class);
    $lastOfOldYear = $generator->next(ReceiptSeries::Donation);

    expect($lastOfOldYear->financialYear)->toBe('2025-26');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01 00:30:00', 'Asia/Kolkata'));

    $firstOfNewYear = $generator->next(ReceiptSeries::Donation);

    expect($firstOfNewYear->financialYear)->toBe('2026-27')
        ->and($firstOfNewYear->sequenceNumber)->toBe(1);
});

it('keeps 80G and donation series numbering independent within the same financial year', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 12:00:00', 'Asia/Kolkata'));

    $generator = app(ReceiptNumberGenerator::class);

    $donation1 = $generator->next(ReceiptSeries::Donation);
    $eightyG1 = $generator->next(ReceiptSeries::EightyG);
    $donation2 = $generator->next(ReceiptSeries::Donation);

    expect($donation1->sequenceNumber)->toBe(1)
        ->and($donation2->sequenceNumber)->toBe(2)
        ->and($eightyG1->sequenceNumber)->toBe(1);
});
