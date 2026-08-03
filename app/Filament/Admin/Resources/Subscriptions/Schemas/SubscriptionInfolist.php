<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->components([
                        TextEntry::make('donor.name')->label('Donor'),
                        TextEntry::make('amount')->money('inr', divideBy: 100),
                        TextEntry::make('interval')->formatStateUsing(fn ($state) => $state->label()),
                        TextEntry::make('status')->badge()->color(fn (SubscriptionStatus $state) => $state->color())->formatStateUsing(fn (SubscriptionStatus $state) => $state->label()),
                        TextEntry::make('completed_cycles')->label('Cycles completed'),
                        TextEntry::make('total_collected')->money('inr', divideBy: 100),
                        TextEntry::make('next_charge_at')->dateTime()->placeholder('—'),
                        TextEntry::make('failed_charge_count')->label('Consecutive failures'),
                    ]),

                RepeatableEntry::make('charges')
                    ->label('Charge history')
                    ->schema([
                        Grid::make(4)->components([
                            TextEntry::make('cycle_number')->label('Cycle'),
                            TextEntry::make('amount')->money('inr', divideBy: 100),
                            TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                            TextEntry::make('charged_at')->dateTime()->placeholder('—'),
                        ]),
                    ]),
            ]);
    }
}
