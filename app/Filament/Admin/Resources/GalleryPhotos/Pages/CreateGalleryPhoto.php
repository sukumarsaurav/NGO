<?php

namespace App\Filament\Admin\Resources\GalleryPhotos\Pages;

use App\Filament\Admin\Resources\GalleryPhotos\GalleryPhotoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryPhoto extends CreateRecord
{
    protected static string $resource = GalleryPhotoResource::class;
}
