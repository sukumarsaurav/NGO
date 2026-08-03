<?php

declare(strict_types=1);

namespace App\Filament\Manager\Resources\Members\Schemas;

use App\Enums\Gender;
use App\Models\Department;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * A trimmed copy of the Admin `MemberForm` — same fields, minus `status`
 * (managers don't transition member status) and with `department_id`
 * restricted to departments this manager actually heads. See
 * docs/modules/M11-manager-panel.md: "the department dropdown only offers
 * their own departments, and the server validates it again on submit" —
 * the submit-time check lives in CreateMember/EditMember.
 */
class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()->maxSize(2048)
                    ->imageEditor()
                    ->imageEditorAspectRatios(['1:1'])
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('400')
                    ->imageResizeTargetHeight('400')
                    ->disk('public')
                    ->directory('members/photos')
                    ->avatar()
                    ->columnSpanFull(),

                Tabs::make('Member')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Personal')
                            ->schema([
                                TextInput::make('user.name')->label('Full name')->required()->maxLength(150),
                                DatePicker::make('date_of_birth'),
                                Select::make('gender')->options(Gender::class),
                                TextInput::make('blood_group')->maxLength(5),
                            ])
                            ->columns(2),

                        Tab::make('Contact')
                            ->schema([
                                TextInput::make('user.email')->label('Email')->email()->required()->maxLength(190),
                                TextInput::make('user.phone')->label('Phone')->tel()->maxLength(20),
                                TextInput::make('address_line1')->maxLength(190),
                                TextInput::make('address_line2')->maxLength(190),
                                TextInput::make('city')->maxLength(80),
                                TextInput::make('state')->maxLength(80),
                                TextInput::make('pincode')->maxLength(10),
                                TextInput::make('emergency_contact_name')->maxLength(120),
                                TextInput::make('emergency_contact_phone')->tel()->maxLength(20),
                            ])
                            ->columns(2),

                        Tab::make('Organisation')
                            ->schema([
                                TextInput::make('member_code')
                                    ->label('Member code')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Generated automatically on create.')
                                    ->visibleOn('edit'),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->options(fn () => Department::query()
                                        ->whereIn('id', Auth::user()->managedDepartmentIds())
                                        ->pluck('name', 'id'))
                                    ->required(),
                                Select::make('designation_id')->relationship('designation', 'title')->searchable()->preload(),
                                DatePicker::make('joined_on')->required()->default(now()),
                                DatePicker::make('valid_until')->helperText('ID card expiry.'),
                            ])
                            ->columns(2),

                        Tab::make('ID proof')
                            ->schema([
                                TextInput::make('id_proof_type')->maxLength(40)->placeholder('Aadhaar, PAN, DL'),
                                TextInput::make('id_proof_number')->maxLength(60)
                                    ->helperText('Encrypted at rest.'),
                            ])
                            ->columns(2),

                        Tab::make('Internal notes')
                            ->schema([
                                Textarea::make('notes')->rows(4)->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
