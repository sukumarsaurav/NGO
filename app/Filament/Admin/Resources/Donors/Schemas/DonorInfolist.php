<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donors\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class DonorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone')->placeholder('—'),
                        TextEntry::make('donor_type')->formatStateUsing(fn ($state) => $state->label()),
                        TextEntry::make('total_donated')->label('Lifetime total')->money('inr', divideBy: 100),
                        TextEntry::make('donation_count')->label('Donations'),
                        TextEntry::make('first_donated_at')->dateTime()->placeholder('—'),
                        TextEntry::make('last_donated_at')->dateTime()->placeholder('—'),
                    ]),

                RepeatableEntry::make('donations')
                    ->label('Donation history')
                    ->schema([
                        Grid::make(4)->components([
                            TextEntry::make('donation_number')->placeholder('—'),
                            TextEntry::make('amount')->money('inr', divideBy: 100),
                            TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                            TextEntry::make('donated_at')->dateTime()->placeholder('—'),
                        ]),
                    ]),
            ]);
    }
}
