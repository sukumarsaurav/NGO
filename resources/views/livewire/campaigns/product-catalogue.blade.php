{{-- Always one unconditional root element — the caller (public.campaigns.show)
     already guards `@livewire(...)` behind `$campaign->products->isNotEmpty()`,
     so a conditional root here isn't needed. It also isn't safe: Livewire
     misidentifies its single-root element when a `@if` sits directly astride
     the root, and was observed attaching its snapshot/wire:id to an unrelated
     nested `<div>` instead — silently breaking every wire:click in this view. --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    @php $products = $this->products; @endphp
    @foreach ($products as $product)
        @php $percent = (int) round($product->percentFunded()); @endphp
        <div wire:key="product-{{ $product->id }}" class="rounded-lg border border-line-divider bg-surface p-4">
            <div class="mb-3 aspect-[4/3] overflow-hidden rounded-md bg-surface-muted">
                @if ($product->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                @endif
            </div>
            <p class="font-semibold text-content">{{ $product->name }}</p>
            @if ($product->description)
                <p class="mb-2 text-xs text-content-muted">{{ $product->description }}</p>
            @endif

            <x-progress-bar class="mb-1" :percent="$percent" />
            <p class="mb-3 text-xs text-content-muted">{{ $product->units_funded }} / {{ $product->units_needed }} Donated</p>

            <div class="flex items-center justify-between">
                <span class="font-semibold text-content">PRICE ₹{{ number_format($product->unit_price / 100) }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="decrement({{ $product->id }})" class="flex h-8 w-8 items-center justify-center rounded-full border border-line text-content hover:bg-surface-muted" aria-label="Decrease quantity">−</button>
                    <span class="w-6 text-center text-content">{{ $quantities[$product->id] ?? 0 }}</span>
                    <button type="button" wire:click="increment({{ $product->id }})" class="flex h-8 w-8 items-center justify-center rounded-full border border-line text-content hover:bg-surface-muted" aria-label="Increase quantity">+</button>
                </div>
            </div>
        </div>
    @endforeach
</div>
