{{-- Always one unconditional root element — the caller (public.campaigns.show)
     already guards `@livewire(...)` behind `$campaign->products->isNotEmpty()`,
     so a conditional root here isn't needed. It also isn't safe: Livewire
     misidentifies its single-root element when a `@if` sits directly astride
     the root, and was observed attaching its snapshot/wire:id to an unrelated
     nested `<div>` instead — silently breaking every wire:click in this view. --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @php $products = $this->products; @endphp
    @foreach ($products as $product)
        @php $percent = (int) round($product->percentFunded()); @endphp
        {{--
            Icon-led card, not a full photo — see
            docs/13-CAMPAIGN-DETAIL-DESIGN-AUDIT-VS-REFERENCE.md PR F. The previous card
            reserved a full `aspect-[4/3]` image box per product; with no product in the
            current dataset carrying a photo, every card rendered as a large empty grey
            rectangle. A 40×40 circular badge shows the real photo when one exists (as a
            cropped fill) and falls back to a generic package icon otherwise — the card
            reads the same either way instead of depending on per-SKU photography.
        --}}
        <div wire:key="product-{{ $product->id }}" class="flex flex-col justify-between rounded-lg border border-line-divider bg-surface p-4 shadow-sm transition-shadow duration-base hover:shadow-md">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="flex h-[2.5rem] w-[2.5rem] shrink-0 items-center justify-center rounded-full bg-success-bg text-success-text">
                        @if ($product->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="" class="h-full w-full rounded-full object-cover">
                        @else
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25 4.5L3.75 7.5m16.5 0l-8.25-4.5L3.75 7.5m16.5 0v9l-8.25 4.5m0-9L3.75 7.5m8.25 4.5v9M3.75 7.5v9l8.25 4.5" />
                            </svg>
                        @endif
                    </span>
                    <p class="line-clamp-1 font-semibold text-content">{{ $product->name }}</p>
                </div>
                @if ($product->description)
                    <p class="mb-3 line-clamp-2 min-h-[2rem] text-xs text-content-muted">{{ $product->description }}</p>
                @endif

                <div class="mb-1 flex items-end justify-between text-xs text-content-muted">
                    <span>{{ $product->units_funded }} / {{ $product->units_needed }}</span>
                    <span class="font-bold text-brand-700">{{ $percent }}%</span>
                </div>
                <x-progress-bar class="mb-3" :percent="$percent" />
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[0.65rem] uppercase tracking-wide text-content-muted">Unit price</p>
                    <p class="font-semibold text-content">₹{{ number_format($product->unit_price / 100) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="decrement({{ $product->id }})" class="flex h-8 w-8 items-center justify-center rounded-full border border-line text-content hover:bg-surface-muted" aria-label="Decrease quantity">−</button>
                    <span class="w-6 text-center text-content">{{ $quantities[$product->id] ?? 0 }}</span>
                    <button type="button" wire:click="increment({{ $product->id }})" class="flex h-8 w-8 items-center justify-center rounded-full border border-line text-content hover:bg-surface-muted" aria-label="Increase quantity">+</button>
                </div>
            </div>
        </div>
    @endforeach
</div>
