<?php

declare(strict_types=1);

use App\Enums\ReceiptSeries;
use App\Services\Numbering\ReceiptNumberGenerator;
use App\Support\FinancialYear;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('produces the documented format on the very first call — the cold-start case', function () {
    $number = app(ReceiptNumberGenerator::class)->next(ReceiptSeries::Donation);

    $fy = FinancialYear::current()->toString();

    expect($number->receiptNumber)->toBe("VGWGF/RCP/{$fy}/00001")
        ->and($number->sequenceNumber)->toBe(1)
        ->and($number->financialYear)->toBe($fy);
});

it('increments sequentially and never repeats within a series', function () {
    $generator = app(ReceiptNumberGenerator::class);

    $first = $generator->next(ReceiptSeries::Donation);
    $second = $generator->next(ReceiptSeries::Donation);
    $third = $generator->next(ReceiptSeries::Donation);

    expect([$first->sequenceNumber, $second->sequenceNumber, $third->sequenceNumber])->toBe([1, 2, 3]);
});

it('keeps donation and 80g series counters completely independent', function () {
    $generator = app(ReceiptNumberGenerator::class);

    $donation1 = $generator->next(ReceiptSeries::Donation);
    $eightyG1 = $generator->next(ReceiptSeries::EightyG);
    $donation2 = $generator->next(ReceiptSeries::Donation);

    expect($donation1->sequenceNumber)->toBe(1)
        ->and($eightyG1->sequenceNumber)->toBe(1)
        ->and($donation2->sequenceNumber)->toBe(2)
        ->and($eightyG1->receiptNumber)->toContain('80G');
});

it('does not collide when the sequence row already exists from a prior call — 1 April cold start', function () {
    // Simulates the row already having been created by an earlier request,
    // which is exactly the race ensureSequenceExists()'s try/catch survives.
    app(ReceiptNumberGenerator::class)->next(ReceiptSeries::Donation);

    $second = app(ReceiptNumberGenerator::class)->next(ReceiptSeries::Donation);

    expect($second->sequenceNumber)->toBe(2);
});

/**
 * NOTE: this is a sequential-loop check, not a true concurrent-process load
 * test. docs/03-ROADMAP.md's Sprint 6 acceptance criteria explicitly call
 * for "100 concurrent donations... load-tested... with ab or k6 against a
 * staging DB" — that requires a real MySQL server under real concurrent
 * connections, which this SQLite-backed local/test environment cannot
 * provide (SQLite lockForUpdate() is a no-op; true row-level locking is a
 * MySQL behaviour). This test proves the counter logic itself is correct
 * and gap-free under repeated calls; it does NOT substitute for the
 * concurrency load test the roadmap calls "the single most important test
 * in this project."
 */
it('produces 100 unique, sequential, gap-free numbers across repeated calls', function () {
    $generator = app(ReceiptNumberGenerator::class);

    $numbers = [];
    for ($i = 0; $i < 100; $i++) {
        $numbers[] = $generator->next(ReceiptSeries::Donation)->sequenceNumber;
    }

    expect($numbers)->toBe(range(1, 100))
        ->and(count(array_unique($numbers)))->toBe(100);
});
