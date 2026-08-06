# Remediation Plan — Homepage & Campaign Pages

**Source:** [`11-UI-UX-AUDIT-HOME-CAMPAIGNS.md`](11-UI-UX-AUDIT-HOME-CAMPAIGNS.md) · **Target branch:** `develop`
**Owner:** full-stack · **Total estimate:** 9–11 dev days across 5 waves

Every item traces to a numbered audit finding. Nothing here is speculative refactoring — if a change is not fixing a
finding or making a finding un-repeatable, it is not in this plan.

---

## 0. Decisions taken up front

These are the judgement calls the rest of the plan assumes. Disagree here, not in review.

| # | Decision | Rationale |
|---|---|---|
| D1 | **Do not add new colour tokens for the `emerald-*` fixes.** Map every one onto the existing measured `success-*` / `trust` pairs. | The tokens exist and are contrast-measured (`tokens.css:114`). Adding an emerald ramp re-opens exactly the drift the replaced-scale decision closed. |
| D2 | **Rebuild the 80G callout on `<x-alert variant="success">` rather than restyling the div.** | The component already is a tinted success surface with an icon, `role="status"`, and a measured 8.60:1 pair. One-line swap, zero new CSS, and it stops the donation form from having its own private banner style. |
| D3 | **Extract `<x-modal>`; do not patch the credentials modal in place.** | There are two modal implementations (drawer at `layout/public.blade.php:275` — correct; credentials at `show.blade.php:143` — no trap, no lock, wrong layer). A third would be worse. Extract from the drawer's known-good behaviour. |
| D4 | **Add `--text-6xl` to the type scale.** Do not add `--space-40` or `--space-10`. | A hero display size is a genuine gap the scale never covered. The spacing misses are all "someone typed a Tailwind default" and must round to the existing scale — that is the point of the coarse scale. |
| D5 | **Delete the "Every Act of Kindness Counts" hero sticker.** | It is decorative-only, lands on the subject's face at 1280px, and is the sole consumer of four dead classes (`font-serif`, `rounded-2xl`, `shadow-xl`, `sm:p-5`). Removing it is cheaper than fixing it and improves the hero. *(§4.12)* |
| D6 | **Preset impact tags come from campaign data or they go.** | `donation-form.blade.php:33-38` hard-codes "1 Week Food" / "1 Month Medical" off the rupee value, unrelated to the campaign. On a medical campaign ₹500 is labelled "1 Week Food". That is a factual claim to a donor, invented in a Blade `match()`. Wave 2 removes it; Wave 5 reintroduces it from `campaign_products`. |
| D7 | **`donor_count` becomes derived, not stored.** | Two sources of truth produced §2.2. Keep the column as a cache, but make one code path own writing it, and make the wall and the count read the same thing. |
| D8 | **Share controls leave the listing card entirely.** | 3 duplicate links + 4 share controls × 12 cards is the bulk of §3.2's 128 tab stops. Share belongs on the detail page, where it already exists and works. |
| D9 | **Ship behind no feature flags.** | Every change is either a bug fix or a visual correction on a pre-launch site. Flags would cost more than they protect. |

---

## Wave 1 — Stop the bleeding (1.5 days, blocks everything)

### PR 1.1 — Push `develop` and harden the dead-class guard *(§1.6)*

The CI gate at `.github/workflows/ci.yml:53-55` already runs `npm run check:classes`. It has never seen this work:
`origin/develop` is at `c5fae35`, local is two commits ahead at `dc7a907`. **Do this first** so the gate is red for a
reason everyone can see, and so the rest of the wave has a pass/fail signal.

**Changes**

1. `scripts/check-dead-classes.mjs:105` — the token filter drops negative-prefixed utilities:

   ```js
   // before
   if (!/^[a-z0-9][a-z0-9:./[\]#%_-]*$/i.test(token)) continue;
   // after — allow a leading `-`, and a leading `-` after a variant prefix (`lg:-mt-4`)
   if (!/^-?[a-z0-9][a-z0-9:./[\]#%_-]*$/i.test(token)) continue;
   ```

   Then re-run: `-top-2.5` (×2, `donation-form.blade.php:49` and `show.blade.php`) must appear in the report. It is
   the cause of §1.1's overlapping badge and is currently invisible to the guard.

2. Add a pre-push hook so this is caught before it is shared, not after:

   ```
   .husky/pre-push  →  npm run build --silent && npm run check:classes
   ```

   Add `husky` as a devDependency and a `prepare` script. ~8s on a warm build; acceptable for pre-push (not
   pre-commit).

3. Add `ALLOW`-list documentation: any future intentional exception must carry a one-line comment saying why.

**Acceptance:** `npm run check:classes` reports **37** dead classes (35 + the two newly-visible `-top-2.5`), CI is red
on `develop`, and a `git push` from a dirty tree is blocked locally.

**Effort:** 2h.

---

### PR 1.2 — Remediate all dead classes *(§1.1–1.5, §1.6)*

The complete mapping. **No entry invents a token** except `text-6xl` (D4).

#### Colour — every `emerald-*` / `gray-*` reference (D1)

| File:line | Before | After | Note |
|---|---|---|---|
| `home.blade.php:75,109` | `text-gray-700` | `text-content-muted` | hero paragraph |
| `home.blade.php:128` | `text-gray-700` | — | deleted with the sticker (D5) |
| `home.blade.php:60,93` | `text-gray-900` | `text-content` | hero `<h1>` |
| `campaigns/show.blade.php:74` | `text-emerald-700` | `text-success-text` | "Legal Audit Cleared" — restores the verification green (§2.5) |
| `campaigns/show.blade.php:168` | `text-emerald-700` | `text-success-text` | modal "Annual Public Financial Audit" |
| `campaigns/show.blade.php:59` | `text-emerald-800 border-emerald-300` | *(drop both)* | the `trust` variant already supplies brand-100/brand-800 at 10.08:1 |
| `campaigns/show.blade.php:156,160` | `bg-emerald-50 text-emerald-800 border-emerald-200` | `bg-success-bg text-success-text` | credential chips; drop the border per §5 "border OR shadow" |
| `campaigns/show.blade.php:143` | `bg-gray-900/60` | `bg-scrim` | the token exists (`tokens.css:176`) and is the documented modal overlay |
| `campaigns/card.blade.php:84` | `hover:bg-gray-900/10 hover:text-gray-900` | *(deleted with share row, D8)* | |
| `donation-form.blade.php:69-70` | `bg-emerald-50/80 border-emerald-200 text-emerald-900 text-emerald-700` | *(replaced by `<x-alert variant="success">`, D2)* | §1.2 |

#### Spacing — round to the scale `0 · px · 1 · 2 · 3 · 4 · 6 · 8 · 12 · 16 · 24`

| File:line | Before | After |
|---|---|---|
| `donation-form.blade.php:49` | `py-0.5` | `py-1` |
| `donation-form.blade.php:49` | `-top-2.5` | `-top-3` |
| `donation-form.blade.php:52` | `mt-0.5` | `mt-1` |
| `donation-form.blade.php:69` | `p-2.5` | *(replaced by `<x-alert>`)* |
| `campaigns/show.blade.php:156,160,164` | `py-0.5` | `py-1` |
| `campaigns/show.blade.php:125` | `h-3.5 w-3.5` | `h-4 w-4` | **§1.4 — currently 95×95px** |
| `campaigns/card.blade.php:75` | `pt-2.5` | *(deleted with share row)* |
| `donation-card.blade.php:57,61,65,75` | `h-5 w-5` | `h-6 w-6` | **§1.4 — currently 58×58px** |
| `home.blade.php:48` | `sm:py-20 gap-10` | `sm:py-16 gap-8` |
| `home.blade.php:65,100` | `w-40` + `flex-1` rules | fixed `w-16` rules — see below | **§1.5 — currently width 0** |
| `public-footer.blade.php:33` | `py-14` | `py-12` | `sm:py-16` already present |
| `layout/public.blade.php:211` | `w-56` | `w-[14rem]` | arbitrary value; the dropdown needs a width no scale step gives |

The hero divider (§1.5) stops depending on a parent width entirely:

```blade
{{-- before — w-40 is dead, so both rules compute to width: 0 --}}
<div class="flex items-center gap-3 w-40 mb-6">
    <div class="h-[2px] flex-1 bg-brand-800/30"></div>
    …heart…
    <div class="h-[2px] flex-1 bg-brand-800/30"></div>
</div>

{{-- after — intrinsic widths from the scale, no flex arithmetic to get wrong --}}
<div class="mb-6 flex items-center gap-3">
    <div class="h-px w-16 bg-brand-800/30"></div>
    …heart…
    <div class="h-px w-16 bg-brand-800/30"></div>
</div>
```

Applies to **both** branches (`:65` banner, `:100` fallback).

#### Radius / shadow

| Before | After | Sites |
|---|---|---|
| `rounded-2xl` | `rounded-lg` (16px) | `show.blade.php:144`; `home.blade.php:127` deleted with sticker |
| `shadow-2xl` | `shadow-lg` | `show.blade.php:144`, `:280` |
| `shadow-xl` | *(deleted with sticker)* | `home.blade.php:127` |
| `shadow-xs` | `shadow-sm` | `donation-form.blade.php:49` |

#### Type — the two additions

1. **`text-6xl`** (D4). `home.blade.php:60,93` want a hero display size; the scale stops at `5xl` (3rem).

   ```css
   /* tokens.css §3, after --text-5xl */
   --text-6xl:  3.75rem;   --leading-6xl:  1.05;
   ```
   ```js
   // tailwind.config.js fontSize
   '6xl': ['var(--text-6xl)', { lineHeight: 'var(--leading-6xl)' }],
   ```
   Record it in `08-DESIGN-SYSTEM.md §3` with the rest of the scale.

2. **`font-mono`** — `show.blade.php:156,160,164` render 80G / 12A / Darpan numbers. Monospace is correct for an
   identifier a donor will visually compare against a certificate.

   ```css
   --font-mono: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
   ```
   ```js
   fontFamily: { …, mono: 'var(--font-mono)' }
   ```
   *Alternative if you want zero scale growth:* use the existing `.tabular` helper from `tokens.css:284` plus
   `tracking-wide`. Weaker, but adds nothing. My recommendation is to add the token — receipts and document IDs will
   want it too.

#### `font-serif` — no fix, deleted

`home.blade.php:128` is the hero sticker. Per D5 the whole block (`:126-134`) is removed. That single deletion clears
`font-serif`, `rounded-2xl`, `shadow-xl`, `sm:p-5`, and one `text-gray-700`.

**Acceptance**

- `npm run build && npm run check:classes` → `✓ No dead classes.`
- Manual: hero divider rules render at 64px each; credentials arrow measures 16×16; share icons 24×24; POPULAR badge
  sits above the ₹1,000 chip, not on it.
- **Verify `-top-3` actually generates.** Negative utilities off a replaced spacing scale are the one item here I
  have not built. If it does not, use `-top-[12px]` and note it.

**Effort:** 5h. **Depends on:** PR 1.1 (for the guard to confirm the work).

---

### PR 1.3 — Fix the layering, extract `<x-modal>` *(§1.3)*

**Layer corrections** — raw z-indexes replaced with the allocated named layers from `tokens.css:182-189`:

| File:line | Before | After |
|---|---|---|
| `show.blade.php:143` | `z-50` | `z-modal` (600) |
| `show.blade.php:280` | `z-40` | `z-donate-bar` (300) |

**New component** `resources/views/components/modal.blade.php` (D3), built from the drawer's known-good pattern:

```blade
@props(['name', 'title', 'maxWidth' => 'max-w-md'])
<div
    x-show="{{ $name }}"
    x-cloak
    x-trap.noscroll="{{ $name }}"
    @keydown.escape.window="{{ $name }} = false"
    role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title"
    class="fixed inset-0 z-modal flex items-center justify-center bg-scrim p-4"
>
    <div @click.outside="{{ $name }} = false" class="w-full {{ $maxWidth }} rounded-lg bg-surface p-6 shadow-lg">
        <div class="flex items-start justify-between gap-4 border-b border-line-divider pb-3">
            <h2 id="{{ $name }}-title" class="font-heading text-lg font-bold text-content">{{ $title }}</h2>
            <button type="button" @click="{{ $name }} = false" aria-label="Close"
                    class="-mr-2 -mt-2 inline-flex min-h-touch min-w-touch items-center justify-center rounded-md text-content-muted hover:bg-surface-muted hover:text-content">
                <svg class="h-4 w-4" …>…</svg>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
```

Notes on what this fixes beyond the layer:

- `x-trap.noscroll` — focus trap **and** body-scroll lock, neither of which the current modal has.
- `role="dialog" aria-modal aria-labelledby` — currently absent; the modal is invisible to assistive tech as a modal.
- Close control is a real `min-h-touch` button with an `aria-label`, not a bare `&times;` glyph at whatever size the
  text happens to be.
- `@click.away` → `@click.outside`. `@click.away` on the *panel* fires on the panel's own descendants in some Alpine
  versions; `.outside` is the current correct directive.

**Also in this PR:** `show.blade.php:172` — "Close & Continue Donation" is `variant="accent"`, making the modal's
*dismiss* the loudest control on screen. Change to `variant="secondary"`.

**Acceptance:** with the modal open — header is behind the scrim; Tab cycles inside the panel only; the page behind
does not scroll; Escape closes; focus returns to the trigger.

**Effort:** 4h.

---

## Wave 2 — Conversion path (2.5 days)

### PR 2.1 — Rebuild the donation-form amount step *(§1.1, §1.2, D6)*

`resources/views/livewire/donations/donation-form.blade.php:29-76`

**1. Grid.** `grid grid-cols-2 gap-2 sm:grid-cols-4` → **`grid grid-cols-2 gap-2`** at every width.

The sidebar column is 362.7px and the radiogroup 262.7px — `sm:` keys off the viewport, so on desktop it forces four
59.7px chips holding 63px labels. 2×2 is what the mobile layout already renders correctly. Add a comment pointing at
the identical, already-documented reasoning at line 130 of the same file, so this is not re-broken.

**2. Badge.** `-top-2.5` → `-top-3` (PR 1.2), and give the chip `pt-4` so the badge has somewhere to sit without the
amount moving.

**3. Impact tags (D6).** Delete the `$presetTag` `match()` at `:33-38` and the `<span>` at `:52`. It asserts
"1 Week Food" on a paediatric-surgery campaign. Wave 5 reintroduces it correctly.

With the tag gone the chip is amount-only, which is also what makes 2×2 comfortable at 130px per chip.

**4. Selected state.** Currently `ring-2 ring-brand-600/30` — a 30%-alpha ring on a light tint. Move to
`border-action bg-trust text-trust-text` (measured 10.08:1) so selection is carried by border *and* fill, not a faint
ring. `aria-checked` already handles the non-visual channel.

**5. The 80G callout → `<x-alert>` (D2, §1.2).**

```blade
{{-- before: 6 dead classes, renders as a black-bordered transparent box --}}
<div class="mt-3 flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50/80 p-2.5 text-xs text-emerald-900">
    <svg class="h-4 w-4 shrink-0 text-emerald-700" …>
    <span><strong>80G Tax Savings:</strong> …</span>
</div>

{{-- after --}}
<x-alert variant="success" class="mt-3 text-xs">
    <strong>80G Tax Savings:</strong> Save approx. <strong>₹{{ number_format((float) $amount * 0.5) }}</strong>
    in income tax (50% deduction under Sec 80G).
</x-alert>
```

**6. Reconcile presets with "Other amount".** `$amount` is pre-populated (renders `1000`) while `$selectedPreset` is
null, so the form opens showing an amount with no chip selected — two controls disagreeing about the same value.
In `App\Livewire\Donations\DonationForm::mount()`, if the initial `$amount` matches a preset, set `$selectedPreset`
to match. Conversely `selectPreset()` should already write `$amount`; verify and add a test.

**Acceptance:** at 1280px the four chips render two-up at ~130px each with no label overflow; POPULAR clears the
amount; the 80G callout renders green-tinted with a left border and `role="status"`; opening the form shows exactly
one selected amount.

**Effort:** 6h. **Tests:** extend `tests/Feature/Livewire/DonationFormTest.php` — `mount()` preselects a matching
preset; `selectPreset()` syncs `$amount`.

---

### PR 2.2 — Campaign card: one link, no share, honest CTA *(§2.4, §2.6, §3.2, D8)*

`resources/views/components/campaigns/card.blade.php`

**1. `h-full` on the root** (§2.6). Measured stagger in the featured rail: cards 566.4px vs wrappers 587.2px, CTAs
20.8px out of line.

```blade
- <div class="flex flex-col overflow-hidden rounded-lg …">
+ <div class="flex h-full flex-col overflow-hidden rounded-lg …">
```

**2. Delete the share row** (`:75-107`, D8). Removes 4 tab stops per card, `pt-2.5`, `hover:bg-gray-900/10`,
`hover:text-gray-900`, and ~90 lines of duplicated SVG. Share already exists, better, in `donation-card.blade.php`.

**3. Collapse three links to one** (§3.2). The image link (`:9`), title link (`:32`) and CTA (`:68`) all point at the
same URL. Use the stretched-link pattern:

```blade
<div class="group relative flex h-full flex-col …">
    <div class="relative aspect-[4/3] bg-surface-muted">…image, no <a>…</div>
    <h3 class="…">
        <a href="{{ route('campaigns.show', $campaign->slug) }}#donate"
           class="after:absolute after:inset-0 group-hover:text-link">{{ $campaign->title }}</a>
    </h3>
    …
    <span aria-hidden="true" class="…button styles…">Support this campaign →</span>
</div>
```

The CTA becomes a presentational `<span>` covered by the stretched anchor — one tab stop, one accessible name (the
title), the whole card clickable. The badges keep `pointer-events-none` so they do not block it.

**4. Honest CTA** (§2.4). "Donate Now" → **"Support this campaign"**, and the href gains `#donate`. Combined with
PR 2.3's anchor this lands the donor on the form rather than the top of the page.

Card tab stops: **7 → 1**. Homepage total: 128 → ~65.

**Acceptance:** rail CTAs align to the pixel; keyboard Tab moves card-to-card; screen reader announces the campaign
title as the link name; hovering anywhere on the card shows the hover state.

**Effort:** 5h. **Regression risk:** medium — this component renders on 5 pages. Snapshot-test the rendered markup.

---

### PR 2.3 — Restructure the campaign detail donation column *(§2.8)*

`resources/views/public/campaigns/show.blade.php:81-86`, `components/campaigns/donation-card.blade.php`

The sticky card measures **839.8px**; usable viewport below the 64px header on a 1366×768 laptop is ~704px. It cannot
stick, so desktop donors below the fold have no campaign-specific CTA at all.

**1. Move "Spread the Word" out of the sticky card.** `donation-card.blade.php:52-81` → a standalone
`<x-campaigns.share-block>` rendered in the left column *below the story*, where sharing is a plausible next action
anyway. Removes ~180px (more, once §1.4's 58px icons shrink to 24px).

**2. Split the sticky region.** Only the amount + progress + form sticks:

```blade
<div class="lg:col-start-3 lg:row-start-1 lg:row-span-2">
    <div class="lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto">
        <x-campaigns.donation-card … id="donate" />
    </div>
</div>
```

`max-h` + internal scroll guarantees the CTA is reachable at any viewport height. Target: **≤620px** so it sticks
outright on a 768px laptop.

**3. Add `id="donate"`** — the anchor PR 2.2 links to. `tokens.css:236` already gives every `[id]` a
`scroll-margin-top` clearing the header, so this needs no extra work.

**Acceptance:** at 1366×768 the card is fully visible below the header and remains pinned while the story scrolls;
`/campaigns/{slug}#donate` lands with the amount presets in view.

**Effort:** 5h.

---

### PR 2.4 — Reconcile the donor count with the donor wall *(§2.2, D7)*

The highest-value fix in the plan per hour spent, because it is a **correctness** bug on a trust surface.

`Campaign::$donor_count` is a stored column (seeded 214); `$recentDonors` in `CampaignController::show()` derives from
`donations` where `status = succeeded` (empty). The page renders `Donors (214)` above `Be the first to donate.`

**1. One writer.** Audit every write to `donor_count`. It should be recalculated in exactly one place — the donation
`succeeded` transition — as a distinct-donor count over succeeded donations. Add a
`php artisan campaigns:recount` command for backfill and reconciliation.

**2. One reader.** `show()` computes `$donorWall` already; derive the displayed count from the same query rather than
from the column, or assert they agree.

**3. Handle the legitimate divergence.** Anonymous donors are excluded from names but must still count. When
`donor_count > 0` and the wall is empty, the empty state must say so, not contradict itself:

```blade
@empty
    @if ($campaign->donor_count > 0)
        <li class="px-4 py-4 text-sm text-content-muted">All donors to this campaign have chosen to stay private.</li>
    @else
        <li class="px-4 py-4 text-sm text-content-muted">Be the first to donate.</li>
    @endif
@endforelse
```

**4. Fix the seeder.** `DemoCampaignSeeder` sets `donor_count` and `raised_amount` without creating donations. Either
create matching `Donation` rows or stop seeding the derived fields. Seeded demo data that contradicts itself is how
this shipped.

**Acceptance:** new Pest test — a campaign with N succeeded donations reports N in the heading, the sidebar, and the
wall length; a campaign with only anonymous donations shows the private-donor copy, never "Be the first".

**Effort:** 6h (mostly backend + seeder). **Tests:** `tests/Feature/Campaigns/DonorCountTest.php`, new.

---

## Wave 3 — Visual hierarchy & trust (2 days)

### PR 3.1 — Hero scrim below `lg` *(§2.1)*

`home.blade.php:36-48`. The current scrim is horizontal (`bg-gradient-to-r`), built for the two-column desktop layout.
Below `lg` the layout stacks full-width and the alpha at the paragraph's right edge is ~3% — the copy sits on bare
photograph from **390px to 1023px**, i.e. every phone and tablet. `06-UI-UX-FOUNDATION.md §2` prohibits exactly this.

Replace the single gradient with two, breakpoint-switched:

```blade
{{-- mobile/tablet: bottom-up scrim, text sits on the opaque lower half --}}
<div class="absolute inset-0 bg-gradient-to-t from-[#fcf9f2] via-[#fcf9f2]/85 via-60% to-[#fcf9f2]/20 lg:hidden"></div>
{{-- desktop: the existing left-to-right scrim, text in the left column --}}
<div class="absolute inset-0 hidden bg-gradient-to-r from-[#fcf9f2]/85 via-[#fcf9f2]/40 via-45% to-transparent lg:block"></div>
```

Also in this PR:

- `object-right` → `object-[75%_center]` below `lg` so the subject is not pushed off-frame when the scrim covers the
  lower half.
- **Delete the "Kindness Counts" sticker** (`:126-134`, D5).
- `min-h-[500px]` → `min-h-[28rem] lg:min-h-[32rem]`. 500px on a 667px phone is 75% of the viewport before any
  content.

**Acceptance:** measure the computed background alpha behind the paragraph at 390 / 768 / 1023px — must exceed 0.80.
Every character of the hero paragraph legible at all three.

**Effort:** 4h.

---

### PR 3.2 — CTA colour hierarchy *(§2.3)*

`06-UI-UX-FOUNDATION.md §2` records the decision: green is the donate button, amber is the secondary accent. The
homepage inverts it — every donate CTA is amber while the header's is green.

| Site | Now | After | Why |
|---|---|---|---|
| `home.blade.php:83,115` hero CTA | `accent` | **`primary`** | it is the page's primary conversion action |
| `home.blade.php:183` impact-stats Donate Now | `accent` | **`primary`** | |
| `card.blade.php:68` card CTA ×10 | `accent` | **`primary`** | |
| `home.blade.php:274` Give Monthly | `accent` | `accent` *(keep)* | legitimately secondary to one-off giving |
| `home.blade.php:429,434` CSR / Internship | `accent` | **`secondary`** | not donation actions at all |
| `show.blade.php:172` modal close | `accent` | **`secondary`** | *(done in PR 1.3)* |
| `public-footer.blade.php:44` Subscribe | inline `--accent-400` | `accent` via `<x-button>` | drops two inline `style=` attributes that bypass the token system entirely |

Result: green means "this leads to giving", amber means "adjacent offer", brand-tinted means "other". One rule, legible
on sight.

**Acceptance:** visual diff of `/` and `/campaigns` at 1280px; no `variant="accent"` on any control whose destination
is the donation flow; no inline `style=` in `public-footer.blade.php`.

**Effort:** 3h.

---

### PR 3.3 — Suppress empty sections; fix the featured rail *(§2.7, §3.3, §3.6, §3.8)*

**1. Empty sections on the campaign page** (§3.6). `Updates` and `Donors` render a heading and a shrug — ~200px of
visible emptiness — and the section nav invites the donor to click into them. Follow the pattern Products already gets
right at `show.blade.php:110`:

- Build the nav from a filtered array rather than five hard-coded `<a>`s, so a suppressed section drops from both
  places at once.
- Keep `Donors` when `donor_count > 0` (PR 2.4 gives it real copy); drop it when the campaign has no donors at all.

**2. Featured rail affordance** (§2.7). `scrollWidth: 1200` vs `clientWidth: 358` with no arrows, no fade, no
"View all". The project already has `.scroll-rail` in `tokens.css:273` with a mask-image edge fade, used only by the
portal nav. Apply it here, plus:

- `role="region" aria-label="Featured campaigns" tabindex="0"` so the rail is keyboard-scrollable and announced.
- A "View all" link in the section header, matching "Recent Campaigns" directly below it.
- Desktop arrow buttons (`scrollBy({left: cardWidth})`), `hidden lg:flex`.

**3. Browse by Cause column count** (§3.3). Six categories in `lg:grid-cols-4` → an orphaned row of two. Reuse the
`match()` pattern already at `home.blade.php:166-172` for the stat tiles, so the column count follows the data
instead of being guessed.

**4. Zero-progress campaigns in featured slots** (§3.8). `₹0 · 0 donors · 0% funded` in the third featured position
inverts the social proof the card exists to provide. In `HomeController`, order featured campaigns by
`donor_count desc` as a secondary sort; and in `card.blade.php`, when `donor_count === 0`, replace the empty progress
bar with a `Just launched — be the first supporter` badge.

**Acceptance:** campaign page with no updates/donors renders neither the section nor its nav entry; the rail announces
itself and scrolls by keyboard; six categories render 3+3; no card shows an empty progress bar.

**Effort:** 6h.

---

## Wave 4 — Performance & accessibility (2 days)

### PR 4.1 — Image pipeline *(§3.1)*

~1.7 MB of PNG, ~950 KB of it above the fold.

| Asset | Now | Action |
|---|---|---|
| `hero-volunteer.png` | 603 KB | AVIF + WebP + PNG fallback via `<picture>`; `srcset` at 640/1024/1600/2400; `fetchpriority="high"`; **no** `loading="lazy"` (it is the LCP) |
| `hero-community.png` | 792 KB | same, lazy (footer) |
| `logo-horizontal.png` | 193 KB | → SVG. It is a logotype. |
| `logo-mark.png` | 152 KB | → SVG, **same file**, see below |

**The double-download** (confirmed in the network log): `layout/public.blade.php:160-161` renders two `<img>` and
hides one with `hidden sm:block` / `sm:hidden`. `display:none` does not stop the fetch — **both 345 KB arrive on every
page of the site.** Collapse to one element:

```blade
<picture>
    <source media="(min-width: 640px)" srcset="{{ asset('images/branding/logo-horizontal.svg') }}">
    <img src="{{ asset('images/branding/logo-mark.svg') }}" alt="{{ $orgName }}" width="180" height="32" class="h-8 w-auto">
</picture>
```

Add a build step (`sharp` in a small `scripts/optimise-images.mjs`, or commit pre-generated derivatives) so this does
not regress. Add a CI size budget: fail if any file in `public/images/` exceeds 150 KB.

**Also:** `preconnect` to `checkout.razorpay.com` on campaign pages, and load `checkout.js` (`show.blade.php:332`)
only when `$campaign->status->acceptsDonations()` — it currently loads on closed campaigns too.

**Acceptance:** above-the-fold image weight < 200 KB (from ~950 KB); Lighthouse LCP on `/` under 2.5s on Slow 4G;
network log shows one logo request.

**Effort:** 6h.

---

### PR 4.2 — Accessibility sweep *(§6)*

Each is small; batching them keeps the review coherent.

| # | Fix | Location |
|---|---|---|
| a | Donor tabs → `role="tablist"` / `role="tab"` / `aria-selected` / `aria-controls`, arrow-key roving tabindex | `show.blade.php:214-217` |
| b | FAQ buttons → `aria-expanded` / `aria-controls`; `±` glyph → `aria-hidden` SVG chevron | `show.blade.php:247-254` |
| c | Sort filters → `aria-current="true"` on the active pill | `campaigns/index.blade.php:8-12` |
| d | Verified-NGO icon → stroke path currently rendered with `fill="currentColor"`, painting a blob. Use `fill="none" stroke="currentColor"`. | `show.blade.php:60` |
| e | `pb-24 lg:pb-0` on the campaign page container so the 69px fixed mobile donate bar stops covering the footer | `show.blade.php` root |
| f | Section nav → `aria-label="Campaign sections"` | `show.blade.php:92` |
| g | Rail → `role`/`aria-label`/`tabindex` | *(done in PR 3.3)* |

**Acceptance:** axe-core clean on `/`, `/campaigns`, `/campaigns/{slug}` at 390px and 1280px; full keyboard pass on
the campaign page reaching every control including the donate CTA.

**Effort:** 5h. **Tooling:** add `@axe-core/cli` and a smoke run over the three URLs.

---

### PR 4.3 — Full-bleed technique *(§3.10)*

`home.blade.php:36,262` use `w-screen` + `margin-left: calc(50% - 50vw)`. `100vw` includes the scrollbar; `100%` does
not — on Windows Chrome/Edge/Firefox this produces ~15px of horizontal overflow and a scrollbar on the homepage. Not
reproducible under overlay scrollbars (this audit's environment), deterministic on affected platforms.

Extract to `<x-layout.full-bleed>` and fix once:

```css
/* tokens.css — global rules */
.full-bleed {
  width: 100%;
  margin-inline: calc(50% - 50vw);
  max-width: 100vw;
}
html { overflow-x: clip; }   /* `clip`, not `hidden` — preserves position: sticky */
```

**Acceptance:** `document.documentElement.scrollWidth === clientWidth` on `/` in a browser with classic scrollbars.

**Effort:** 2h.

---

## Wave 5 — Content, discovery, polish (2–3 days)

### PR 5.1 — Populate the dark sections and the settings *(§5)*

**8 of the homepage's 15 sections render nothing today.** This is content work, not code, but it is on the critical
path to launch and the code has never been exercised against real data.

| Section | Gate | Needed |
|---|---|---|
| Hero banner | `Banner` | ≥1 row (the fallback copy is currently what ships) |
| Who We Serve | `homepage.serve_heading/_body/_image` | 3 settings |
| Monthly promo | `homepage.monthly_heading/_body` | 2 settings |
| How to Donate | `homepage.steps` | 4 steps |
| Featured In | `PressMention` | rows + logos |
| Blog / Gallery / Partners / Certificates | 4 models | rows |

Also unset: `org.whatsapp` (float never renders), `org.phone`, `org.address_line1`, all five `social.*`,
`org.80g_number` / `org.12a_number` / `org.registration_number` (the footer trust strip is empty).

**Blocking for launch:** `show.blade.php:152-168` hard-codes `AACTV1234F20231`, `AACTV1234F20214` and
`IN/2023/0349210` as literals in a Blade template, presented to donors as legal verification. Move to
`org.*` settings and render from there. Placeholder legal identifiers must not be reachable in production.

**Effort:** 4h code (settings wiring + seeder), content effort separate.

---

### PR 5.2 — Category placeholder images *(§3.7)*

Zero of eight campaigns has a cover image, so **100% of cards** render a ~300×225px grey box with the category name.
`06-UI-UX-FOUNDATION.md §1` item 6 lists "8 category placeholder images" as a Phase 0 deliverable; they were never
produced.

Add `campaign_categories.placeholder_image_path`, fall back to it in `card.blade.php:12-14` and `show.blade.php:46-48`
before falling back to the grey box.

**Effort:** 3h code, plus asset production.

---

### PR 5.3 — Listing search and filters *(§2.9, §4.6, §4.7)*

`home.blade.php:450-454` emits a `SearchAction` pointing at `/campaigns?search={term}`. `CampaignController::index()`
reads only `sort` and `page` — the parameter is silently ignored, and there is no search input anywhere.

- Search input on `/campaigns`, bound to a `search` query param; `where('title','like',…)` plus category name.
- Category filter chips alongside the existing sort pills.
- Result count: "Showing 1–12 of 34".
- Extend `paginateCached()`'s cache key with `search` and the category filter. **Watch this** — the current key is
  `campaigns.index.{sort}.{page}`; adding params without adding them to the key serves the wrong page.

**Acceptance:** `?search=blanket` filters; the JSON-LD claim becomes true; cache keys are distinct per filter
combination (test this explicitly).

**Effort:** 6h. **Tests:** `tests/Feature/Campaigns/CampaignSearchTest.php` including the cache-key case.

---

### PR 5.4 — Motion and orientation *(§3.4, §3.5, §3.9, §4)*

The token file defines a full motion system that these two pages barely use.

- **Scrollspy** on the campaign section nav (§3.9) — `IntersectionObserver` in `app.js`, same pattern as
  `initStickyHeaders()`. Active item gets the same scale-x underline the header nav uses.
- **Progress-bar reveal** (§4.8) — `x-progress-bar` already animates `scaleX` over `duration-slow`; it renders at its
  final value with the transition never running. Start at `scaleX(0)`, animate on intersect.
- **Impact-stat count-up** (§4.9) — the tiles are already width-locked at `home.blade.php:156` explicitly "to avoid
  CLS if a count-up animation is ever added".
- **Deadline / urgency** (§4.5) — `ends_at` exists and drives the "Ending soon" sort but is never rendered. Add
  "14 days left" to the card and detail page. Highest-leverage scarcity signal available, already in the schema.
- **Heading font policy** (§3.4) — pick one: `font-heading` on all section `<h2>`s or none. Currently 3 of 12 have it,
  which reads as accidental. Record the rule in `08-DESIGN-SYSTEM.md`.
- **Section rhythm** (§3.5) — all 15 sections are `mb-12`. `tokens.css:150-152` defines `--space-12/16/24` as
  "section padding, mobile / tablet / desktop"; use `mb-12 sm:mb-16 lg:mb-24` and let the major sections breathe.
- **Breadcrumb consistency** (§4.11) — campaign detail skips the `Campaigns` level the category page includes, in
  both the visible trail and the JSON-LD at `show.blade.php:297-301`.

All motion is already covered by the global `prefers-reduced-motion` block at `tokens.css:213`.

**Effort:** 8h.

---

## Sequencing

```
Wave 1  PR 1.1 ──▶ PR 1.2 ──┬─▶ PR 1.3
(1.5d)                       │
                             ▼
Wave 2  PR 2.1 ─▶ PR 2.2 ─▶ PR 2.3 ─┐   PR 2.4 (independent, backend)
(2.5d)                               │
                                     ▼
Wave 3  PR 3.1   PR 3.2   PR 3.3 ────┘
(2d)    (all three independent of each other)

Wave 4  PR 4.1   PR 4.2   PR 4.3     (independent; 4.2 wants 3.3 merged)
(2d)

Wave 5  PR 5.1   PR 5.2   PR 5.3   PR 5.4
(2–3d)  (5.2 blocks nothing; 5.1 blocks launch)
```

**Hard dependencies:** PR 1.2 before everything visual (otherwise you are styling classes that do not exist).
PR 2.2 before PR 2.3 (the `#donate` anchor needs the card's href). PR 3.3 before PR 4.2's rail item.

**Parallelisable:** PR 2.4 (backend) can run alongside all of Wave 2's frontend. Wave 5's content work (PR 5.1) should
start on day 1 regardless — it has the longest lead time and blocks launch.

---

## Test strategy

| Layer | Additions |
|---|---|
| **CI gate** | Dead-class check already wired (`ci.yml:53`); harden extractor (PR 1.1), add pre-push hook, add image size budget (PR 4.1) |
| **Pest — feature** | `DonorCountTest` (PR 2.4), `CampaignSearchTest` incl. cache-key isolation (PR 5.3), settings-driven credentials render (PR 5.1) |
| **Pest — Livewire** | `DonationFormTest`: preset ↔ amount sync on mount and on select (PR 2.1) |
| **Blade snapshot** | `x-campaigns.card` markup — it renders on 5 pages and PR 2.2 restructures it |
| **axe-core** | Smoke run over `/`, `/campaigns`, `/campaigns/{slug}` at 390 and 1280 (PR 4.2) |
| **Manual, per PR** | The measured acceptance numbers stated in each PR above — chip widths, icon sizes, z-order, scrim alpha, tab-stop count |

The audit's measurements are the regression baseline. Re-run them after each wave; they are all one
`javascript_tool` call.

---

## Risk register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| `-top-3` does not generate off the replaced spacing scale | Medium | Low | Verified by the guard in PR 1.2's acceptance; fall back to `-top-[12px]` |
| Stretched-link card (PR 2.2) breaks badge/hover behaviour on 5 pages | Medium | Medium | Snapshot test; `pointer-events-none` on overlay badges; manual pass on all 5 |
| `donor_count` recount (PR 2.4) touches the payment success path | Low | **High** | Recount runs after the status transition, never inside it; `campaigns:recount` for backfill; existing donation tests must stay green |
| Adding `search` to `paginateCached()` without the cache key (PR 5.3) | Medium | High | Explicit cache-key test; the existing key already had this shape of trap |
| `overflow-x: clip` on `html` (PR 4.3) breaks `position: sticky` | Low | Medium | `clip` (not `hidden`) preserves sticky; verify header, section nav and sidebar all still pin |
| Image pipeline regresses at deploy (PR 4.1) | Medium | Low | CI size budget; `<picture>` degrades to PNG |

---

## Definition of done

1. `npm run build && npm run check:classes` → `✓ No dead classes`, green in CI on `develop`.
2. Every audit finding 🔴 and 🟠 is closed, or explicitly deferred with a reason recorded in this file.
3. `docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md` re-run at 390 / 768 / 1280 with the measured numbers updated.
4. axe-core clean on the three audited URLs.
5. Above-the-fold image weight < 200 KB; one logo request per page.
6. No placeholder legal identifiers (80G / 12A / Darpan) reachable in any template.
7. `08-DESIGN-SYSTEM.md` updated for the two scale additions (`text-6xl`, `font-mono`) and the heading-font policy.
