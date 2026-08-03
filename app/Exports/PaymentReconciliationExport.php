<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * "The bank statement shows ₹9,764 credited. The donation ledger shows
 * ₹10,000 received." — see docs/modules/M12-reports-analytics.md.
 * "Expected bank credit" is the sum of `net_amount`, i.e. gross minus the
 * gateway's own fee and tax — what should actually land in the bank.
 */
final class PaymentReconciliationExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    public function __construct(
        private readonly string $financialYear,
    ) {}

    public function query(): Builder
    {
        return app(ReportQueries::class)->paymentReconciliation($this->financialYear);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Date', 'Gross', 'Gateway Fee', 'Tax', 'Expected Bank Credit', 'Transaction Count'];
    }

    /**
     * @return list<mixed>
     */
    public function map($row): array
    {
        return [
            $row->captured_date,
            $row->gross / 100,
            $row->total_fee / 100,
            $row->total_tax / 100,
            $row->expected_bank_credit / 100,
            $row->transaction_count,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Payment Reconciliation';
    }

    public function reportRange(): string
    {
        return "Financial Year {$this->financialYear}";
    }
}
