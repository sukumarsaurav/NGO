<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions\Tables;

use App\Actions\Subscriptions\CancelSubscription;
use App\Enums\CancelledBy;
use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionActivatedMail;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('donor.name')->label('Donor')->searchable(),
                TextColumn::make('amount')->money('inr', divideBy: 100)->sortable(),
                TextColumn::make('interval')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SubscriptionStatus $state) => $state->color())
                    ->formatStateUsing(fn (SubscriptionStatus $state) => $state->label()),
                TextColumn::make('completed_cycles')->label('Cycles'),
                TextColumn::make('total_collected')->money('inr', divideBy: 100)->sortable(),
                TextColumn::make('next_charge_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('started_at')->date()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(SubscriptionStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('resendSetupEmail')
                    ->label('Resend setup email')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::Active)
                    ->action(function (Subscription $record): void {
                        Mail::to($record->donor->email)->queue(new SubscriptionActivatedMail($record));
                        Notification::make()->title('Setup email resent')->success()->send();
                    }),

                Action::make('cancel')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Subscription $record) => in_array($record->status, [SubscriptionStatus::Active, SubscriptionStatus::Paused, SubscriptionStatus::Halted], true))
                    ->schema([
                        Textarea::make('reason')->label('Reason (optional)'),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        try {
                            app(CancelSubscription::class)->handle($record, CancelledBy::Admin, $data['reason'] ?: null);
                            Notification::make()->title('Subscription cancelled')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not cancel')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
