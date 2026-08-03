<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FundraiserRequests\Tables;

use App\Actions\Campaigns\ApproveFundraiserRequest;
use App\Actions\Campaigns\RejectFundraiserRequest;
use App\Enums\FundraiserRequestStatus;
use App\Models\CampaignCategory;
use App\Models\FundraiserRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class FundraiserRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('name')->label('Requester')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('goal_amount')->money('inr', divideBy: 100),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(FundraiserRequestStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FundraiserRequest $record) => in_array($record->status, [FundraiserRequestStatus::New, FundraiserRequestStatus::UnderReview], true))
                    ->requiresConfirmation()
                    ->schema([
                        Select::make('cause_category_id')
                            ->label('Cause category')
                            ->options(fn () => CampaignCategory::query()->orderBy('name')->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->fillForm(fn (FundraiserRequest $record) => ['cause_category_id' => $record->cause_category_id])
                    ->action(function (FundraiserRequest $record, array $data) {
                        try {
                            $record->update(['cause_category_id' => $data['cause_category_id']]);
                            app(ApproveFundraiserRequest::class)->handle($record->fresh(), auth()->id());
                            Notification::make()->title('Request approved — draft campaign created')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not approve')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (FundraiserRequest $record) => in_array($record->status, [FundraiserRequestStatus::New, FundraiserRequestStatus::UnderReview], true))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')->label('Reason (shown to the requester)')->required(),
                    ])
                    ->action(function (FundraiserRequest $record, array $data) {
                        try {
                            app(RejectFundraiserRequest::class)->handle($record, $data['reason'], auth()->id());
                            Notification::make()->title('Request rejected')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not reject')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
