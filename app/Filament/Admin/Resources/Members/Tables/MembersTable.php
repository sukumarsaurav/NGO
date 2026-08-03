<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Members\Tables;

use App\Actions\Documents\IssueAppointmentLetter;
use App\Actions\Documents\IssueCertificate;
use App\Actions\Documents\IssueIdCard;
use App\Actions\Members\DeactivateMember;
use App\Enums\MemberStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Table: photo thumbnail, name, member code, department, designation, status
 * badge, joined date. Filters: status, department, designation, joined-date
 * range, has-photo. Search: name, member code, email, phone.
 * See docs/modules/M03-members.md.
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
                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'user',
                        fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                    ))
                    ->sortable(),
                TextColumn::make('member_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MemberStatus $state) => $state->color())
                    ->formatStateUsing(fn (MemberStatus $state) => $state->label()),
                TextColumn::make('joined_on')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(MemberStatus::class),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(fn () => Department::query()->pluck('name', 'id')),
                SelectFilter::make('designation_id')
                    ->label('Designation')
                    ->options(fn () => Designation::query()->pluck('title', 'id')),
                Filter::make('joined_on')
                    ->schema([
                        DatePicker::make('joined_from'),
                        DatePicker::make('joined_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['joined_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('joined_on', '>=', $date))
                            ->when($data['joined_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('joined_on', '<=', $date));
                    }),
                TernaryFilter::make('has_photo')
                    ->label('Has photo')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('photo_path'),
                        false: fn (Builder $query) => $query->whereNull('photo_path'),
                    ),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make(),

                    BulkAction::make('bulkIssueIdCards')
                        ->label('Bulk issue ID cards')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $issued = 0;

                            /** @var Member $member */
                            foreach ($records as $member) {
                                try {
                                    app(IssueIdCard::class)->handle($member, (int) Auth::id());
                                    $issued++;
                                } catch (InvalidArgumentException) {
                                    // No active default ID card template — skip, don't fail the batch.
                                }
                            }

                            Notification::make()->title("{$issued} ID card(s) queued")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('changeDepartment')
                        ->label('Change department')
                        ->icon(Heroicon::OutlinedBuildingOffice2)
                        ->schema([
                            Select::make('department_id')
                                ->label('New department')
                                ->options(fn () => Department::query()->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['department_id' => $data['department_id']]);

                            Notification::make()
                                ->title("Moved {$records->count()} members")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Routes through DeactivateMember, not a blanket mass-update
                    // — a heterogeneous selection (some active, some resigned)
                    // has different valid next states per member. Invalid
                    // transitions are skipped and reported, not silently forced.
                    BulkAction::make('changeStatus')
                        ->label('Change status')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->schema([
                            Select::make('status')->options(MemberStatus::class)->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            // A Select backed by enum ::options() hydrates the
                            // submitted state as the enum instance itself, not
                            // its scalar value — verified against the real
                            // component state, not assumed (it threw a
                            // TypeError on ::from() until this was fixed).
                            $target = $data['status'] instanceof MemberStatus
                                ? $data['status']
                                : MemberStatus::from($data['status']);
                            $moved = 0;
                            $skipped = 0;

                            /** @var Member $member */
                            foreach ($records as $member) {
                                try {
                                    app(DeactivateMember::class)->handle($member, $target);
                                    $moved++;
                                } catch (InvalidArgumentException) {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->title("{$moved} updated".($skipped ? ", {$skipped} skipped (invalid transition)" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
