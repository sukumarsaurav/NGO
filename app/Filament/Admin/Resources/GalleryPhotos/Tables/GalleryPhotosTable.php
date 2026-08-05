<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GalleryPhotos\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleryPhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public'),
                TextColumn::make('title')->placeholder('—')->searchable(),
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
