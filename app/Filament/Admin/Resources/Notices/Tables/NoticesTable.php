<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\Tables;

use App\Actions\Notices\PublishNotice;
use App\Enums\NoticeAudience;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Jobs\SendNoticeEmail;
use App\Models\Notice;
use App\Models\NoticeRecipient;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('audience')->badge()->formatStateUsing(fn (NoticeAudience $state) => $state->label()),
                TextColumn::make('priority')->badge()->color(fn (NoticePriority $state) => $state->color())->formatStateUsing(fn (NoticePriority $state) => $state->label()),
                TextColumn::make('status')->badge()->color(fn (NoticeStatus $state) => $state->color())->formatStateUsing(fn (NoticeStatus $state) => $state->label()),
                TextColumn::make('recipients_read')
                    ->label('Read')
                    ->state(fn (Notice $record) => "{$record->read_count} of {$record->recipient_count}"),
                TextColumn::make('published_at')->dateTime()->placeholder('Not sent')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(NoticeStatus::class),
                SelectFilter::make('audience')->options(NoticeAudience::class),
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

                Action::make('resendFailed')
                    ->label('Resend to failures')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Notice $record) => in_array($record->status, [NoticeStatus::Sent, NoticeStatus::Sending], true))
                    ->action(function (Notice $record): void {
                        $failedIds = NoticeRecipient::query()
                            ->where('notice_id', $record->id)
                            ->whereIn('email_status', ['failed', 'bounced'])
                            ->pluck('id');

                        foreach ($failedIds as $id) {
                            NoticeRecipient::query()->whereKey($id)->update(['email_status' => 'pending']);
                        }

                        foreach ($failedIds->chunk(100) as $chunk) {
                            SendNoticeEmail::dispatch($record->id, $chunk->all());
                        }

                        Notification::make()->title("Resending to {$failedIds->count()} recipient(s)")->success()->send();
                    }),

                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
