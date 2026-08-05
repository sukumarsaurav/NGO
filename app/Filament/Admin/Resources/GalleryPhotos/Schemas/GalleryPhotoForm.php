<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GalleryPhotos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class GalleryPhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('title')->maxLength(190),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            FileUpload::make('image_path')->image()->required()->maxSize(2048)->disk('public')->directory('gallery-photos'),
            Textarea::make('caption')->rows(3),
            Toggle::make('is_published')->default(true),
        ]);
    }
}
