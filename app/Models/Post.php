<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A blog post. Blog content ranks where campaign pages can't — see
 * docs/modules/M10-public-site-cms.md's "CMS".
 *
 * @property PostCategory|null $category
 * @property Carbon|null $published_at
 */
class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'excerpt', 'body', 'cover_image_path', 'category', 'tags',
        'author_user_id', 'published_at', 'view_count', 'meta_title', 'meta_description', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'category' => PostCategory::class,
            'tags' => 'array',
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function isLive(): bool
    {
        return $this->is_published && $this->published_at !== null && $this->published_at->isPast();
    }
}
