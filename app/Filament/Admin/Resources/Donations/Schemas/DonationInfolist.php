<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donations\Schemas;

use App\Enums\DonationStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class DonationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextEntry::make('donation_number')->placeholder('—'),
                        TextEntry::make('donor.name')->label('Donor'),
                        TextEntry::make('amount')->money('inr', divideBy: 100),
                        TextEntry::make('status')->badge()->color(fn (DonationStatus $state) => $state->color())->formatStateUsing(fn (DonationStatus $state) => $state->label()),
                        TextEntry::make('payment_mode')->placeholder('—')->formatStateUsing(fn ($state) => $state?->label()),
                        TextEntry::make('financial_year'),
                        TextEntry::make('donated_at')->dateTime()->placeholder('—'),
                        TextEntry::make('is_offline')->label('Offline?')->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('eligible_for_80g')->label('80G eligible?')->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('message')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
