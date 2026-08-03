<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Concerns\HasReportHeader;
use App\Models\IssuedDocument;
use App\Services\Reports\ReportQueries;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class DocumentIssueLogExport implements FromQuery, WithChunkReading, WithEvents, WithHeadings, WithMapping
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
        return app(ReportQueries::class)->documentIssueLog($this->departmentIds);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Document No', 'Type', 'Member', 'Issued By', 'Date', 'Status'];
    }

    /**
     * @return list<mixed>
     */
    public function map($document): array
    {
        /** @var IssuedDocument $document */
        return [
            $document->document_number,
            $document->type->label(),
            $document->member->user->name,
            $document->issuedBy ? $document->issuedBy->name : '—',
            $document->issued_on?->format('d M Y') ?? '—',
            $document->status->label(),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function reportTitle(): string
    {
        return 'Document Issue Log';
    }

    public function reportRange(): string
    {
        return $this->departmentIds === null ? 'All departments' : 'Scoped to managed department(s)';
    }
}
