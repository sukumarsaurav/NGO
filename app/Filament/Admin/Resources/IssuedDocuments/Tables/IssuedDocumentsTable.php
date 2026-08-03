<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\IssuedDocuments\Tables;

use App\Actions\Documents\RevokeDocument;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\IssuedDocument;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class IssuedDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->searchable()->copyable(),
                TextColumn::make('member.user.name')->label('Member')->searchable(),
                TextColumn::make('type')->badge()->formatStateUsing(fn (DocumentType $state) => $state->label()),
                TextColumn::make('title')->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (DocumentStatus $state) => $state->color())
                    ->formatStateUsing(fn (DocumentStatus $state) => $state->label()),
                TextColumn::make('issued_on')->date()->sortable(),
                TextColumn::make('verified_count')->label('QR scans'),
            ])
            ->filters([
                SelectFilter::make('type')->options(DocumentType::class),
                SelectFilter::make('status')->options(DocumentStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (IssuedDocument $record) => (bool) $record->file_path)
                    ->action(function (IssuedDocument $record) {
                        $record->increment('download_count');

                        return Storage::disk('local')->download($record->file_path, "{$record->document_number}.pdf");
                    }),

                Action::make('revoke')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (IssuedDocument $record) => $record->status === DocumentStatus::Issued && auth()->user()->can('revoke', $record))
                    ->schema([
                        Textarea::make('reason')->required(),
                    ])
                    ->action(function (IssuedDocument $record, array $data): void {
                        app(RevokeDocument::class)->handle($record, $data['reason']);

                        Notification::make()->title('Document revoked')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
