<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Models\Member;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class MemberRosterExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
{
    use HasReportHeader;

    /**
     * @param  list<int>|null  $departmentIds
     */
    public function __construct(
        private readonly ?array $departmentIds = null,
    ) {}

    public function query(): Builder
    {
        return app(ReportQueries::class)->memberRoster($this->departmentIds);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Code', 'Name', 'Department', 'Designation', 'Status', 'Joined', 'ID Card Valid Until'];
    }

    /**
     * @return list<mixed>
     */
    public function map($member): array
    {
        /** @var Member $member */
        return [
            $member->member_code,
            $member->user->name,
            $member->department ? $member->department->name : '—',
            $member->designation ? $member->designation->title : '—',
            $member->status->label(),
            $member->joined_on?->format('d M Y') ?? '—',
            $member->valid_until?->format('d M Y') ?? '—',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Member Roster';
    }

    public function reportRange(): string
    {
        return $this->departmentIds === null ? 'All departments' : 'Scoped to managed department(s)';
    }
}
