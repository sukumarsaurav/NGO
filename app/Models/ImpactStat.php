<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Site-wide homepage counters. Distinct from `campaign_stats`, which belong
 * to one campaign. Manually maintained — auto-computing "lives impacted"
 * from the database would be dishonest. See
 * docs/modules/M10-public-site-cms.md's "CMS".
 */
class ImpactStat extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'value', 'suffix', 'icon', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
