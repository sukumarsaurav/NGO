<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Actions\Members\BulkImportMembers as BulkImportMembersAction;
use App\Filament\Admin\Resources\Members\MemberResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

/**
 * Upload -> dry run -> error report -> confirm -> import. Column mapping is
 * automatic by header name (case-insensitive against
 * BulkImportMembers::EXPECTED_HEADERS), not a manual drag-and-drop mapper —
 * see the scope note on that class. See docs/modules/M03-members.md.
 *
 * @property-read Schema $form
 */
class ImportMembers extends Page
{
    protected static string $resource = MemberResource::class;

    protected string $view = 'filament.admin.resources.members.pages.import-members';

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var list<array<string, string|null>> */
    public array $rows = [];

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    public bool $imported = false;

    /**
     * Filament only auto-gates the standard CRUD page types (List/Create/Edit)
     * against the resource's policy — a bespoke page like this one does not
     * inherit that for free and needs its own check. Importing is a form of
     * creating members, so it's gated the same way.
     */
    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('create_members') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('csv')
                    ->label('Members CSV')
                    ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ->maxSize(2048)
                    ->disk('local')
                    ->directory('imports')
                    ->visibility('private')
                    ->required()
                    ->helperText('Expected headers: '.implode(', ', BulkImportMembersAction::EXPECTED_HEADERS)),
            ]);
    }

    public function parseAndPreview(): void
    {
        $state = $this->form->getState();
        $path = $state['csv'] ?? null;

        if (! $path) {
            Notification::make()->title('Choose a CSV file first.')->danger()->send();

            return;
        }

        $csv = Reader::createFromString(Storage::disk('local')->get($path));
        $csv->setHeaderOffset(0);

        $headerMap = $this->mapHeaders($csv->getHeader());

        $this->rows = [];
        foreach ($csv->getRecords() as $record) {
            $row = [];
            foreach ($headerMap as $expected => $actual) {
                $row[$expected] = $actual !== null ? ($record[$actual] ?? null) : null;
            }
            $this->rows[] = $row;
        }

        $result = app(BulkImportMembersAction::class)->handle($this->rows, dryRun: true);

        $this->summary = [
            'total' => $result->totalRows,
            'valid' => $result->validCount(),
            'errorRows' => $result->errorRowCount(),
            'errors' => collect($result->errors)
                ->map(fn ($e) => ['row' => $e->row, 'field' => $e->field, 'message' => $e->message])
                ->all(),
        ];
        $this->imported = false;
    }

    public function confirmImport(): void
    {
        if ($this->rows === []) {
            return;
        }

        $result = app(BulkImportMembersAction::class)->handle($this->rows, dryRun: false);

        $this->summary = [
            'total' => $result->totalRows,
            'valid' => $result->validCount(),
            'errorRows' => $result->errorRowCount(),
            'errors' => collect($result->errors)
                ->map(fn ($e) => ['row' => $e->row, 'field' => $e->field, 'message' => $e->message])
                ->all(),
        ];
        $this->imported = true;

        Notification::make()
            ->title(count($result->created).' members imported')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to members')
                ->url(fn () => MemberResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    /**
     * Case-insensitive header auto-matching. A CSV header of "Date of Birth"
     * or "date_of_birth" both resolve to the `date_of_birth` expected column.
     *
     * @param  string[]  $actualHeaders
     * @return array<string, string|null>
     */
    private function mapHeaders(array $actualHeaders): array
    {
        $normalise = fn (string $h) => strtolower(trim(str_replace([' ', '-'], '_', $h)));
        $normalisedActual = collect($actualHeaders)->mapWithKeys(fn ($h) => [$normalise($h) => $h]);

        return collect(BulkImportMembersAction::EXPECTED_HEADERS)
            ->mapWithKeys(fn ($expected) => [$expected => $normalisedActual->get($expected)])
            ->all();
    }
}
