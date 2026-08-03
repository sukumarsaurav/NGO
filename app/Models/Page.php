<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A CMS page — About, Privacy Policy, Terms, or any ad-hoc page. See
 * docs/modules/M10-public-site-cms.md's "CMS".
 */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'body', 'template', 'meta_title', 'meta_description', 'is_published', 'sort_order',
    ];

    /**
     * Slugs that collide with a real single-segment route — blocked at
     * Filament validation, not at request time, per docs/modules/
     * M10-public-site-cms.md's "CMS page slug colliding with a route" edge
     * case. Two-segment routes (`/blog/{slug}`, `/causes/{slug}`) can never
     * collide with a one-segment `Page` slug, so they're not listed here.
     *
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return [
            'campaigns', 'causes', 'monthly-giving', 'start-fundraiser', 'donate',
            'blog', 'contact', 'verify', 'login', 'register', 'logout',
            'password', 'portal', 'admin', 'manager', 'sitemap.xml', 'robots.txt',
            'up', 'newsletter',
        ];
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
