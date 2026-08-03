<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * "Admin sees 'read by 132 of 247.'" — docs/modules/M09-notices-communication.md.
 */
class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Recipient')->searchable(),
                TextColumn::make('user.email')->label('Email'),
                IconColumn::make('read_at')->label('Read')->boolean(),
                TextColumn::make('email_status')->badge(),
                TextColumn::make('emailed_at')->dateTime()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('email_status')->options([
                    'pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed', 'bounced' => 'Bounced',
                ]),
            ])
            ->defaultSort('created_at');
    }
}
