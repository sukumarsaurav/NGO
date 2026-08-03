<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscribers\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->searchable(),
                TextColumn::make('name')->searchable()->placeholder('—'),
                IconColumn::make('confirmed_at')->label('Confirmed')->boolean(),
                IconColumn::make('unsubscribed_at')->label('Unsubscribed')->boolean(),
                TextColumn::make('source')->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('confirmed_at')->label('Confirmed')->nullable(),
                TernaryFilter::make('unsubscribed_at')->label('Unsubscribed')->nullable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
