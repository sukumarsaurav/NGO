<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CsrInquiries\Tables;

use App\Enums\CsrInquiryStatus;
use App\Models\CsrInquiry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CsrInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organisation_name')->searchable(),
                TextColumn::make('contact_name')->label('Contact')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CsrInquiryStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('updateStatus')
                    ->label('Update status')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Select::make('status')->options(CsrInquiryStatus::class)->required(),
                        Textarea::make('review_notes')->label('Notes')->rows(3),
                    ])
                    ->fillForm(fn (CsrInquiry $record): array => [
                        'status' => $record->status,
                        'review_notes' => $record->review_notes,
                    ])
                    ->action(function (CsrInquiry $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                            'review_notes' => $data['review_notes'],
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
