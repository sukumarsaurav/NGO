<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The needs catalogue nested under a campaign — "Medicine kit · ₹900 · 11 of
 * 1500 funded". See docs/modules/M08-campaigns-crowdfunding.md.
 */
class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('unit_price')
                    ->label('Price (₹)')
                    ->numeric()->required()->minValue(1)
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('units_needed')->numeric()->required()->minValue(1),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            TextInput::make('description')->maxLength(255)->columnSpanFull(),
            FileUpload::make('image_path')->image()->maxSize(2048)->disk('public')->directory('campaign-products')->columnSpanFull(),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('unit_price')->label('Price')->money('inr', divideBy: 100),
                TextColumn::make('units_funded')->label('Funded'),
                TextColumn::make('units_needed')->label('Needed'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
