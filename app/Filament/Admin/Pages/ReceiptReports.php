<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Actions\Receipts\ExportForm10BD;
use App\Actions\Receipts\Generate80GReceipt;
use App\Models\Donation;
use App\Services\Export\Form10BDExporter;
use App\Services\Export\ReceiptReports as ReceiptReportsService;
use App\Support\FinancialYear;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use UnitEnum;

/**
 * The admin Reports section docs/modules/M07-receipts-80g.md calls for:
 * FY receipt register with totals, gap-detection report (should always be
 * empty), donors missing PAN, and the Form 10BD export.
 *
 * A single page rather than a resource — these are reports and actions
 * over existing data, not a CRUD list of their own record type.
 */
class ReceiptReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Receipt Reports';

    protected static string|UnitEnum|null $navigationGroup = 'Donations';

    protected string $view = 'filament.admin.pages.receipt-reports';

    public string $financialYear = '';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('view_receipts') ?? false;
    }

    public function mount(): void
    {
        $this->financialYear = FinancialYear::current()->toString();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function register(): Collection
    {
        return app(ReceiptReportsService::class)->register();
    }

    /**
     * @return array{donation: list<int>, '80g': list<int>}
     */
    public function gaps(): array
    {
        $service = app(ReceiptReportsService::class);

        return [
            'donation' => $service->gaps('donation', $this->financialYear),
            '80g' => $service->gaps('80g', $this->financialYear),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function missingPan(): array
    {
        return app(Form10BDExporter::class)->missingDonorDetails($this->financialYear);
    }

    public function selectFinancialYear(string $fy): void
    {
        $this->financialYear = $fy;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportForm10bd')
                ->label('Export Form 10BD (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(function () {
                    $csv = app(ExportForm10BD::class)->handle($this->financialYear);

                    return response()->streamDownload(
                        fn () => print ($csv),
                        "form-10bd-{$this->financialYear}.csv",
                        ['Content-Type' => 'text/csv']
                    );
                }),

            Action::make('bulkRegenerate80g')
                ->label('Bulk-issue 80G for this FY')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->action(function (): void {
                    $issued = 0;
                    $skipped = 0;

                    $donations = Donation::query()
                        ->where('financial_year', $this->financialYear)
                        ->where('status', 'succeeded')
                        ->where('eligible_for_80g', true)
                        ->whereDoesntHave('receipts', fn ($q) => $q->where('series', '80g')->where('is_cancelled', false))
                        ->get();

                    foreach ($donations as $donation) {
                        try {
                            app(Generate80GReceipt::class)->handle($donation);
                            $issued++;
                        } catch (InvalidArgumentException) {
                            $skipped++;
                        }
                    }

                    Notification::make()
                        ->title("{$issued} 80G receipt(s) issued".($skipped ? ", {$skipped} skipped (not eligible)" : ''))
                        ->success()
                        ->send();
                }),
        ];
    }
}
