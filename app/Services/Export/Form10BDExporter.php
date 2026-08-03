<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\Donation;
use App\Models\Receipt;
use Illuminate\Support\Collection;

/**
 * The annual statement of donations filed with the Income Tax Department.
 * See docs/modules/M07-receipts-80g.md's "Form 10BD export" section — the
 * prescribed shape is Sl. No. / Pre Acknowledgement Number / ID Type / ID
 * Number / Name of donor / Address of donor / Donation Type / Mode of
 * receipt / Amount / Section code.
 *
 * "The exporter groups multiple donations from the same donor in the same
 * FY into a single row with the summed amount — that's how the form works,
 * and getting it wrong means a rejected filing." Grouping is by 80G receipt,
 * not by raw donation: only donations that actually received an 80G
 * certificate belong in the statutory filing.
 */
final class Form10BDExporter
{
    /** @var list<string> */
    public const HEADERS = [
        'Sl. No.', 'Pre Acknowledgement Number', 'ID Type', 'ID Number', 'Name of donor',
        'Address of donor', 'Donation Type', 'Mode of receipt', 'Amount', 'Section code',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(string $financialYear): Collection
    {
        $result = new Collection;
        $index = 0;

        Receipt::query()
            ->where('series', '80g')
            ->where('financial_year', $financialYear)
            ->where('is_cancelled', false)
            ->get()
            ->groupBy('donor_id')
            ->each(function (Collection $donorReceipts) use ($result, &$index) {
                $result->push($this->toForm10bdRow($donorReceipts, ++$index));
            });

        return $result;
    }

    /**
     * @param  Collection<int, Receipt>  $donorReceipts
     * @return array<string, mixed>
     */
    private function toForm10bdRow(Collection $donorReceipts, int $slNo): array
    {
        /** @var Receipt $first */
        $first = $donorReceipts->first();
        $snapshot = $first->snapshot_data;

        return [
            'Sl. No.' => $slNo,
            'Pre Acknowledgement Number' => $first->receipt_number,
            'ID Type' => 'PAN',
            'ID Number' => $snapshot['donor_pan'] ?? '',
            'Name of donor' => $snapshot['donor_name'] ?? '',
            'Address of donor' => $snapshot['donor_address'] ?? '',
            'Donation Type' => 'Others',
            'Mode of receipt' => $this->modeOfReceipt($donorReceipts),
            'Amount' => $donorReceipts->sum('amount') / 100,
            'Section code' => 'Section 80G',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function missingDonorDetails(string $financialYear): array
    {
        return Donation::query()
            ->where('financial_year', $financialYear)
            ->where('status', 'succeeded')
            ->where('eligible_for_80g', true)
            ->with('donor')
            ->get()
            ->filter(fn ($donation) => ! $donation->donor->pan
                || ! $donation->donor->address_line1
                || ! $donation->donor->city
                || ! $donation->donor->state
                || ! $donation->donor->pincode)
            ->map(fn ($donation) => [
                'donation_id' => $donation->id,
                'donor_name' => $donation->donor->name,
                'donor_email' => $donation->donor->email,
                'missing_pan' => ! $donation->donor->pan,
                'missing_address' => ! $donation->donor->address_line1,
            ])
            ->unique('donor_email')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Receipt>  $receipts
     */
    private function modeOfReceipt(Collection $receipts): string
    {
        $modes = $receipts->pluck('snapshot_data.payment_mode')->filter()->unique();

        if ($modes->count() > 1) {
            return 'Electronic modes including account payee cheque or draft';
        }

        $mode = mb_strtolower((string) $modes->first());

        return match (true) {
            str_contains($mode, 'cash') => 'Cash',
            str_contains($mode, 'cheque') => 'Electronic modes including account payee cheque or draft',
            default => 'Electronic modes including account payee cheque or draft',
        };
    }
}
