<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Models\Campaign;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class CampaignPerformanceExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    public function query(): Builder
    {
        return app(ReportQueries::class)->campaignPerformance();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Campaign', 'Goal', 'Raised', '% Funded', 'Donors', 'Avg Gift', 'Days Active', 'Status'];
    }

    /**
     * @return list<mixed>
     */
    public function map($campaign): array
    {
        /** @var Campaign $campaign */
        $raised = $campaign->displayedRaisedAmount();
        $percent = $campaign->goal_amount > 0 ? round(($raised / $campaign->goal_amount) * 100, 1) : 0;
        $avgGift = $campaign->donor_count > 0 ? round($raised / $campaign->donor_count / 100, 2) : 0;
        $daysActive = $campaign->starts_at ? $campaign->starts_at->diffInDays(now()) : 0;

        return [
            $campaign->title,
            $campaign->goal_amount / 100,
            $raised / 100,
            $percent,
            $campaign->donor_count,
            $avgGift,
            $daysActive,
            $campaign->status->label(),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Campaign Performance';
    }

    public function reportRange(): string
    {
        return 'All campaigns';
    }
}
