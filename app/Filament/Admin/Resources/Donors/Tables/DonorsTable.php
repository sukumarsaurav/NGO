<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Donors\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DonorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->placeholder('—'),
                TextColumn::make('donation_count')->label('Donations')->sortable(),
                TextColumn::make('total_donated')->label('Lifetime total')->money('inr', divideBy: 100)->sortable(),
                IconColumn::make('is_anonymous')->label('Anon.')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('total_donated', 'desc');
    }
}
