<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Notices\Schemas;

use App\Actions\Notices\ResolveNoticeAudience;
use App\Enums\NoticeAudience;
use App\Enums\NoticePriority;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * `department_ids` / `designation_ids` / `user_ids` are virtual fields,
 * never written to the `notices` table directly — the Create/Edit pages
 * fold them into `audience_filter` on save and unpack them back out on
 * load. Each is wrapped in a `Group` carrying `visible()` +
 * `dehydratedWhenHidden()`: Filament excludes a directly-hidden component
 * from form state by default (`dehydrated(true)` on the field itself does
 * NOT override this — it only checks its *parent's*
 * `isDehydratedWhenHidden()`), so the visibility toggle has to live one
 * level up from the field whose value must survive to submit. See
 * docs/modules/M09-notices-communication.md: "Show a live recipient count
 * in the compose UI before sending."
 */
class NoticeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(190)->columnSpanFull(),
            RichEditor::make('body')->required()->columnSpanFull(),

            Select::make('audience')
                ->options(NoticeAudience::class)
                ->required()
                ->live()
                ->default(NoticeAudience::AllMembers->value),

            Group::make([
                Select::make('department_ids')
                    ->label('Departments')
                    ->multiple()
                    ->options(fn () => Department::query()->pluck('name', 'id'))
                    ->live(),
            ])->visible(fn ($get) => $get('audience') === NoticeAudience::Department->value)->dehydratedWhenHidden(),

            Group::make([
                Select::make('designation_ids')
                    ->label('Designations')
                    ->multiple()
                    ->options(fn () => Designation::query()->pluck('title', 'id'))
                    ->live(),
            ])->visible(fn ($get) => $get('audience') === NoticeAudience::Designation->value)->dehydratedWhenHidden(),

            Group::make([
                Select::make('user_ids')
                    ->label('Specific users')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->live(),
            ])->visible(fn ($get) => $get('audience') === NoticeAudience::Specific->value)->dehydratedWhenHidden(),

            Placeholder::make('recipient_estimate')
                ->label('Estimated recipients')
                ->live()
                ->content(function ($get) {
                    $rawAudience = $get('audience');
                    $audience = $rawAudience instanceof NoticeAudience
                        ? $rawAudience
                        : NoticeAudience::tryFrom((string) $rawAudience);

                    if (! $audience) {
                        return new HtmlString('—');
                    }

                    $filter = [
                        'department_ids' => $get('department_ids') ?? [],
                        'designation_ids' => $get('designation_ids') ?? [],
                        'user_ids' => $get('user_ids') ?? [],
                    ];

                    $count = count(app(ResolveNoticeAudience::class)->handle($audience, $filter));

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
                // Content-sniffed server-side via Filament's mimetypes:
                // rule, not extension-matched — a renamed `.php` still
                // gets rejected. See docs/03-ROADMAP.md's Sprint 15
                // acceptance criterion.
                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg', 'image/webp'])
                ->maxSize(5120)
                ->helperText('PDF or image, max 5 MB.'),

            DateTimePicker::make('expires_at')->helperText('Hidden from the portal inbox after this date. Optional.'),
        ]);
    }
}
