<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donations\Tables;

use App\Actions\Donations\RefundDonation;
use App\Enums\DonationStatus;
use App\Enums\PaymentMode;
use App\Models\Donation;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
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
            ])
            ->recordActions([
                ViewAction::make(),

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
}
