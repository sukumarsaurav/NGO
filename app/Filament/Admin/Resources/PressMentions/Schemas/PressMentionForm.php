<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PressMentions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PressMentionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('outlet_name')->required()->maxLength(150),
                TextInput::make('url')->url()->maxLength(255),
                DatePicker::make('published_on'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            FileUpload::make('logo_path')->image()->maxSize(2048)->disk('public')->directory('press-mentions'),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
