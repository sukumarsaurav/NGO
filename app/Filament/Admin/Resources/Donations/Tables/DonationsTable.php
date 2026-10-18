<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donations\Tables;

use App\Actions\Donations\ConfirmUpiDonation;
use App\Actions\Donations\RefundDonation;
use App\Actions\Donations\RejectUpiDonation;
use App\Enums\DonationStatus;
use App\Enums\PaymentMode;
use App\Models\Donation;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class DonationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('donation_number')->label('Number')->placeholder('—')->searchable(),
                TextColumn::make('donor.name')->label('Donor')->searchable(),
                TextColumn::make('amount')->money('inr', divideBy: 100)->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (DonationStatus $state) => $state->color())
                    ->formatStateUsing(fn (DonationStatus $state) => $state->label()),
                TextColumn::make('payment_mode')->label('Mode')->placeholder('—')->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('upi_reference')->label('UPI ref')->placeholder('—')->searchable()->copyable(),
                TextColumn::make('financial_year')->label('FY'),
                IconColumn::make('is_offline')->label('Offline')->boolean(),
                TextColumn::make('donated_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(DonationStatus::class),
                SelectFilter::make('payment_mode')->options(PaymentMode::class),
                SelectFilter::make('financial_year')
                    ->options(fn () => Donation::query()->distinct()->pluck('financial_year', 'financial_year')),
                TernaryFilter::make('is_offline')->label('Offline only'),
                Filter::make('awaiting_upi')
                    ->label('Awaiting UPI verification')
                    ->query(fn (Builder $query) => $query
                        ->where('status', DonationStatus::Pending->value)
                        ->where('payment_mode', PaymentMode::Upi->value)
                        ->where('is_offline', true)),
            ])
            ->recordActions([
                ViewAction::make(),

                // A direct-UPI donation is only a claim until someone sees the money in the bank
                // statement. Confirming is what makes it count — campaign total, donor history,
                // thank-you and receipt — so the dialog shows exactly what to match against.
                Action::make('confirmUpi')
                    ->label('Confirm payment')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Donation $record) => self::isPendingUpi($record) && auth()->user()->can('reviewUpiPayment', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Confirm this UPI payment?')
                    ->modalDescription(fn (Donation $record) => sprintf(
                        'Only confirm if you can see ₹%s in the bank statement%s. Confirming counts the donation and sends the donor their receipt.',
                        number_format($record->amount / 100, 2),
                        $record->upi_reference ? " with reference {$record->upi_reference}" : ' (the donor has not reported a reference yet)',
                    ))
                    ->action(function (Donation $record): void {
                        try {
                            app(ConfirmUpiDonation::class)->handle($record, (int) auth()->id());
                            Notification::make()->title('Payment confirmed')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not confirm')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('rejectUpi')
                    ->label('Not received')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Donation $record) => self::isPendingUpi($record) && auth()->user()->can('reviewUpiPayment', $record))
                    ->modalHeading('Mark this payment as not received?')
                    ->modalDescription('Use this when no matching credit appears in the bank statement. The donation is cancelled and the donor is not counted.')
                    ->schema([
                        Textarea::make('reason')->label('Reason (kept in the notes)')->rows(2),
                    ])
                    ->action(function (Donation $record, array $data): void {
                        try {
                            app(RejectUpiDonation::class)->handle($record, $data['reason'] ?? null);
                            Notification::make()->title('Marked as not received')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not update')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('refund')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Donation $record) => $record->status === DonationStatus::Succeeded && auth()->user()->can('refund', $record))
                    ->schema([
                        TextInput::make('amount')
                            ->label('Refund amount (₹)')
                            ->numeric()
                            ->required()
                            ->default(fn (Donation $record) => $record->amount / 100),
                    ])
                    ->action(function (Donation $record, array $data): void {
                        try {
                            app(RefundDonation::class)->handle($record, Money::fromRupees((string) $data['amount']));
                            Notification::make()->title('Refund processed')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not refund')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function isPendingUpi(Donation $record): bool
    {
        return $record->status === DonationStatus::Pending
            && $record->payment_mode === PaymentMode::Upi
            && $record->is_offline;
    }
}
