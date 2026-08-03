<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Notices\Schemas;

use App\Actions\Notices\ResolveNoticeAudience;
use App\Enums\NoticeAudience;
use App\Enums\NoticePriority;
use App\Models\Department;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * A manager can only publish to a department they manage — see
 * docs/modules/M11-manager-panel.md's capability table: "Publish notices:
 * To own department only." Unlike the Admin `NoticeForm`, there's no
 * audience picker at all: it's implicitly `department`, scoped to this
 * manager's own department(s), enforced again server-side in `CreateNotice`.
 */
class NoticeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(190)->columnSpanFull(),
            RichEditor::make('body')->required()->columnSpanFull(),

            Select::make('department_ids')
                ->label('Departments')
                ->multiple()
                ->required()
                ->options(fn () => Department::query()
                    ->whereIn('id', Auth::user()->managedDepartmentIds())
                    ->pluck('name', 'id'))
                ->live(),

            Placeholder::make('recipient_estimate')
                ->label('Estimated recipients')
                ->live()
                ->content(function ($get) {
                    $count = count(app(ResolveNoticeAudience::class)->handle(
                        NoticeAudience::Department,
                        ['department_ids' => $get('department_ids') ?? []],
                    ));

                    return new HtmlString("This will reach <strong>{$count}</strong> ".($count === 1 ? 'person' : 'people').'.');
                }),

            Select::make('priority')
                ->options(NoticePriority::class)
                ->required()
                ->default(NoticePriority::Normal->value),

            Toggle::make('send_email')->label('Also email recipients')->default(true),

            FileUpload::make('attachment_path')
                ->label('Attachment')
                ->directory('notices')
                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'image/webp'])
                ->maxSize(5120)
                ->helperText('PDF or image, max 5 MB.'),

            DateTimePicker::make('expires_at')->helperText('Hidden from the portal inbox after this date. Optional.'),
        ]);
    }
}
