<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Posts\Tables;

use App\Enums\PostCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('category')->badge(),
                TextColumn::make('author.name')->label('Author'),
                TextColumn::make('published_at')->dateTime()->sortable(),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('view_count')->label('Views')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->options(PostCategory::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
