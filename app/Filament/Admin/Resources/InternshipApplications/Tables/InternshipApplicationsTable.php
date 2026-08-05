<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\InternshipApplications\Tables;

use App\Enums\InternshipApplicationStatus;
use App\Models\InternshipApplication;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class InternshipApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('track')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(InternshipApplicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                // `local` disk, deliberately — resumes carry PII and must
                // never be publicly linkable via a plain asset URL.
                Action::make('downloadResume')
                    ->label('Resume')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (InternshipApplication $record) => (bool) $record->resume_path)
                    ->action(fn (InternshipApplication $record) => Storage::disk('local')->download(
                        $record->resume_path,
                        str($record->name)->slug().'-resume.'.pathinfo($record->resume_path, PATHINFO_EXTENSION)
                    )),
                Action::make('updateStatus')
                    ->label('Update status')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Select::make('status')->options(InternshipApplicationStatus::class)->required(),
                        Textarea::make('review_notes')->label('Notes')->rows(3),
                    ])
                    ->fillForm(fn (InternshipApplication $record): array => [
                        'status' => $record->status,
                        'review_notes' => $record->review_notes,
                    ])
                    ->action(function (InternshipApplication $record, array $data): void {
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
