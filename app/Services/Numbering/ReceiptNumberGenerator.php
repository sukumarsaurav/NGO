<?php

declare(strict_types=1);

namespace App\Services\Numbering;

use App\Enums\ReceiptSeries;
use App\Models\ReceiptSequence;
use App\Services\Settings\SettingsRepository;
use App\Support\FinancialYear;
use Illuminate\Database\ConnectionInterface;

/**
 * The single most concurrency-sensitive service in the project — see
 * docs/03-ROADMAP.md's Sprint 6 note: "100 concurrent donations produce
 * 100 unique, gap-free receipt numbers" and "a duplicated 80G receipt
 * number is a compliance failure."
 *
 * Same create-then-lock pattern as MemberCodeGenerator and
 * DocumentNumberGenerator, for the same reason: `lockForUpdate()` on a row
 * that doesn't exist yet locks nothing. The sequence row is created OUTSIDE
 * the transaction, before the lock is taken — this is what makes the
 * "receipt_sequences empty, concurrent donations on 1 April" cold-start race
 * safe too.
 */
final class ReceiptNumberGenerator
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly SettingsRepository $settings,
    ) {}

    public function next(ReceiptSeries $series): GeneratedReceiptNumber
    {
        $financialYear = FinancialYear::current()->toString();
        $prefix = $this->prefixFor($series);

        $this->ensureSequenceExists($series, $financialYear, $prefix);

        return $this->db->transaction(function () use ($series, $financialYear, $prefix) {
            $sequence = ReceiptSequence::query()
                ->where('series', $series->value)
                ->where('financial_year', $financialYear)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');
            $sequence->update(['locked_at' => now()]);

            $padding = (int) $this->settings->get('receipt.number_padding', 5);
            $number = $sequence->last_number;

            return new GeneratedReceiptNumber(
                receiptNumber: $prefix.$financialYear.'/'.str_pad((string) $number, $padding, '0', STR_PAD_LEFT),
                sequenceNumber: $number,
                financialYear: $financialYear,
            );
        });
    }

    private function prefixFor(ReceiptSeries $series): string
    {
        // Fallback defaults must differ per series — if `receipt.80g_prefix`
        // and `receipt.prefix` are both missing (e.g. an unseeded
        // environment), an identical fallback would let the two series
        // collide on the same receipt_number and violate the unique index.
        $key = $series === ReceiptSeries::EightyG ? 'receipt.80g_prefix' : 'receipt.prefix';
        $default = $series === ReceiptSeries::EightyG ? '80G/' : 'RCP/';

        return (string) $this->settings->get($key, $default);
    }

    private function ensureSequenceExists(ReceiptSeries $series, string $financialYear, string $prefix): void
    {
        // firstOrCreate() already survives the 1 April cold-start race
        // itself — Eloquent's createOrFirst() catches the unique constraint
        // violation from a concurrent insert and re-queries for the row
        // that request just created, rather than erroring.
        ReceiptSequence::query()->firstOrCreate(
            ['series' => $series->value, 'financial_year' => $financialYear],
            ['prefix' => $prefix, 'last_number' => 0]
        );
    }
}
