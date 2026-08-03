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

final class DonorSummaryExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    public function __construct(
        private readonly string $financialYear,
    ) {}

    public function query(): Builder
    {
        return app(ReportQueries::class)->donorSummary($this->financialYear);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Donor', 'Total Donated', 'Donation Count', 'First Donation', 'Last Donation', 'PAN on file'];
    }

    /**
     * @return list<mixed>
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->total_donated / 100,
            $row->donation_count,
            date('d M Y', strtotime((string) $row->first_donation_at)),
            date('d M Y', strtotime((string) $row->last_donation_at)),
            $row->pan ? 'Yes' : 'No',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Donor Summary';
    }

    public function reportRange(): string
    {
        return "Financial Year {$this->financialYear}";
    }
}
