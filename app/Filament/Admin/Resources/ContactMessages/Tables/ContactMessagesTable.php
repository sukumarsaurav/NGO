<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ContactMessages\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('subject')->placeholder('—'),
                IconColumn::make('is_read')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_read'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->before(function ($record) {
                        if (! $record->is_read) {
                            $record->update(['is_read' => true, 'read_at' => now()]);
                        }
                    }),
                Action::make('toggleRead')
                    ->label(fn ($record) => $record->is_read ? 'Mark unread' : 'Mark read')
                    ->icon('heroicon-o-envelope')
                    ->action(fn ($record) => $record->update([
                        'is_read' => ! $record->is_read,
                        'read_at' => $record->is_read ? null : now(),
                    ])),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
