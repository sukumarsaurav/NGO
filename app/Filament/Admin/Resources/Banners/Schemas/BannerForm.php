<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Banners\Schemas;

use App\Models\Campaign;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('title')->required()->maxLength(190),
                TextInput::make('subtitle')->maxLength(255),
            ]),
            FileUpload::make('image_path')
                ->label('Desktop image (~1920×720)')
                ->image()->maxSize(2048)
                ->disk('public')
                ->directory('banners')
                ->required(),
            FileUpload::make('mobile_image_path')
                ->label('Mobile image (~750×900) — falls back to the desktop image')
                ->image()->maxSize(2048)
                ->disk('public')
                ->directory('banners'),
            Grid::make(2)->schema([
                TextInput::make('cta_label')->maxLength(60),
                TextInput::make('cta_url')->url()->maxLength(255)
                    ->helperText('Ignored if a linked campaign is selected below.'),
                Select::make('campaign_id')
                    ->label('Linked campaign (shortcut instead of a CTA URL)')
                    ->options(fn () => Campaign::query()->orderByDesc('created_at')->limit(50)->pluck('title', 'id'))
                    ->searchable(),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            Grid::make(2)->schema([
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
            ]),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
