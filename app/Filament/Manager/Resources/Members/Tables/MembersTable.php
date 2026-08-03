<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Members\Tables;

use App\Actions\Documents\IssueAppointmentLetter;
use App\Actions\Documents\IssueCertificate;
use App\Actions\Documents\IssueIdCard;
use App\Enums\MemberStatus;
use App\Models\Designation;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Same shape as the Admin `MembersTable` minus every bulk action (bulk
 * department move, bulk status change) — those aren't in a manager's
 * capability table (docs/modules/M11-manager-panel.md) and the resource's
 * query scope already limits rows to the manager's own department(s).
 */
class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/member-placeholder.svg')),
                TextColumn::make('user.name')->label('Name')->searchable()->sortable(),
                TextColumn::make('member_code')->label('Code')->searchable()->copyable(),
                TextColumn::make('department.name')->label('Department')->sortable(),
                TextColumn::make('designation.title')->label('Designation')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MemberStatus $state) => $state->color())
                    ->formatStateUsing(fn (MemberStatus $state) => $state->label()),
                TextColumn::make('joined_on')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(MemberStatus::class),
                SelectFilter::make('designation_id')
                    ->label('Designation')
                    ->options(fn () => Designation::query()->pluck('title', 'id')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('issueDocument')
                    ->label('Issue document')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->schema([
                        Select::make('type')
                            ->label('Document type')
                            ->options([
                                'id_card' => 'ID card',
                                'appointment_letter' => 'Appointment letter',
                                'certificate' => 'Certificate',
                            ])
                            ->required()
                            ->live(),
                        TextInput::make('title')
                            ->label('Certificate title')
                            ->required()
                            ->visible(fn (Get $get) => $get('type') === 'certificate'),
                    ])
                    ->action(function (Member $record, array $data): void {
                        try {
                            match ((string) $data['type']) {
                                'id_card' => app(IssueIdCard::class)->handle($record, (int) Auth::id()),
                                'appointment_letter' => app(IssueAppointmentLetter::class)->handle($record, (int) Auth::id()),
                                'certificate' => app(IssueCertificate::class)->handle($record, (string) $data['title'], (int) Auth::id()),
                                default => throw new InvalidArgumentException("Unknown document type '{$data['type']}'."),
                            };

                            Notification::make()->title('Document queued for generation')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title('Could not issue document')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
