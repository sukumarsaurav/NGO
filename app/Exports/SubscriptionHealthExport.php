<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Models\Subscription;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class SubscriptionHealthExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    public function query(): Builder
    {
        return app(ReportQueries::class)->subscriptionHealth();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Donor', 'Amount', 'Interval', 'Status', 'Completed Cycles', 'Total Collected', 'Next Charge', 'Failed Charges'];
    }

    /**
     * @return list<mixed>
     */
    public function map($subscription): array
    {
        /** @var Subscription $subscription */
        return [
            $subscription->donor->name,
            $subscription->amount / 100,
            $subscription->interval->label(),
            $subscription->status->label(),
            $subscription->completed_cycles,
            $subscription->total_collected / 100,
            $subscription->next_charge_at?->format('d M Y') ?? '—',
            $subscription->failed_charge_count,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Subscription Health';
    }

    public function reportRange(): string
    {
        return 'All active and past subscriptions';
    }
}
