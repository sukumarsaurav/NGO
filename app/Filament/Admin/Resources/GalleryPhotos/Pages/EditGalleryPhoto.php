<?php

namespace App\Filament\Admin\Resources\GalleryPhotos\Pages;

use App\Filament\Admin\Resources\GalleryPhotos\GalleryPhotoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryPhoto extends EditRecord
{
    protected static string $resource = GalleryPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
