<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Banners\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('campaign.title')->label('Linked campaign')->placeholder('—'),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('starts_at')->dateTime()->placeholder('—'),
                TextColumn::make('ends_at')->dateTime()->placeholder('—'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
