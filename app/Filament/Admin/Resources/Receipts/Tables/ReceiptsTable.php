<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Receipts\Tables;

use App\Actions\Receipts\CancelReceipt;
use App\Actions\Receipts\ReissueReceipt;
use App\Enums\ReceiptSeries;
use App\Mail\DonationThankYouMail;
use App\Models\Receipt;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')->searchable()->copyable(),
                TextColumn::make('series')->badge()->formatStateUsing(fn (ReceiptSeries $state) => $state->value === '80g' ? '80G' : 'Donation'),
                TextColumn::make('donor.name')->label('Donor')->searchable(),
                TextColumn::make('amount')->money('inr', divideBy: 100)->sortable(),
                TextColumn::make('financial_year')->label('FY'),
                TextColumn::make('issued_on')->date()->sortable(),
                TextColumn::make('email_status')->badge(),
                IconColumn::make('is_cancelled')->label('Cancelled')->boolean(),
            ])
            ->filters([
                SelectFilter::make('series')->options(['donation' => 'Donation', '80g' => '80G']),
                SelectFilter::make('financial_year')
                    ->label('FY')
                    ->options(fn () => Receipt::query()->distinct()->pluck('financial_year', 'financial_year')),
                SelectFilter::make('email_status')->options([
                    'pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed', 'bounced' => 'Bounced',
                ]),
                TernaryFilter::make('is_cancelled')->label('Cancelled only'),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Receipt $record) => (bool) $record->file_path)
                    ->action(function (Receipt $record) {
                        $record->increment('download_count');
                        $filename = str_replace('/', '-', $record->receipt_number);

                        return Storage::disk('local')->download($record->file_path, "{$filename}.pdf");
                    }),

                Action::make('reemail')
                    ->label('Re-email')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (Receipt $record) => (bool) $record->file_path)
                    ->action(function (Receipt $record): void {
                        Mail::to($record->donor->email)->queue(new DonationThankYouMail($record));
                        $record->update(['email_status' => 'sent', 'emailed_at' => now()]);
                        Notification::make()->title('Receipt re-emailed')->success()->send();
                    }),

                Action::make('cancel')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Receipt $record) => ! $record->is_cancelled)
                    ->schema([
                        Textarea::make('reason')->required(),
                    ])
                    ->action(function (Receipt $record, array $data): void {
                        try {
                            app(CancelReceipt::class)->handle($record, $data['reason']);
                            Notification::make()->title('Receipt cancelled — number retained, never reused')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not cancel')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('reissue')
                    ->label('Cancel & reissue')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')->label('Reason for reissue')->required(),
                    ])
                    ->action(function (Receipt $record, array $data): void {
                        $new = app(ReissueReceipt::class)->handle($record, $data['reason']);
                        Notification::make()->title("Reissued as {$new->receipt_number}")->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('reemailFailed')
                        ->label('Re-email failed deliveries')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->action(function (Collection $records): void {
                            $count = 0;

                            /** @var Receipt $receipt */
                            foreach ($records as $receipt) {
                                if (in_array($receipt->email_status, ['failed', 'bounced'], true) && $receipt->file_path) {
                                    Mail::to($receipt->donor->email)->queue(new DonationThankYouMail($receipt));
                                    $receipt->update(['email_status' => 'sent', 'emailed_at' => now()]);
                                    $count++;
                                }
                            }

                            Notification::make()->title("{$count} receipt(s) re-emailed")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
