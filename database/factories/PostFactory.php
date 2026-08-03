<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => rtrim($title, '.'),
            'excerpt' => fake()->sentence(20),
            'body' => fake()->paragraphs(6, true),
            'category' => 'impact_stories',
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
    }
}
