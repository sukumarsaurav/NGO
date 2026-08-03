<?php

declare(strict_types=1);

namespace App\Actions\Receipts;

use App\Enums\DonationStatus;
use App\Enums\ReceiptSeries;
use App\Jobs\GenerateReceiptPdf;
use App\Models\Donation;
use App\Models\Receipt;
use App\Services\Numbering\ReceiptNumberGenerator;
use App\Support\NumberToWords;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * Shared core of GenerateDonationReceipt and Generate80GReceipt — the two
 * differ only in eligibility gates and what goes into `snapshot_data`, so
 * this is where the guarded-write-and-number-allocation logic lives once.
 * Same shape as IssueDocument (M04) sharing IssueIdCard/IssueAppointmentLetter.
 *
 * Guarded write: lock the donation, assert no live (non-cancelled) receipt
 * exists for this (donation, series), allocate the next revision, then
 * insert. See docs/modules/M07-receipts-80g.md's "Why receipts needs a
 * revision column" — `UNIQUE(donation_id, series)` alone would make
 * reissue-after-cancellation impossible.
 */
final class IssueReceipt
{
    public function __construct(
        private readonly ReceiptNumberGenerator $numbers,
        private readonly ConnectionInterface $db,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshotData
     */
    public function handle(Donation $donation, ReceiptSeries $series, array $snapshotData): Receipt
    {
        return $this->db->transaction(function () use ($donation, $series, $snapshotData) {
            $locked = Donation::query()->whereKey($donation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== DonationStatus::Succeeded) {
                throw new InvalidArgumentException(
                    "Cannot issue a receipt for a donation that is '{$locked->status->value}', not succeeded."
                );
            }

            $existing = Receipt::query()
                ->where('donation_id', $locked->id)
                ->where('series', $series->value)
                ->where('is_cancelled', false)
                ->first();

            if ($existing) {
                return $existing;
            }

            $nextRevision = (int) Receipt::query()
                ->where('donation_id', $locked->id)
                ->where('series', $series->value)
                ->max('revision') + 1;

            $number = $this->numbers->next($series);

            $receipt = Receipt::query()->create([
                'receipt_number' => $number->receiptNumber,
                'sequence_number' => $number->sequenceNumber,
                'series' => $series->value,
                'revision' => $nextRevision,
                'donation_id' => $locked->id,
                'donor_id' => $locked->donor_id,
                'financial_year' => $number->financialYear,
                'amount' => $locked->amount,
                'amount_in_words' => NumberToWords::rupees($locked->amount),
                'snapshot_data' => $snapshotData,
                'issued_on' => now()->toDateString(),
                'email_status' => 'pending',
                'is_cancelled' => false,
            ]);

            GenerateReceiptPdf::dispatch($receipt->id);

            return $receipt;
        });
    }
}
