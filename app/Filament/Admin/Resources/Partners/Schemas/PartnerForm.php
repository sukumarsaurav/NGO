<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Partners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(190),
                TextInput::make('category')->maxLength(100)->helperText('e.g. Corporate Partner, Implementation Partner'),
                TextInput::make('website_url')->url()->maxLength(255),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            FileUpload::make('logo_path')->image()->required()->maxSize(2048)->disk('public')->directory('partners'),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
