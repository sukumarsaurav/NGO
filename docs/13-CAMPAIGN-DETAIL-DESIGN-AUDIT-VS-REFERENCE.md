# Campaign Detail — Design Audit vs. Reference

**Date:** 2026-08-07 · **Scope:** the donation sidebar and main-column composition of the campaign detail page
**Reference:** <https://bright-minds-haven.vercel.app/cases/aarav-heart-surgery> (live-inspected)
**Ours ("current style"):** <https://salmon-penguin-815902.hostingersite.com/campaigns/flood-relief-kits-assam> (live-inspected — this is an older deploy than the `develop` branch; noted throughout where the branch already differs)

**Method:** both pages inspected live — computed styles pulled via DevTools-equivalent (`getComputedStyle`) on the actual rendered DOM, not eyeballed from screenshots. Every value below (colour, radius, padding, shadow) is a measured value from one of the two sites, not an estimate.

---

## Executive summary

The token layer is not the problem. Pulled directly from the reference's computed styles: page background `rgb(250,249,246)`, border colour `rgb(217,212,198)` — both **exact matches** to our own `--neutral-25` (`#faf9f6`) and `--neutral-200` (`#d9d4c6`). That is not a coincidence: `docs/06-UI-UX-FOUNDATION.md` names this exact site as the brand-and-typography reference, and the palette was lifted from it correctly. Colour parity is already solved.

**What's different is composition** — how the same tokens get arranged into cards, boxes, and hierarchy:

1. Their sidebar wraps distinct concerns in **nested surfaces** (a bordered, shadowed outer card containing a separately-tinted inner box just for the raised/goal numbers). Ours is one flat card with everything printed directly on it.
2. Their two trust signals (**Tax Benefit**, **Live Updates**) are the **first thing in the card** — large, equal-width pills in a 2-column grid. Ours are small badges, and "Live Updates" barely reads as equivalent in weight to "Tax Benefit".
3. Their amount step leads with the **custom-amount input**, presets below it, plus a **"Pay via" row** of payment-method logos before the CTA. Ours leads with presets, custom amount second, no payment logos anywhere on the page.
4. Their **donate button carries the amount and the action together** (`₹3,000` left, `Donate Now →` right, one control) and is a single click through to checkout. Ours is a generic `Continue` that advances an internal step — the amount picked is not visible on the button that submits it.
5. Their **Story** section and their **product-sponsorship section** are both boxed, bordered, padded panels with real shadow — not a bare heading over a bare paragraph.
6. Their product-sponsorship section ("Add Care Products") is a fully-designed, prominent grid of icon-led cards. Ours has the equivalent component (`livewire/campaigns/product-catalogue.blade.php`) but no campaign in the current dataset has any products, so it has never been seen rendered next to real content — and where it does render, it leans on a full photo per item rather than the reference's compact icon treatment.
7. Layout proportions differ: theirs is a 12-column grid at **7 / 5** (58% / 42%), giving the sidebar noticeably more width than ours (a 3-column grid at **2 / 1**, 66% / 33%).

None of this requires new design tokens. Every recommendation below reuses `--radius-lg`, `--shadow-md`, `--color-surface-muted`, etc. — tokens that already exist and are already measured.

---

## 1. Side-by-side: what's actually different

| Element | Reference (measured) | Ours (measured, live) | Gap |
|---|---|---|---|
| Sidebar outer card | `bg-white border border-[#d9d4c6] rounded-xl(12px) p-6(24px) shadow(0 4px 6px -1px, 0 2px 4px -2px)` | `rounded-lg(16px) border border-line-divider bg-surface p-4(16px) shadow-sm(0 1px 2px)` | Padding 16px vs 24px; shadow far lighter than reference's; radius close enough (16 vs 12) |
| Raised/Goal figures | Wrapped in its **own** inner box: `bg-[#f5f3ee] border rounded-xl p-4` — a visually distinct sub-panel | Printed directly on the card background, no separating surface | Reference gives the money figures their own frame; ours blends into the rest of the card |
| Trust pills (Tax Benefit / Live Updates) | `grid grid-cols-2 gap-2`, each a full-width rounded-full pill, `border bg-white p-2`, icon + 12px semibold label — **first element in the card** | `flex flex-wrap gap-2` of small `<x-badge>` chips, auto-width, sits *between* the pills-would-be spot and the raised/goal numbers | Ours are smaller, unequal width, lower visual priority than the reference's treatment |
| Amount step order | **Custom amount input first** (48px tall, ₹ prefix icon, 18px font) → preset pills below | **Presets first** → custom amount input below | Order inverted |
| Preset pills | Single row of 4, `border` only (no fill), transparent bg, `rounded-lg(8px)`, 14px/700, ~101×38px, no sub-label | 2×2 grid (as of the `develop` branch fix), bordered, tinted fill when selected | Reference is plainer and fits one row; see §3 for why our 2×2 may no longer be necessary |
| Payment methods | **"PAY VIA" row** with UPI/Paytm/card logos, directly above the CTA | Not present anywhere on the page | Missing trust signal — donors deciding whether to trust a payment form want to see recognisable payment marks before they commit |
| CTA | One control: `₹3,000` left-aligned, `Donate Now →` right-aligned, `rounded-full`, single click to `/donate?amount=…` | `Continue` — generic label, amount not shown, advances to a second in-card step | Amount invisible at the point of commitment; label doesn't confirm what happens next |
| Story panel | `bg-white border rounded-xl p-12(md:48px) shadow(0 4px 20px, 3% opacity — a soft diffused shadow)` — a real card | Plain `<h2>` + `<div class="prose">`, no wrapper, no border, no shadow | Reference frames the story as a discrete, trustworthy unit; ours reads as loose page copy |
| Product-sponsorship cards | Compact: 40×40 circular icon badge + title on one row, 2-line description, `fraction / percent` on one line, mini progress bar, `UNIT PRICE` label + stepper | Full `aspect-[4/3]` photo (blank grey box when no image, which is 100% of current campaigns), name, description, progress bar, `PRICE ₹N` + stepper | Reference doesn't depend on photography per SKU — works identically whether or not a photo exists. Ours currently shows a large empty grey rectangle per product because no product has an image |
| Layout columns | 12-col grid, content `col-span-7` / sidebar `col-span-5` (~58/42) | 3-col grid, content `col-span-2` / sidebar `col-span-1` (~66/33) | Reference's sidebar gets ~9 percentage points more width — this is *why* their 4 presets fit one row and ours needed 2×2 |
| Sidebar stickiness | `lg:sticky lg:top-24`, matches ours conceptually | `lg:sticky lg:top-24` (already fixed on `develop` to cap height — see `docs/12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md` PR 2.3) | Already addressed on the branch, not live yet |

---

## 2. What the `develop` branch already fixes vs. what's still open

Since the linked hostinger deploy predates the work already done in this session, two rows above are **already closed** once that branch ships:

- Sidebar height/stickiness (PR 2.3, already implemented).
- "Spread the Word" no longer bloats the sidebar (already moved out, already implemented).

Everything else in the table above is **new** — none of it was in scope for the previous audit, which focused on dead classes, contrast, and empty-state bugs, not this kind of structural/compositional parity with the reference.

---

## 3. Root cause of the preset-grid disagreement

The 2×2 preset grid on `develop` was a *correct* fix for a *specific* problem: with the old per-amount impact tag ("1 Week Food", "1 Month Medical"), four columns in a ~360px sidebar produced 59.7px chips holding up to 63px of label. That problem is gone — the impact tag was removed in the same fix.

With the tag gone, a preset chip is just `₹500` — measured ~46-63px of text. Four chips at reference's own ~90-100px width comfortably fit a ~360-400px sidebar with room for gaps. **Recommendation: revert to a single row of 4** now that the constraint that forced 2×2 no longer applies, matching the reference and looking less cramped.

---

## 4. Remediation plan

Ordered by visual impact. All class names below are from tokens that already exist in `tokens.css` / `tailwind.config.js` — nothing new to add.

### PR A — Sidebar card: real separation, real shadow

`resources/views/components/campaigns/donation-card.blade.php`

1. Root: `p-4 shadow-sm` → **`p-6 shadow-md`** (24px padding, the measured `--shadow-md` token instead of the barely-visible `shadow-sm`).
2. Wrap the raised/goal block in its own sub-surface:
   ```blade
   <div class="mb-4 rounded-lg border border-line-divider bg-surface-muted p-4">
       <div class="flex items-start justify-between">
           <!-- Raised so far / Goal amount, unchanged -->
       </div>
       <x-progress-bar class="mt-3 mb-1" :percent="$percent" />
       <div class="flex items-center justify-between text-xs text-content-muted">
           <span>{{ $campaign->donor_count }} donors</span>
           <span class="font-semibold text-content">{{ $percent }}% funded</span>
       </div>
   </div>
   ```
   `bg-surface-muted` is `--neutral-50`, already the token closest to the reference's measured `#f5f3ee`.

### PR B — Trust pills: promoted, equal-width, first

Same file, replace the small-badge row with two full-width pills, `grid-cols-2`, matching the reference's actual `grid grid-cols-2 gap-2` structure (not `flex flex-wrap`):

```blade
<div class="mb-4 grid grid-cols-2 gap-2">
    @if ($campaign->is_tax_benefit)
        <div class="flex items-center justify-center gap-1.5 rounded-full border border-line-divider bg-surface p-2 text-xs font-semibold text-content">
            <svg class="h-3.5 w-3.5 text-success" ...check icon.../>
            Tax Benefit
        </div>
    @endif
    @if ($campaign->updates->isNotEmpty())
        <div class="flex items-center justify-center gap-1.5 rounded-full border border-line-divider bg-surface p-2 text-xs font-semibold text-content">
            <svg class="h-3.5 w-3.5 text-highlight" ...bolt icon.../>
            Live Updates
        </div>
    @endif
</div>
```
When only one of the two applies, it should still render as one pill in a 2-col grid (matches the reference's behaviour of never centring a lone pill oddly — verify visually once products/updates data exists).

### PR C — Amount step: reorder, single row, add Pay Via

`resources/views/livewire/donations/donation-form.blade.php`

1. Move the "Other amount" `<x-form.field>` **above** the preset radiogroup.
2. Preset grid: `grid grid-cols-2 gap-2` → **`flex flex-wrap gap-2`** (or `grid-cols-4` now that labels are short — flex is safer against a 5th preset from settings overflowing awkwardly). Drop the tinted `bg-trust` selected state in favour of a plainer `border-action` outline + `font-bold`, closer to the reference's minimalism — this is a judgement call, not a hard requirement; keeping the current tinted selection state is also defensible for accessibility (colour + fill, not border alone) and can stay if preferred.
3. Add a "Pay via" row above the CTA:
   ```blade
   <div class="mt-4 mb-2">
       <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-content-muted">Pay via</p>
       <div class="flex items-center gap-2">
           <img src="{{ asset('images/payment/upi.svg') }}" alt="UPI" class="h-6">
           <img src="{{ asset('images/payment/visa.svg') }}" alt="Visa" class="h-6">
           <img src="{{ asset('images/payment/mastercard.svg') }}" alt="Mastercard" class="h-6">
           <img src="{{ asset('images/payment/rupay.svg') }}" alt="RuPay" class="h-6">
       </div>
   </div>
   ```
   Needs the four logo assets — check whether Razorpay's own hosted badge set can be used directly (`checkout.razorpay.com` static assets) rather than sourcing/committing four new SVGs; that's the fastest path and stays visually current if Razorpay adds a payment method.

### PR D — CTA shows the amount

Same file, the step-1 `Continue` button:

```blade
<x-button type="button" size="xl" full wire:click="nextStep" class="!justify-between !px-6">
    <span>₹{{ number_format((float) $amount) }}</span>
    <span class="flex items-center gap-1">Continue <svg class="h-4 w-4" ...arrow.../></span>
</x-button>
```
`<x-button>` doesn't currently support a `justify-between` layout — either add a `spread` prop to the component (cleaner, reusable) or override with `!justify-between !px-6` as shown. Recommend the prop: `<x-button :spread="true">` with the two-span pattern, since this "amount left, label+icon right" pattern is worth having as a first-class button variant, not a one-off override.

**Scope note:** this PR keeps our two-step flow (amount → details) rather than adopting the reference's separate `/donate` page. That's a deliberate call, not an oversight — a full-page redirect is a bigger, riskier change than the plan's other items, and our existing single-page Livewire flow was a considered decision (fewer page loads, checkout stays close to the amount just chosen). If a genuine single-page-to-payment flow is wanted later, that's a separate, larger proposal — flag before starting it.

### PR E — Story panel: real card

`resources/views/public/campaigns/show.blade.php`, the `#story` section:

```blade
<section id="story" class="scroll-mt-[6.5rem] mb-12 rounded-lg border border-line-divider bg-surface p-6 shadow-sm sm:p-12">
    <h2 class="mb-3 font-heading text-xl font-bold text-content">Story</h2>
    <div class="prose prose-sm max-w-none text-content">
        {!! str($campaign->story)->sanitizeHtml() !!}
    </div>
</section>
```
Note the `prose` wrapper moves to an inner `<div>` since the outer `<section>` now needs its own non-prose padding/border classes — `prose` on the section itself would have `@tailwindcss/typography` fighting the card's own spacing.

### PR F — Product cards: icon-led, not photo-dependent

`resources/views/livewire/campaigns/product-catalogue.blade.php` — restructure each card:

```blade
<div wire:key="product-{{ $product->id }}" class="rounded-lg border border-line-divider bg-surface p-4 shadow-sm transition-shadow duration-base hover:shadow-md">
    <div class="mb-2 flex items-center gap-2">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-success-bg text-success-text">
            @if ($product->image_path)
                <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" class="h-full w-full rounded-full object-cover">
            @else
                <svg class="h-5 w-5" ...category-appropriate icon.../>
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
    <div class="flex items-center justify-between">
        <div>
            <p class="text-[0.65rem] uppercase tracking-wide text-content-muted">Unit price</p>
            <p class="font-semibold text-content">₹{{ number_format($product->unit_price / 100) }}</p>
        </div>
        <div class="flex items-center gap-2"><!-- stepper, unchanged --></div>
    </div>
</div>
```
This still uses a real photo when one exists (full-bleed inside the circle), and degrades gracefully to an icon when it doesn't — which, right now, is every product in the dataset. This is the one item in this plan that meaningfully changes behaviour, not just styling, so it's worth a quick visual check against a seeded product before merging.

**Content gap, not a code gap:** no campaign in the current dataset has any products, so this section has never rendered against real data. Seeding at least one campaign with 2-3 products (as the `docs/12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md` PR 5.1 content pass already recommends doing for other empty sections) is what actually makes this section visible to review.

### PR G — Layout proportions

`resources/views/public/campaigns/show.blade.php`, the main grid:

`grid-cols-1 gap-8 lg:grid-cols-3` with `lg:col-span-2` / `lg:col-start-3` → **`lg:grid-cols-12`** with content `lg:col-span-7` and sidebar `lg:col-span-5`, matching the reference's 58/42 split (vs. our current 66/33). Ripples through every `lg:col-span-2` / `lg:col-start-3` / `lg:row-span-2` reference in that section — mechanical but touches several lines, worth its own isolated PR so a layout regression is easy to bisect.

---

## 5. What's explicitly *not* recommended

- **A separate `/donate` page.** Noted in PR D — the reference does this, ours doesn't, and that's fine. Redirecting to checkout is a bigger architectural change with its own trade-offs (extra page load, donor leaves the campaign context) that deserves its own discussion, not a drive-by copy because the reference happens to do it that way.
- **New design tokens.** Every gap above closes with tokens that already exist (`shadow-md`, `surface-muted`, `radius-lg`). Don't add a second radius or shadow scale to chase a 12px-vs-16px difference that's barely perceptible side by side.
- **Copying the reference's `material-symbols-outlined` icon font.** Ours uses inlined SVG per `08-DESIGN-SYSTEM.md §9`; stay consistent with that rather than adding a second icon system for this one page.

---

## 6. Suggested order

1. **PR A + B** (sidebar card + trust pills) — highest visual impact, one file, no logic changes.
2. **PR E** (Story panel) — one file, no logic changes, closes the "loose copy vs. framed trust surface" gap that matters most for a first-time donor reading the story.
3. **PR C + D** (amount step reorder + CTA) — touches the Livewire form; needs the existing DonationForm tests re-run, and the payment-logo assets sourced first.
4. **PR G** (12-column layout) — mechanical, isolate from the others so any regression is obvious.
5. **PR F** (product cards) — pair with seeding at least one campaign's products so it can actually be reviewed against real data.

Effort: A/B/E ~half a day combined; C/D ~half a day plus asset sourcing; G ~2 hours; F ~3 hours plus seeding.
