<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GalleryPhoto;
use App\Models\User;

class GalleryPhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_gallery_photos');
    }

    public function view(User $user, GalleryPhoto $galleryPhoto): bool
    {
        return $user->can('manage_gallery_photos');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_gallery_photos');
    }

    public function update(User $user, GalleryPhoto $galleryPhoto): bool
    {
        return $user->can('manage_gallery_photos');
    }

    public function delete(User $user, GalleryPhoto $galleryPhoto): bool
    {
        return $user->can('manage_gallery_photos');
    }
}
