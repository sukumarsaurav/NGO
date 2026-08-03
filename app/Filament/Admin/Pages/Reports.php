<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Exports\CampaignPerformanceExport;
use App\Exports\DocumentIssueLogExport;
use App\Exports\DonationLedgerExport;
use App\Exports\DonorSummaryExport;
use App\Exports\FailedPaymentsExport;
use App\Exports\MemberRosterExport;
use App\Exports\PaymentReconciliationExport;
use App\Exports\SubscriptionHealthExport;
use App\Services\Reports\ReportQueries;
use App\Support\FinancialYear;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/**
 * The eight reports from docs/modules/M12-reports-analytics.md that aren't
 * already covered by the Receipts section's own page (receipt register,
 * gap detection, Form 10BD — see ReceiptReports.php). One page, one tab
 * per report, so the FY picker and export actions are shared chrome
 * rather than duplicated eight times.
 */
class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reports';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected string $view = 'filament.admin.pages.reports';

    public string $activeReport = 'donation_ledger';

    public string $financialYear = '';

    public int $page = 1;

    /** @var list<array{key: string, label: string, fyScoped: bool}> */
    public array $reportTabs = [
        ['key' => 'donation_ledger', 'label' => 'Donation Ledger', 'fyScoped' => true],
        ['key' => 'donor_summary', 'label' => 'Donor Summary', 'fyScoped' => true],
        ['key' => 'subscription_health', 'label' => 'Subscription Health', 'fyScoped' => false],
        ['key' => 'campaign_performance', 'label' => 'Campaign Performance', 'fyScoped' => false],
        ['key' => 'member_roster', 'label' => 'Member Roster', 'fyScoped' => false],
        ['key' => 'document_issue_log', 'label' => 'Document Issue Log', 'fyScoped' => false],
        ['key' => 'payment_reconciliation', 'label' => 'Payment Reconciliation', 'fyScoped' => true],
        ['key' => 'failed_payments', 'label' => 'Failed Payments', 'fyScoped' => true],
    ];

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public function mount(): void
    {
        $this->financialYear = FinancialYear::current()->toString();
    }

    public function selectReport(string $key): void
    {
        $this->activeReport = $key;
        $this->page = 1;
    }

    public function selectFinancialYear(string $fy): void
    {
        $this->financialYear = $fy;
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function results(): LengthAwarePaginator
    {
        $queries = app(ReportQueries::class);

        return match ($this->activeReport) {
            'donation_ledger' => $queries->donationLedger($this->financialYear)->paginate(25, page: $this->page),
            'donor_summary' => $queries->donorSummary($this->financialYear)->paginate(25, page: $this->page),
            'subscription_health' => $queries->subscriptionHealth()->paginate(25, page: $this->page),
            'campaign_performance' => $queries->campaignPerformance()->paginate(25, page: $this->page),
            'member_roster' => $queries->memberRoster()->paginate(25, page: $this->page),
            'document_issue_log' => $queries->documentIssueLog()->paginate(25, page: $this->page),
            'payment_reconciliation' => $queries->paymentReconciliation($this->financialYear)->paginate(25, page: $this->page),
            'failed_payments' => $queries->failedPayments($this->financialYear)->paginate(25, page: $this->page),
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
        $export = match ($this->activeReport) {
            'donation_ledger' => new DonationLedgerExport($this->financialYear),
            'donor_summary' => new DonorSummaryExport($this->financialYear),
            'subscription_health' => new SubscriptionHealthExport,
            'campaign_performance' => new CampaignPerformanceExport,
            'member_roster' => new MemberRosterExport,
            'document_issue_log' => new DocumentIssueLogExport,
            'payment_reconciliation' => new PaymentReconciliationExport($this->financialYear),
            'failed_payments' => new FailedPaymentsExport($this->financialYear),
            default => throw new InvalidArgumentException("Unknown report '{$this->activeReport}'."),
        };

        return Excel::download($export, "{$this->activeReport}-{$this->financialYear}.{$extension}", $writerType);
    }
}
