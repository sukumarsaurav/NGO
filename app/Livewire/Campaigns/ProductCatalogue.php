<?php

declare(strict_types=1);

namespace App\Livewire\Campaigns;

use App\Models\CampaignProduct;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * The needs-catalogue product cards — "Medicine kit · 11 / 1500 Donated ·
 * PRICE ₹900" with a `− 1 +` stepper. See
 * docs/modules/M08-campaigns-crowdfunding.md's "Products" section.
 *
 * A sibling to `DonationForm`, not a parent/child — the sticky donation card
 * and this catalogue occupy different, non-adjacent regions of the page per
 * `06-UI-UX-FOUNDATION.md`'s campaign-page wireframe, so state syncs via a
 * Livewire browser event rather than nested component props.
 */
class ProductCatalogue extends Component
{
    public int $campaignId;

    /** @var array<int, int> product id => quantity */
    public array $quantities = [];

    public function mount(int $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return Collection<int, CampaignProduct>
     */
    public function getProductsProperty(): Collection
    {
        return CampaignProduct::query()
            ->where('campaign_id', $this->campaignId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function increment(int $productId): void
    {
        $this->quantities[$productId] = ($this->quantities[$productId] ?? 0) + 1;
        $this->syncTotal();
    }

    public function decrement(int $productId): void
    {
        $this->quantities[$productId] = max(0, ($this->quantities[$productId] ?? 0) - 1);
        $this->syncTotal();
    }

    private function syncTotal(): void
    {
        $items = collect($this->quantities)
            ->filter(fn (int $quantity) => $quantity > 0)
            ->map(fn (int $quantity, int $productId) => ['product_id' => $productId, 'quantity' => $quantity])
            ->values()
            ->all();

        $this->dispatch('catalogue-updated', items: $items);
    }

    public function render()
    {
        return view('livewire.campaigns.product-catalogue');
    }
}
