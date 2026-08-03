<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Notices\Tables;

use App\Actions\Notices\PublishNotice;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NoticesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('priority')->badge()->color(fn (NoticePriority $state) => $state->color())->formatStateUsing(fn (NoticePriority $state) => $state->label()),
                TextColumn::make('status')->badge()->color(fn (NoticeStatus $state) => $state->color())->formatStateUsing(fn (NoticeStatus $state) => $state->label()),
                TextColumn::make('recipients_read')
                    ->label('Read')
                    ->state(fn (Notice $record) => "{$record->read_count} of {$record->recipient_count}"),
                TextColumn::make('published_at')->dateTime()->placeholder('Not sent')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(NoticeStatus::class),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (Notice $record) => $record->status === NoticeStatus::Draft),

                Action::make('publish')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->visible(fn (Notice $record) => $record->status === NoticeStatus::Draft)
                    ->action(function (Notice $record): void {
                        app(PublishNotice::class)->handle($record);
                        Notification::make()->title('Notice published')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
