<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Models\Donation;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class DonationLedgerExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    public function __construct(
        private readonly string $financialYear,
    ) {}

    public function query(): Builder
    {
        return app(ReportQueries::class)->donationLedger($this->financialYear);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Date', 'Receipt No', 'Donor', 'PAN', 'Amount', 'Mode', 'Campaign', 'Status', 'Gateway Fee', 'Net'];
    }

    /**
     * @return list<mixed>
     */
    public function map($donation): array
    {
        /** @var Donation $donation */
        $firstReceipt = $donation->receipts->first();

        return [
            $donation->donated_at?->format('d M Y'),
            $firstReceipt ? $firstReceipt->receipt_number : '—',
            $donation->donor->name,
            $donation->donor->pan ?? '—',
            $donation->amount / 100,
            $donation->payment_mode?->label() ?? '—',
            $donation->campaign ? $donation->campaign->title : '—',
            $donation->status->label(),
            $donation->gateway_fee !== null ? $donation->gateway_fee / 100 : '—',
            $donation->net_amount !== null ? $donation->net_amount / 100 : '—',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Donation Ledger';
    }

    public function reportRange(): string
    {
        return "Financial Year {$this->financialYear}";
    }
}
