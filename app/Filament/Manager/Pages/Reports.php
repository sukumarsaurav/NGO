<?php

declare(strict_types=1);

namespace App\Filament\Manager\Pages;

use App\Exports\DocumentIssueLogExport;
use App\Exports\MemberRosterExport;
use App\Services\Reports\ReportQueries;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Manager-scoped versions of member and document reports" — see
 * docs/modules/M12-reports-analytics.md. Reuses the exact same
 * `ReportQueries` methods as the Admin Reports page, just with
 * `managedDepartmentIds()` passed in — never a second copy of the query.
 * "The scope is stated visibly on the report header so they know what
 * they're looking at" — rendered in the view.
 */
class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected string $view = 'filament.manager.pages.reports';

    public string $activeReport = 'member_roster';

    public int $page = 1;

    public static function canAccess(array $parameters = []): bool
    {
        return Auth::user()?->can('view_reports') ?? false;
    }

    public function selectReport(string $key): void
    {
        $this->activeReport = $key;
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function results(): LengthAwarePaginator
    {
        $departmentIds = Auth::user()->managedDepartmentIds();
        $queries = app(ReportQueries::class);

        return match ($this->activeReport) {
            'member_roster' => $queries->memberRoster($departmentIds)->paginate(25, page: $this->page),
            'document_issue_log' => $queries->documentIssueLog($departmentIds)->paginate(25, page: $this->page),
            default => throw new InvalidArgumentException("Unknown report '{$this->activeReport}'."),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => $this->export(ExcelFormat::CSV, 'csv')),

            Action::make('exportXlsx')
                ->label('Export XLSX')
                ->icon(Heroicon::OutlinedTableCells)
                ->action(fn () => $this->export(ExcelFormat::XLSX, 'xlsx')),
        ];
    }

    private function export(string $writerType, string $extension): BinaryFileResponse
    {
        $departmentIds = Auth::user()->managedDepartmentIds();

        $export = match ($this->activeReport) {
            'member_roster' => new MemberRosterExport($departmentIds),
            'document_issue_log' => new DocumentIssueLogExport($departmentIds),
            default => throw new InvalidArgumentException("Unknown report '{$this->activeReport}'."),
        };

        return Excel::download($export, "{$this->activeReport}.{$extension}", $writerType);
    }
}
