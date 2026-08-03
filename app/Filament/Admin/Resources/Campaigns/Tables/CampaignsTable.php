<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Tables;

use App\Actions\Campaigns\PublishCampaign;
use App\Enums\CampaignStatus;
use App\Models\CampaignCategory;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('category.name')->label('Cause'),
                TextColumn::make('status')->badge(),
                ViewColumn::make('progress')
                    ->label('Progress')
                    ->view('filament.admin.tables.columns.campaign-progress'),
                TextColumn::make('raised_amount')->label('Raised')->money('inr', divideBy: 100)->sortable(),
                TextColumn::make('goal_amount')->label('Goal')->money('inr', divideBy: 100)->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                IconColumn::make('is_urgent')->label('Urgent')->boolean(),
                TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CampaignStatus::class),
                SelectFilter::make('category_id')->label('Cause')
                    ->options(fn () => CampaignCategory::query()->pluck('name', 'id')),
                TernaryFilter::make('is_featured'),
                Filter::make('funded_100')
                    ->label('100%+ funded')
                    ->query(fn (Builder $query) => $query->whereColumn('raised_amount', '>=', 'goal_amount')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publish')
                    ->icon('heroicon-o-megaphone')
                    ->visible(fn ($record) => in_array($record->status, [CampaignStatus::Draft, CampaignStatus::PendingReview], true))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            app(PublishCampaign::class)->handle($record);
                            Notification::make()->title('Campaign published')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not publish')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('toggleFeatured')
                    ->label(fn ($record) => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon('heroicon-o-star')
                    ->action(fn ($record) => $record->update(['is_featured' => ! $record->is_featured])),
                DeleteAction::make(),
            ])
            // Drag-to-reorder controls the homepage featured carousel and the
            // recent-campaigns grid order — see M08's "Featured-campaign
            // carousel management". Filament only reorders while unsorted by
            // a column, so this only takes effect once a user clicks the
            // reorder toggle in the table header.
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
