<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Old URL → new URL, for slugs changed after a campaign has been published.
 * See docs/07-SEO.md §7. `hits`/`last_hit_at` are bumped by
 * `App\Http\Middleware\ResolveRedirect` on every match — they show which old
 * links still get traffic.
 */
class Redirect extends Model
{
    public $timestamps = false;

    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits', 'last_hit_at'];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
