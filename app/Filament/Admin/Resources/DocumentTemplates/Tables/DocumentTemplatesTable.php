<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\DocumentTemplates\Tables;

use App\Enums\DocumentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DocumentType $state) => $state->label()),
                TextColumn::make('page_size'),
                IconColumn::make('is_default')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
