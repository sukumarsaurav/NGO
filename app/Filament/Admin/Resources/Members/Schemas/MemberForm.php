<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Members\Schemas;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Form tabs: Personal · Contact · Organisation · ID proof · Internal notes.
 * See docs/modules/M03-members.md.
 */
class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1:1 crop — ID cards look bad with arbitrary aspect ratios.
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
                                Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                                Select::make('designation_id')->relationship('designation', 'title')->searchable()->preload(),
                                DatePicker::make('joined_on')->required()->default(now()),
                                DatePicker::make('valid_until')->helperText('ID card expiry.'),
                                Select::make('status')->options(MemberStatus::class)->default(MemberStatus::Pending)->required()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Change via the status action, not this field — it validates the transition.')
                                    ->visibleOn('edit'),
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
