<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Donations\Tables;

use App\Enums\DonationStatus;
use App\Enums\PaymentMode;
use App\Models\Donation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * "View donations: Read-only, all — no edit, no refund" — see
 * docs/modules/M11-manager-panel.md's capability table. No `department_id`
 * on donations, so there's nothing to scope; the permission grant alone
 * (`view_donations`, no `refund_donations`) is what limits this to viewing.
 */
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
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
