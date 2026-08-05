<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GalleryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'image_path' => 'gallery-photos/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(),
            'is_published' => true,
            'sort_order' => 0,
        ];
    }
}
