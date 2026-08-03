<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Campaigns\Tables;

use App\Enums\CampaignStatus;
use App\Models\CampaignCategory;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * "View campaigns: Read-only" — see docs/modules/M11-manager-panel.md's
 * capability table. No create/edit/publish/delete actions.
 */
class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('category.name')->label('Cause'),
                TextColumn::make('status')->badge(),
                TextColumn::make('raised_amount')->label('Raised')->money('inr', divideBy: 100)->sortable(),
                TextColumn::make('goal_amount')->label('Goal')->money('inr', divideBy: 100)->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CampaignStatus::class),
                SelectFilter::make('category_id')->label('Cause')
                    ->options(fn () => CampaignCategory::query()->pluck('name', 'id')),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
