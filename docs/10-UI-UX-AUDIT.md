# UI/UX Audit — Vision Good Work Global Foundation

**Date:** 2026-08-01 · **Branch:** `develop` · **Scope:** public site, donor portal, auth, Filament custom views
**Method:** static review of all 60 Blade views against `docs/06-UI-UX-FOUNDATION.md` + `docs/08-DESIGN-SYSTEM.md`, a
production Tailwind build (`npx vite build`) diffed against every class the templates reference, and live browser
inspection of the rendered pages with computed styles measured in-page.

---

## Executive summary

The design system in `resources/css/tokens.css` and `docs/08-DESIGN-SYSTEM.md` is genuinely good — three-layer tokens,
measured contrast ratios recorded per colour pair, a deliberate decision to *replace* rather than *extend* Tailwind's
scales so drift is impossible. That last decision is correct, and it is also the root of this audit's biggest finding.

**Because the scales were replaced, any class outside them produces no CSS at all — silently.** The templates
reference **44 distinct utility classes that generate nothing**, across **142 occurrences**. There is no build error,
no console warning; the class just evaporates. The result is not "slightly off" styling — it is missing padding,
missing colour, and, on the primary donate button, **unreadable text on the single most important control in the
product.**

| Severity | Count | Headline |
|---|---|---|
| 🔴 Critical | 6 | Donate CTA text renders at **2.90:1** — fails WCAG AA. Verified in-browser. |
| 🟠 High | 9 | Page content collides with the sticky header; every status pill is unstyled; form inputs are unlabelled |
| 🟡 Medium | 12 | Two container widths, four button paddings, 13 documented components never built |
| 🔵 Enhancement | 15 | Motion, visual feedback, and conversion improvements |

**The product currently ships essentially zero motion.** Across all non-scaffold views there is exactly one
`transition-colors` (in `<x-button>`) and one `x-transition` (the mobile drawer). The token file defines a complete
motion system — `--duration-fast/base/slow`, two easing curves, a global `prefers-reduced-motion` block — and none of
it is used. Section 6 covers what to add.

---

# 1. 🔴 Critical

## 1.1 The primary CTA is unreadable — `text-on-action` does not exist

The token is exposed by `tailwind.config.js:53-58` as `action: { DEFAULT, hover, active, on }`, which produces the
class **`text-action-on`**. Eleven templates write **`text-on-action`** instead. The class generates nothing, so the
button text falls back to the inherited body colour.

Measured live in the browser on the campaign page:

```
button "Donate ₹1,000"
  color            rgb(33, 38, 31)    ← --neutral-900, inherited from <body>
  background-color rgb(31, 122, 77)   ← --brand-500
  contrast ratio   2.90:1             ← WCAG AA requires 4.5:1
```

Intended pairing (`--color-on-action` = white on `--brand-500`) measures **5.32:1** and is recorded as such in
`tokens.css:24`. The system knows the right answer; the class name is simply misspelled.

`<x-button>` at [components/button.blade.php:8](resources/views/components/button.blade.php:8) uses the *correct*
`text-action-on` — which is why the auth pages look right and everything else does not. The 11 broken sites:

| File | Line | Element |
|---|---|---|
| [layout/public.blade.php](resources/views/components/layout/public.blade.php:80) | 80 | Header **Donate** button — on every public page |
| [layout/portal.blade.php](resources/views/components/layout/portal.blade.php:41) | 41 | Unread-notice count badge |
| [donations/donation-form.blade.php](resources/views/livewire/donations/donation-form.blade.php:170) | 170 | **The donate submit button** |
| [public/home.blade.php](resources/views/public/home.blade.php:46) | 46, 65, 149, 214 | Hero CTA, Donate Now, Give Monthly, Subscribe |
| [public/donate/success.blade.php](resources/views/public/donate/success.blade.php:15) | 15 | Give again |
| [public/donate/failed.blade.php](resources/views/public/donate/failed.blade.php:13) | 13 | Try again |
| [public/contact/show.blade.php](resources/views/public/contact/show.blade.php:49) | 49 | Send message |
| [public/fundraiser/show.blade.php](resources/views/public/fundraiser/show.blade.php:71) | 71 | Submit request |

Two more places use it inside Alpine `:class` bindings, where a grep-based fix will miss them:
[campaigns/show.blade.php:126-127](resources/views/public/campaigns/show.blade.php:126) (donor tabs) and
[campaigns/index.blade.php:11](resources/views/public/campaigns/index.blade.php:11) + `category.blade.php` (filter pills).

**Fix:** global replace `text-on-action` → `text-action-on`. Then add a CI guard (§7).

## 1.2 Page content renders underneath the sticky header

[`layout/public.blade.php:116`](resources/views/components/layout/public.blade.php:116) sets `py-10` on `<main>`.
The spacing scale is `0, px, 1, 2, 3, 4, 6, 8, 12, 16, 24` — **there is no `10`**. Measured: `padding-top: 0px`.

The header is `sticky top-0 h-16`, so the first line of every public page sits flush against it. On the homepage the
`<h1>` is visibly clipped. Same class of bug at:

- `p-10` ×6 — every "no campaigns yet" empty state and the no-banner hero fallback have **zero padding**
  ([home.blade.php:62,119](resources/views/public/home.blade.php:62), `blog/index:13`, `campaigns/index:17`,
  `campaigns/category:25`, `campaigns/monthly-giving:9`)
- `mb-10` ×7 — every section on the campaign detail page ([campaigns/show.blade.php](resources/views/public/campaigns/show.blade.php:72))
  has no bottom margin, so Products / Story / Updates / Donors / FAQ run together with no separation
- `px-5` ×4, `mt-10` ×1

**Fix:** `py-10`→`py-12`, `p-10`→`p-12`, `mb-10`→`mb-12`, `px-5`→`px-6` — or add `10` to the spacing scale if the
6→8→12 jump is genuinely too coarse. Prefer the former; the coarse scale is deliberate.

## 1.3 Every status pill in the portal is invisible

Tailwind's default palette was replaced, so `bg-green-100`, `text-red-700`, `bg-amber-50`, `text-gray-500`,
`bg-blue-100`, `bg-gray-100` and friends **do not exist**. ~60 occurrences reference them. Confirmed absent from the
production build.

Affected — each renders as plain body-coloured text on a transparent background, so *succeeded*, *failed*, *halted*
and *revoked* are typographically identical:

- [portal/donations/index.blade.php:64](resources/views/portal/donations/index.blade.php:64) — donation status
- [portal/subscriptions/index.blade.php:27,67](resources/views/portal/subscriptions/index.blade.php:27) — subscription + charge status
- [portal/documents/index.blade.php:26](resources/views/portal/documents/index.blade.php:26) — revoked vs active
- [public/verify.blade.php:4,35,45](resources/views/public/verify.blade.php:4) and `verify-receipt.blade.php` — **the
  public document-verification result.** A donor checking whether an 80G receipt is genuine sees the same visual
  treatment for VALID and REVOKED.

This is the most consequential instance: verification is a trust surface, and it currently communicates its result
through wording alone.

Correct tokens already exist and are measured: `bg-success-bg text-success-text` (8.60:1), `bg-danger-bg
text-danger-text` (7.23:1), `bg-warning-bg text-warning-text` (7.81:1), `bg-info-bg text-info-text` (6.40:1).
`portal/notices/index.blade.php:18` already uses them correctly — it is the one file that got this right.

## 1.4 Validation errors on the donation form do not render as errors

Every inline error in [donation-form.blade.php](resources/views/livewire/donations/donation-form.blade.php:69) uses
`text-red-600` (×8, dead). Errors render in near-black body colour at `text-xs`, visually indistinguishable from the
helper text directly above them.

Compounding it: the `conflict` error banner (line 7) uses `bg-red-50 text-red-700` and the price-change and
80G/anonymous warnings (lines 13, 21, 145) use `bg-amber-50 text-amber-800` — all dead. **A donor whose order total
changed sees an unstyled paragraph of text, not a warning.**

Also missing: `city`, `state` and `pincode` have no `@error` block at all
([lines 122-133](resources/views/livewire/donations/donation-form.blade.php:122)), so validation failures on those
fields are silently swallowed — the form simply refuses to submit with no visible reason.

**Fix:** `text-danger` for inline errors; `<x-alert variant="warning">` (§4) for banners; add the three missing
`@error` blocks.

## 1.5 Form inputs have a 1.48:1 border — WCAG 1.4.11 failure

`tokens.css:78-81` is explicit:

> Two tokens, deliberately: 3:1 is required for a control boundary, and `--color-divider` does not meet it.
> `--color-border` (3.71) — inputs, selects, any control edge · `--color-divider` (1.41) — decorative rules ONLY

**Every hand-written input in the product uses `border-line-divider`.** Measured live: `rgb(217, 212, 198)` on white =
**1.48:1** against the 3:1 requirement. 38 raw `<input>` elements across the donation form, contact form, fundraiser
form, newsletter, and the portal PAN form.

`<x-form.input>` uses the correct `border-line` — but it is used in only 20 places (the auth pages) versus 38 raw
inputs everywhere else.

## 1.6 Inputs are 14px and 38px tall — iOS zoom + touch-target failure

Measured on the donation form: `font-size: 14px`, `height: 38.3px`.

`tokens.css:130` marks `--text-base` (16px) as the **"HARD FLOOR for mobile inputs"** — below 16px, iOS Safari
zooms the viewport on focus, and the donor has to pinch back out mid-checkout. `tailwind.config.js:196` defines
`min-h-touch: 44px` for exactly this; the raw inputs never apply it.

The campaign filter pills are worse — measured **22.3px tall** with `padding-top: 0px`, because `py-1.5` is off-scale
([campaigns/index.blade.php:10](resources/views/public/campaigns/index.blade.php:10)). Half the minimum target size.
Checkboxes measure **16×16px**.

---

# 2. 🟠 High

## 2.1 The campaign anchor nav slides under the header

[campaigns/show.blade.php:53](resources/views/public/campaigns/show.blade.php:53) — `sticky top-0 z-10`.
Measured: nav `z-index: 10`, `top: 0`; header `z-index: 200`, `height: 64px`. The nav sticks *behind* the header and
is never visible once scrolled.

Two rules broken at once. `docs/08-DESIGN-SYSTEM.md:215` reserves `--z-sticky-nav: 100` for this exact element and
says *"No raw z-index anywhere else."* And `top-0` should be `top-16` to clear the 64px header.

**Fix:** `sticky top-16 z-sticky-nav`.

## 2.2 The mobile sticky donate bar was specified but never built

`--z-donate-bar: 300` exists in `tokens.css:177`. `docs/06-UI-UX-FOUNDATION.md:275` specifies it for 360–639px, and
`:182` works out the layering against the WhatsApp float. **No template implements it.**

On mobile the donation card renders inline at
[campaigns/show.blade.php:49](resources/views/public/campaigns/show.blade.php:49), roughly one screen down. Below
that come Products, the trust section, stats, Story, Updates, Donors, FAQ and related campaigns — the entire rest of
the page, with **no donate CTA anywhere.** Verified by scrolling the live page at 375px.

For an India-first NGO where most traffic is mobile, this is the single highest-value missing feature in the audit.

## 2.3 Not one form input is associated with its label

Across all views: **1** `<label for=…>`, **32** labels without. **2** inputs with `id`, **38** without.

```blade
{{-- donation-form.blade.php:87 — current --}}
<label class="mb-1 block text-xs text-content-muted">Name *</label>
<input type="text" wire:model="name" class="…">
```

Screen readers announce these inputs as unlabelled. Sighted users can't click the label to focus the field. The whole
donation form, contact form, fundraiser form and portal PAN form are affected.

`<x-form.input>` already wires `aria-invalid` and `aria-describedby` correctly — the components exist and are simply
bypassed.

## 2.4 Required fields are marked with a bare `*`

`Name *`, `PAN *`, `Address *` — no `aria-required`, no `required` attribute, and no legend explaining what `*` means.
`<x-form.label :required>` renders `<span class="text-danger">*</span>`, which is at least colour-coded, but is
likewise unused outside auth.

## 2.5 Interactive widgets have no ARIA state

Zero occurrences of `aria-expanded`, `aria-controls`, or `aria-current` in the entire codebase.

- **FAQ accordion** ([campaigns/show.blade.php:158](resources/views/public/campaigns/show.blade.php:158)) — buttons
  toggle a panel with no `aria-expanded`; the `+`/`−` is announced as literal text
- **Donor tabs** (line 126) — styled as tabs, no `role="tab"` / `aria-selected` / arrow-key navigation
- **Mobile drawer** ([layout/public.blade.php:96](resources/views/components/layout/public.blade.php:96)) — no
  `role="dialog"`, no `aria-modal`, **no focus trap, and no Escape-to-close.** Keyboard users tab straight through the
  open drawer into the page behind it
- **Campaign filter pills** — the active sort has no `aria-current="page"`
- **Header nav** — no `aria-current` on the active section

## 2.6 The drawer close button is a text glyph

[layout/public.blade.php:102](resources/views/components/layout/public.blade.php:102) — `<button>✕</button>`.
No dimensions, so the tap target is the ~12px glyph itself. `docs/08-DESIGN-SYSTEM.md` §9 specifies icons; this is a
Unicode character. Same pattern for `−`/`+` in the FAQ accordion and `&#10003;`/`&#10007;`/`&#8987;` on the
donate result pages.

## 2.7 Result-page status icons have no size and no colour

`donate/success.blade.php:3`, `failed.blade.php:3`, `monthly/{authorized,declined,redirecting,confirming}.blade.php`,
`donation-pending.blade.php:2` all use `h-14 w-14 … bg-green-100 text-green-700`. **All four classes are dead** —
`14` is off-scale and the palette colours don't exist. The circle collapses to the glyph's intrinsic size with no
background. Every payment-outcome page is affected.

## 2.8 There is no skip link and no landmark structure

One `sr-only` in the entire app (the footer logo). No skip-to-content link, so keyboard users tab through 6 nav links
+ Login + Donate on every page load. `<main>` exists but has no `id` to skip to.

## 2.9 The horizontal card rail collapses to a single card

`w-72` is dead (`72` is off-scale), used at
[layout/public.blade.php:99](resources/views/components/layout/public.blade.php:99) (drawer width),
[featured-carousel.blade.php:6](resources/views/components/campaigns/featured-carousel.blade.php:6) and
[home.blade.php:80](resources/views/public/home.blade.php:80). The featured-campaigns rail shows one shrink-wrapped
card instead of a scrollable row — visible in the homepage screenshot. The mobile drawer has no fixed width and
sizes to its content.

---

# 3. 🟡 Medium — consistency

## 3.1 Two container widths

`max-w-container` (1200px, from `--container-max`) is used by the header, footer and `<main>`. But
`campaigns/show.blade.php:9`, `campaigns/index.blade.php:2`, `category`, `blog/index` and `monthly-giving` set
**`max-w-6xl`** (1152px) on their inner wrapper. Measured: `main` 1200px, inner `div` 1152px.

Result: page content is inset 24px from the header and footer edges on wide screens — the logo and the `<h1>` below it
do not align. `max-w-6xl` survives only because `maxWidth` is in `extend`, so Tailwind's defaults leak through the
otherwise-sealed system.

Full `max-w` inventory: `md` ×10, `none` ×8, `container` ×7, `6xl` ×6, `lg` ×4, `4xl` ×3, `2xl` ×3, `xl` ×1.
Eight different widths for what should be three roles (page / prose / card).

## 3.2 Seventeen distinct primary-button class strings

Every button is hand-written. Sampled from public + portal + livewire:

```
rounded-md bg-action px-4 py-2 text-sm font-semibold text-on-action
inline-block rounded-md bg-action px-5 py-3 text-sm font-semibold text-on-action
w-full rounded-md bg-action px-4 py-3 text-sm font-semibold text-on-action
mt-3 inline-block rounded-md bg-action px-4 py-2 text-sm font-semibold text-on-action
rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white          ← bg-brand has no DEFAULT
rounded-md bg-brand px-3 py-1.5 text-sm font-semibold text-white
```

Padding inventory across all views: `px-3 py-2` ×33, `px-4 py-3` ×27, `px-4 py-2` ×19, `px-3 py-1` ×7,
`px-2 py-0.5` ×5, `px-5 py-1.5` ×4, `px-4 py-1.5` ×3, `px-3 py-1.5` ×3, `px-5 py-3` ×2, `px-5 py-2.5` ×2 — **ten
different button sizes.** `docs/06-UI-UX-FOUNDATION.md:293` specifies `<x-donate-button>` with *three*.

None of the hand-rolled buttons carry `transition-colors`, `hover:`, `active:` or `min-h-touch`. Only `<x-button>`
does — and it is used 7 times against 30 raw `<button>` elements.

**`<x-button>` is hard-coded `w-full`**, which is exactly why nobody uses it: it cannot be placed inline. That single
constraint is the root cause of the button drift.

## 3.3 `bg-brand` — a token that doesn't exist

Used ×6 (`portal/donations:26`, `portal/subscriptions:46`, `donate/monthly/{authorize,authorized,declined,redirecting}`).
`brand` is a primitive *ramp* — `brand-50` … `brand-900` with no `DEFAULT` key. These buttons render with no
background, and their `text-white` on the page background is invisible. **The "Resume monthly donation" and
"Authorize" buttons are effectively blank.**

Separately, `docs/08-DESIGN-SYSTEM.md` states reaching for primitive ramps in a template "is a code-review finding" —
`bg-brand-50` in `<x-button>`'s secondary variant does exactly that.

## 3.4 Thirteen documented components were never built

`docs/06-UI-UX-FOUNDATION.md:290` lists a component inventory. Built: `<x-button>`, `<x-form.input>`,
`<x-form.label>`, `<x-form.error>`, `<x-campaigns.card>`. **Missing:** `<x-progress-bar>`, `<x-donate-button>`,
`<x-trust-badge>`, `<x-stat-tile>`, `<x-testimonial-card>`, `<x-category-tile>`, `<x-share-buttons>`,
`<x-form.select>`, `<x-form.checkbox>`, `<x-form.money>`, `<x-empty-state>`, `<x-skeleton>`, `<x-alert>`, plus
`<x-modal>` and `<x-toast>` from §10.12–10.13.

Measured duplication that follows directly:

| Pattern | Copies | Divergence |
|---|---|---|
| Progress bar | 4 | `h-2` in card + donation-card, `h-1.5` in product-catalogue (dead → 0px tall), `w-32 h-1.5` in the Filament column |
| Share buttons | 2 | Card has WhatsApp + Facebook; donation-card has WhatsApp + Facebook + X. Docs specify 5 incl. copy-link |
| Empty state | 5 | Identical string, `p-10` (dead) in all five |
| Alert / banner | ~10 | Each with a different hand-picked, mostly non-existent colour pair |
| Trust badge | 3 | `bg-success-bg` in one place, `bg-action/10` in another, `bg-trust` (the actual token) nowhere |

`--color-trust-bg` / `--color-trust-text` are defined, measured at 10.08:1, and **used zero times.**

## 3.5 Stale scaffold: `welcome.blade.php`

223 lines of Laravel's default starter page. **Not referenced by any route.** Contains 40+ hard-coded hex values
(`#161615`, `#FF4433`, `#1915014a`…), a full set of `dark:` variants against a system where
`docs/08-DESIGN-SYSTEM.md` §11 disables dark mode by decision, and off-scale spacing throughout. It is also the single
largest source of noise in any class-linting pass. **Delete it.**

## 3.6 Filament panels are aligned, with one gap

`AdminPanelProvider:46-51` and `ManagerPanelProvider:43-48` both map primary/danger/warning/success/info to the exact
token hexes, and both call `->darkMode(false)`. Correct and consistent.

But the two custom Filament views bypass it:
[document-template-preview.blade.php:32](resources/views/filament/admin/document-template-preview.blade.php:32) and
[tables/columns/campaign-progress.blade.php:5-9](resources/views/filament/admin/tables/columns/campaign-progress.blade.php:5)
use `bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 bg-primary-500 text-gray-500` — none of which
exist in this build, and the `dark:` variants target a mode that is switched off.

## 3.7 Smaller items

- **`donate/monthly/authorize.blade.php`** — `py-2.5` and `px-5` both dead; the authorize/decline buttons have no
  vertical padding on the mandate-consent screen
- **Newsletter input** ([home.blade.php:213](resources/views/public/home.blade.php:213)) — `border-line-divider`,
  `text-sm`, no label, no `min-h-touch`; sits beside a `py-2` button so the two are different heights
- **Campaign card badge** — `py-0.5` dead, so the "Urgent" pill has horizontal but no vertical padding
- **`prose prose-sm`** on the campaign story ([show.blade.php:100](resources/views/public/campaigns/show.blade.php:100))
  — `@tailwindcss/typography` ships its own greys and link colours that were never mapped to the tokens, so
  admin-authored HTML renders in a different type system than the rest of the page
- **`space-y-2` / `divide-y`** are used but `space-y`/`divide` aren't in the replaced scales — they work only because
  they fall back to Tailwind v4 dynamic utilities, i.e. they are outside the system by accident, not design

---

# 4. Recommended component library

Building these six kills the majority of §3. Each is small.

**`<x-button>` — unblock it first.** Drop the hard-coded `w-full`; add `size` (`sm`/`md`/`lg` → `px-3 py-2` /
`px-4 py-3` / `px-6 py-3`, all with `min-h-touch`) and `full` props, and variants `primary` / `secondary` /
`ghost` / `danger`. Add `hover:`, `active:`, and `transition-colors duration-fast`. This one change replaces ~30 raw
buttons and 10 padding permutations.

**`<x-alert variant>`** — `info`/`success`/`warning`/`danger` mapped to the measured `*-bg`/`*-text` pairs, with
`role="alert"` for danger and `role="status"` otherwise. Replaces ~10 hand-rolled banners and fixes §1.3 and §1.4 at once.

**`<x-badge variant>`** — `success`/`danger`/`warning`/`neutral`/`trust`. Replaces every status pill; finally uses
`--color-trust-*`.

**`<x-progress-bar :percent :size>`** — one implementation, with `role="progressbar"` + `aria-valuenow` (currently
absent everywhere) and the text percentage the docs require.

**`<x-empty-state icon message :cta>`** — five copies today.

**`<x-form.field>`** — a wrapper that generates the `id`, wires `for`, `aria-describedby`, `aria-required`, applies
`border-line` + `text-base` + `min-h-touch`, and renders the error. This is the single highest-leverage accessibility
fix in the audit: it closes §1.5, §1.6, §2.3 and §2.4 together.

---

# 5. UX improvements by flow

## 5.1 Donation form — the highest-value surface

Beyond the defects already listed:

- **The button label lags the amount.** `wire:model="amount"`
  ([line 65](resources/views/livewire/donations/donation-form.blade.php:65)) is deferred, so typing ₹5,000 into
  "Other amount" leaves the button reading **"Donate ₹1,000"** until some other action forces a round trip. The most
  important number on the page is stale. Use `wire:model.live.debounce.300ms`.
- **Clicking the custom-amount field costs a network request.** `wire:click="$set('selectedPreset', null)"` on the
  input fires a Livewire round trip on every focus — and being `wire:click` rather than `wire:input`, it doesn't
  actually clear the preset when the user *types* after selecting one. Move it to `wire:input`.
- **Three-column name/email/phone inside a 380px sidebar.** `sm:grid-cols-3`
  ([line 85](resources/views/livewire/donations/donation-form.blade.php:85)) triggers at 640px *viewport*, but the
  form lives in a `lg:col-span-1` sidebar. Each field ends up ~60px wide. Stack them; it's a container-width problem,
  not a viewport one.
- **80G is pre-checked**, which expands six extra required fields (PAN, address ×2, city, state, pincode) before the
  donor has decided anything. Default it off and let the donor opt in.
- **No progressive disclosure.** Amount, monthly, identity, 80G, anonymity, message and terms are all visible at once
  — roughly 14 controls. Consider two steps: *amount* → *details*.
- **`alert()` on checkout-load failure** ([line 213](resources/views/livewire/donations/donation-form.blade.php:213))
  — a browser modal at the moment of payment. Use the `<x-alert>` component inline.
- **No amount formatting as you type.** `₹50000` should read `₹50,000`.
- **No suggested-amount framing.** "₹1,000 feeds a family for a week" converts materially better than a bare number,
  and `campaign_products` already carries the data to generate it.

## 5.2 Campaign page

- Ship the **mobile sticky donate bar** (§2.2)
- The anchor nav doesn't highlight the current section — add scroll-spy with `aria-current`
- Donor lists are capped with no "show more" and no count of what's hidden
- `Str::limit($update->body, 300)` truncates updates with no expand affordance
- No campaign-level urgency signal (days remaining / "₹X to go") despite `is_urgent` and `goal_amount` existing

## 5.3 Portal

- Both tables ([donations](resources/views/portal/donations/index.blade.php:47),
  [documents](resources/views/portal/documents/index.blade.php:7)) are `<table>` with no responsive treatment — they
  overflow horizontally on mobile with no scroll hint. Use a card layout below `sm`.
- No `<caption>` or `scope="col"` on any table header
- `onsubmit="return confirm(…)"` for cancelling a monthly donation
  ([subscriptions:50](resources/views/portal/subscriptions/index.blade.php:50)) — a native `confirm()` for an
  irreversible, money-related action. Use a proper modal (`--z-modal` is already allocated).
- Portal nav is 5 links + Log out with **no mobile treatment at all** — it will overflow at 375px. The public site has
  a drawer; the portal has nothing.
- The unread-notice badge uses `px-1.5 py-0.5` (both dead) → no padding, plus `text-on-action` → invisible count.
- Portal and public headers are structurally different (mark vs horizontal logo, `py-4` vs `h-16`, muted vs default
  link colour) for no stated reason.

## 5.4 Trust & conversion

- `--color-trust-bg` / `--color-trust-text` are defined, measured, and **never used** — the 80G/verified badges use
  three other treatments instead
- Payment-method names in the footer are plain text (`UPI`, `Visa`, `Mastercard`) where donors expect logos
- No copy-link share option, and no "Copied" toast — `docs/08-DESIGN-SYSTEM.md:402` explicitly requires *"never a
  silent success"*
- Campaign cards show `₹X raised` and `%` but not the goal; the sidebar shows all three. Same data, two treatments.

---

# 6. 🔵 Motion & visual feedback — what to add

**Current state: one `transition-colors` and one `x-transition` in the entire product.** The token file defines
`--duration-fast: 120ms`, `--duration-base: 200ms`, `--duration-slow: 320ms`, `--ease-out`, `--ease-in-out`, and a
global `prefers-reduced-motion` block that already neutralises everything — so this can be built safely and quickly.
`tokens.css:184` constrains it correctly: **animate `transform` and `opacity` only.**

### Tier 1 — feedback the product is currently missing

| Where | Add | Why |
|---|---|---|
| All buttons/links/cards | `transition-colors duration-fast` + `hover:` + `active:scale-[0.98]` | Nothing outside `<x-button>` responds to hover or press at all |
| Donate submit | Inline spinner + `aria-busy`, button width locked | The `wire:loading` text swap changes button width mid-click |
| Preset amount chips | 120ms bg/border transition on select | Currently an instant, unexplained jump |
| Progress bars | `transition-[width] duration-slow ease-out` + count from 0 on scroll-in | The single best "this is working" moment on a donation page |
| Form fields | Focus ring transition; error shake (transform-only); ✓ on valid blur | No feedback at all today |
| Toasts | Build `<x-toast>` — `z-toast` is already allocated, nothing uses it | Every success is currently a full page reload |
| Skeletons | `<x-skeleton>` with `animate-pulse` for Livewire loads | `docs` §10.11 specifies it; the catalogue re-renders with a blank flash |
| FAQ accordion | Height/opacity transition + rotating chevron | Content currently appears instantly |
| Drawer | Directional `x-transition` (slide from right) + scrim fade | Generic `x-transition` = a fade; it's a drawer |
| Card hover | `hover:shadow-md hover:-translate-y-px` | Cards are inert; nothing signals they're clickable |

### Tier 2 — polish

- Hero carousel: crossfade instead of the current instant `x-show` swap; pause on hover/focus; **the auto-advance
  `setInterval` at [home.blade.php:70](resources/views/public/home.blade.php:70) has no pause control, which is a
  WCAG 2.2.2 failure** (moving content >5s must be pausable)
- Impact stats: count-up on scroll-in — already anticipated by the `min-w-[7rem]` CLS lock and the `.tabular` class
- Donor tabs: slide the active indicator rather than swapping backgrounds
- Success page: a one-shot check-mark draw (respecting reduced motion)
- Section reveals: 200ms fade-up at 8px on scroll-in, staggered ~40ms across grid children
- Sticky header: add a subtle shadow once scrolled past 0

### Tier 3 — micro-detail

- Ripple/press state on the mobile donate bar
- Skeleton→content crossfade rather than a hard swap
- WhatsApp float: a single attention pulse on first load, then still
- Number transitions on the raised amount when a Livewire poll updates it

**One caveat:** the reduced-motion block in `tokens.css:205` sets `animation-duration: 0.01ms !important`. That kills
*decorative* animation correctly, but it will also break a count-up implemented as a CSS animation and any
`animation`-driven skeleton. Implement count-ups in JS with an explicit `matchMedia('(prefers-reduced-motion)')`
check that renders the final value immediately.

---

# 7. Prevention

The whole §1 class of bug — 142 dead classes, none of which produced a warning — is preventable with one CI step. The
build already produces the ground truth:

```bash
npx vite build && node scripts/check-dead-classes.mjs
```

Extract every class token from `resources/**/*.blade.php` and `app/**/*.php`, diff against the selectors present in
`public/build/assets/app-*.css`, and fail on any miss. Note that variant-prefixed classes (`hover:`, `sm:`) and
Tailwind v4's dynamic utilities (`grid-cols-3`, `z-10`) resolve fine and must not be flagged — match full tokens,
strip trailing pseudo-selectors.

Worth adding alongside it:

- A Blade lint rule rejecting raw palette names (`text-red-600`, `bg-gray-100`, `bg-brand`) outside `tokens.css`
- `docs/08-DESIGN-SYSTEM.md` §14 (Governance) already asks for this — it just isn't wired up

---

# 8. Suggested order of work

**Phase 1 — one day, mostly find-and-replace**
1. `text-on-action` → `text-action-on` (11 sites, incl. 2 Alpine bindings) — fixes the 2.90:1 CTA
2. Off-scale spacing: `py-10`/`p-10`/`mb-10`/`px-5`/`py-1.5`/`py-0.5`/`h-14`/`w-14`/`w-72`
3. Raw palette → semantic status tokens (~60 sites) — fixes every status pill and the verification pages
4. `bg-brand` → `bg-action` (6 sites)
5. `border-line-divider` → `border-line` on controls only
6. Anchor nav → `top-16 z-sticky-nav`
7. Delete `welcome.blade.php`
8. Add the dead-class CI check so none of this returns

**Phase 2 — the components (§4)**
Unblock `<x-button>`, then `<x-form.field>`, `<x-alert>`, `<x-badge>`, `<x-progress-bar>`, `<x-empty-state>`.
Migrate the donation form first — it is the money path and the worst offender.

**Phase 3 — accessibility**
Label association, ARIA state on accordion/tabs/drawer, drawer focus trap + Escape, skip link, carousel pause,
`aria-current` on nav and filters.

**Phase 4 — mobile sticky donate bar, then motion tier 1.**

---

*Every measurement in this report was taken from a production build and a live browser session, not inferred from
source. Contrast ratios are computed from `getComputedStyle` values; class-existence claims are diffed against the
compiled stylesheet.*
