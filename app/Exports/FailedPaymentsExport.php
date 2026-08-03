<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Models\PaymentTransaction;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class FailedPaymentsExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    public function __construct(
        private readonly string $financialYear,
    ) {}

    public function query(): Builder
    {
        return app(ReportQueries::class)->failedPayments($this->financialYear);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Date', 'Donor', 'Amount', 'Error Code', 'Description'];
    }

    /**
     * @return list<mixed>
     */
    public function map($transaction): array
    {
        /** @var PaymentTransaction $transaction */
        return [
            $transaction->created_at?->format('d M Y, H:i'),
            $transaction->donation->donor->name,
            $transaction->amount / 100,
            $transaction->error_code ?? '—',
            $transaction->error_description ?? '—',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Failed Payments';
    }

    public function reportRange(): string
    {
        return "Financial Year {$this->financialYear}";
    }
}
