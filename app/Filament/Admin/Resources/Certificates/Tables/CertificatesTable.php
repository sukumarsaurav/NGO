<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Certificates\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('issuing_authority')->placeholder('—'),
                TextColumn::make('valid_until')->date()->placeholder('—'),
                IconColumn::make('is_published')->boolean(),
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
