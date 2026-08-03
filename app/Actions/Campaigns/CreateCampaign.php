<?php

declare(strict_types=1);

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Str;

/**
 * Creates a campaign in `draft`. See docs/modules/M08-campaigns-crowdfunding.md.
 *
 * The slug is generated from the title if one isn't supplied and made unique
 * with an incrementing suffix on collision — see the "Slug collision" edge
 * case. A draft's slug is still free to change until `PublishCampaign` locks
 * it (`Campaign::booted()` only starts recording redirects once a campaign
 * has left `draft`).
 */
final class CreateCampaign
{
    /**
     * @param  array{title: string, slug?: string|null, category_id: int, subtitle?: string|null,
     *     beneficiary_name?: string|null, story: string, cover_image_path?: string|null,
     *     cover_image_alt?: string|null, video_url?: string|null, goal_amount: int,
     *     status?: string, allows_recurring?: bool, is_tax_benefit?: bool, is_featured?: bool,
     *     is_urgent?: bool, starts_at?: string|null, ends_at?: string|null, sort_order?: int,
     *     meta_title?: string|null, meta_description?: string|null,
     *     offline_raised_amount?: int, created_by_user_id?: int|null}  $attributes
     */
    public function handle(array $attributes): Campaign
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['title']);
        $attributes['status'] ??= 'draft';

        return Campaign::query()->forceCreate($attributes);
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);
        $slug = $base;
        $suffix = 1;

        while (Campaign::withTrashed()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
