# UI/UX Audit — Homepage & Campaign Pages

**Date:** 2026-08-06 · **Branch:** `develop` @ `dc7a907` · **Scope:** `/` (homepage), `/campaigns`, `/causes/{category}`,
`/campaigns/{slug}` and the shared components they render (`x-campaigns.card`, `x-campaigns.donation-card`,
`livewire:donations.donation-form`, `x-layout.public`, `x-layout.public-footer`).

**Method:** static review of the Blade sources against `docs/06-UI-UX-FOUNDATION.md` and `docs/08-DESIGN-SYSTEM.md`; a
production Tailwind build (`npx vite build`) run through `scripts/check-dead-classes.mjs`; and live in-browser
inspection at 390 × (mobile), 1280 × (desktop) with computed styles and element geometry measured in-page.

This is a **follow-up** to `docs/10-UI-UX-AUDIT.md` (2026-08-01). The critical findings in that document —
`text-on-action`, `py-10`, unstyled status pills — are **fixed**. Everything below is new, and most of it landed with
the `6155a85` / `dc7a907` UI work.

---

## Executive summary

The system underneath these pages is sound. Tokens are three-layered with measured contrast ratios, the layout shell
handles sticky-header edge cases correctly, the donation form has genuinely careful state handling, and there is a CI
guard (`check-dead-classes.mjs`) purpose-built for this codebase's biggest failure mode.

**That guard is currently failing and nothing is watching it.** `node scripts/check-dead-classes.mjs` reports **35
classes generating no CSS across 68 occurrences**, and they are concentrated almost entirely in the two pages audited
here. The consequences are not cosmetic:

- The **"POPULAR" badge sits on top of the ₹1,000 amount** in the donation form, and the preset amount labels overflow
  their chips.
- The **80G tax-savings callout** — the most persuasive element in the donation flow — renders with a **near-black
  border on a transparent background** instead of the intended soft green. It reads as an error, not a benefit.
- Two icons render at **95×95px and 58×58px** instead of 14px and 20px, blowing out the trust-credentials row and the
  share block.
- The hero's heart divider is **invisible** (both rules compute to `width: 0`).

Separately, three issues are worth as much as all of the above combined:

1. **Hero body copy sits directly on the photograph with no scrim below 1024px** — the exact failure
   `06-UI-UX-FOUNDATION.md §2` says must never ship.
2. **Every donate CTA on the homepage is amber, the header's is green.** `06-UI-UX-FOUNDATION.md §2` records a
   deliberate decision that green is the donate button and amber is the secondary accent. The homepage inverts it.
3. **The campaign page shows "Donors (214)" directly above "Be the first to donate."** on a trust surface.

| Severity | Count | Headline |
|---|---|---|
| 🔴 Critical | 6 | Donation-form preset chips are visually broken; tax-savings callout reads as an error; modal renders under the header |
| 🟠 High | 9 | Hero text on bare photograph below `lg`; donor count contradicts donor list; two CTA colours for one action |
| 🟡 Medium | 11 | Ragged featured rail, 1.7 MB of PNGs, 128 tab stops on the homepage, orphaned category row |
| 🔵 Enhancement | 12 | Scrollspy, campaign-card image fallback, listing search, section rhythm |

---

# 1. 🔴 Critical

## 1.1 The donation form's amount presets are visually broken

`resources/views/livewire/donations/donation-form.blade.php:29-55`

Measured live in the desktop sidebar (viewport 1280, sidebar column 362.7px, radiogroup 262.7px):

| Chip | Chip width | Amount label width | Overflow | Sub-label render |
|---|---|---|---|---|
| ₹500 | 59.7px | 46.8px | — | `1…` |
| ₹1,000 | 59.7px | 60.4px | **+0.7px** | `Shelt…` |
| ₹2,500 | 59.7px | 63.0px | **+3.3px** | `1…` |
| ₹5,000 | 59.7px | 62.9px | **+3.2px** | `Critic…` |

Three things are wrong at once:

1. **`sm:grid-cols-4` in a 263px container.** The comment at line 130 of the same file explains that the name/email/
   phone fields were stacked precisely because `sm:` keys off the *viewport*, not the container, "so the three fields
   collapsed to roughly 60px wide each on exactly the screens with the most room to spare." The preset grid has the
   identical bug and was not fixed.
2. **`-top-2.5` is a dead class.** The "POPULAR" badge computes `top: 26.55px` (its static position) instead of
   `-10px`, so it renders **on top of the ₹1,000 amount**, obscuring the number on the chip the design is actively
   steering donors toward.
3. **The sub-labels are meaningless.** At 34–41px of usable width, "1 Week Food" renders as `1…` and "1 Month Medical"
   renders as `1…`. Two of the four chips carry an identical, empty label. The whole point of the tags — telling the
   donor what their money buys — is lost.

**Fix:** `grid-cols-2` at all widths for the preset grid (2×2 is what the mobile layout already does correctly, and it
is what fits the sidebar). Replace `-top-2.5` with an in-scale value or an arbitrary `-top-[10px]`. Move the impact tag
below the amount with room to render in full.

## 1.2 The 80G tax-savings callout renders as an error box

`donation-form.blade.php:69-74`

Intended: `border-emerald-200 bg-emerald-50/80 text-emerald-900`. All three are dead classes. Measured computed style:

```
background-color  rgba(0, 0, 0, 0)      ← transparent (intended: soft green)
border-color      rgb(33, 38, 31)       ← --neutral-900, near-black (intended: #a7f3d0)
color             rgb(33, 38, 31)       ← --neutral-900
```

The single most persuasive element in the donation flow — "Save approx. ₹500 in income tax" — appears as a hard
black-bordered box. In every other part of this product a bordered callout with an icon means *warning*. It is
reading as a caution at the moment the donor is deciding.

The correct semantic pair already exists and is measured: `bg-success-bg text-success-text` (8.60:1) per
`tokens.css:114`.

## 1.3 The NGO credentials modal renders underneath the sticky header

`resources/views/public/campaigns/show.blade.php:143`

```
modal   z-index: 50
header  z-index: 200
```

Verified in-browser: with the modal open, the header (logo, Donate button, hamburger) sits **fully crisp on top of the
modal's backdrop-blur scrim**, while everything else is blurred behind it. The Donate button is clickable through the
"modal".

`tokens.css:188` allocates `--z-modal: 600` for exactly this, and `tailwind.config.js` exposes it as `z-modal`. The
template used a raw `z-50` instead. The mobile donate bar at `show.blade.php:280` has the same problem — raw `z-40`
where `z-donate-bar` (300) is the allocated layer.

The modal also lacks the `x-trap.noscroll` that the mobile drawer in `layout/public.blade.php:275` correctly uses, so
it has **no focus trap and no body-scroll lock**. Two modal patterns in one codebase, one of them accessible.

## 1.4 Two icons render at 5–7× their intended size

| Element | Class | Intended | Measured |
|---|---|---|---|
| "View Credentials & Certificates" arrow (`show.blade.php:125`) | `h-3.5 w-3.5` | 14×14 | **95.19 × 95.19** |
| Share tile icons ×4 (`donation-card.blade.php:57,61,65,75`) | `h-5 w-5` | 20×20 | **58.16 × 58.16** |

`3.5` and `5` are not in the spacing scale (`0, px, 1, 2, 3, 4, 6, 8, 12, 16, 24`), so both classes evaporate and the
SVGs fall back to intrinsic sizing. The credentials button's bounding box measures **192 × 95px** for what should be a
single line of link text, forcing "View Credentials & Certificates" to wrap to three lines beside a giant arrow. It is
the most conspicuous visual defect on the campaign page.

## 1.5 The hero's heart divider is invisible

`public/home.blade.php:65` and `:100`

```html
<div class="flex items-center gap-3 w-40 mb-6">
    <div class="h-[2px] flex-1 bg-brand-800/30"></div>
    ...heart...
    <div class="h-[2px] flex-1 bg-brand-800/30"></div>
</div>
```

`w-40` is dead. The container shrinks to its intrinsic 40px (16px heart + two 12px gaps), leaving **0px for both
rules**. Measured: `width: 0` on both. What renders is a lone heart glyph floating under the headline — it reads as a
rendering artefact, not a design element. Present in both the banner and no-banner branches.

## 1.6 `check-dead-classes.mjs` is failing, on unpushed work

`node scripts/check-dead-classes.mjs` → **35 classes, 68 occurrences**. Findings 1.1–1.5 are all instances of it.

The guard is correctly wired: `.github/workflows/ci.yml:53-55` runs `npm run check:classes` after the build, on every
push and PR to `main` and `develop`. It would have caught all of this.

It has not run, because **the work that introduced these regressions was never pushed.** `origin/develop` is at
`c5fae35`; the local branch is two commits ahead at `dc7a907`, and those two commits (`6155a85` "add Gallery,
Partners, Certificates, CSR Partnership and Internship", `dc7a907` "ui") are where essentially every dead class below
was introduced. The guard works; nothing has asked it.

Two gaps remain in the guard itself:

- **Negative-prefixed utilities are skipped.** `check-dead-classes.mjs:105` requires a token to match
  `^[a-z0-9]…`, so `-top-2.5` — the cause of §1.1's overlapping badge — is filtered out before it is checked.
- **No pre-push or pre-commit hook**, so the failure is only discovered at CI time, after the work is shared.

**Full list scoped to the audited surfaces:**

| File | Dead classes |
|---|---|
| `public/home.blade.php` | `text-gray-700` ×3, `text-gray-900` ×2, `lg:text-6xl` ×2, `w-40` ×2, `rounded-2xl`, `shadow-xl`, `sm:p-5`, `sm:py-20`, `gap-10`, `font-serif` |
| `public/campaigns/show.blade.php` | `text-emerald-800` ×3, `font-mono` ×3, `py-0.5` ×3, `border-emerald-200` ×2, `text-emerald-700` ×2, `bg-emerald-50` ×2, `shadow-2xl` ×2, `rounded-2xl`, `border-emerald-300`, `bg-gray-900/60`, `h-3.5`, `w-3.5`, `-top-2.5`\* |
| `components/campaigns/donation-card.blade.php` | `h-5` ×4, `w-5` ×4 |
| `components/campaigns/card.blade.php` | `pt-2.5`, `hover:bg-gray-900/10`, `hover:text-gray-900` |
| `livewire/donations/donation-form.blade.php` | `py-0.5`, `mt-0.5`, `p-2.5`, `shadow-xs`, `bg-emerald-50/80`, `text-emerald-900`, `border-emerald-200`, `text-emerald-700`, `-top-2.5`\* |
| `components/layout/public.blade.php` | `w-56` (More dropdown) |
| `components/layout/public-footer.blade.php` | `py-14` |

\* `-top-2.5` is not caught by the script's extractor (negative prefix) but is confirmed dead by computed style.

Note the pattern: **every `emerald-*` reference is dead.** Someone reached for Tailwind's default palette to express
"verified / safe", not realising the palette was replaced. The measured semantic equivalents (`success-bg`/
`success-text`, `trust`/`trust-text`) already exist and were used correctly elsewhere in the same files.

**Fix:** add `npm run check:classes` to CI after the build step, and extend the extractor to catch negative-prefixed
utilities.

---

# 2. 🟠 High

## 2.1 Hero body copy sits on the bare photograph from 390px to 1023px

`public/home.blade.php:36-48`

The scrim is `bg-gradient-to-r from-[#fcf9f2]/85 via-[#fcf9f2]/40 via-45% to-transparent` — a **horizontal** gradient
designed for the two-column desktop layout, where the text occupies the left 58%.

Below `lg` (1024px) the layout stacks to a single column and the text spans the **full width**. At 390px the paragraph
runs from x=16 to x=374; the gradient's alpha at x=374 is roughly **3%**. Verified visually: "We work for the
well-being of humanity by helping the underprivileged and spreading hope, love and care." renders directly over the
volunteer's shirt and the printed logo on it, with several words effectively unreadable.

`docs/06-UI-UX-FOUNDATION.md §2` states the rule explicitly: *"Hero copy sits on a gradient overlay
(`rgba(33,38,31,0.55)` upward) or in a solid panel — never directly on an uncontrolled image."* This is the exact
failure the doc calls out in the reference site.

The affected range — 390–1023px — includes every phone and every tablet.

**Fix:** switch to a vertical/bottom-up scrim below `lg` (`bg-gradient-to-t from-[#fcf9f2] via-[#fcf9f2]/85 to-transparent`
with the horizontal gradient reintroduced at `lg:`), or move the mobile hero copy into a solid panel below the image.

## 2.2 "Donors (214)" sits directly above "Be the first to donate."

`public/campaigns/show.blade.php:213-227`, `CampaignController::show()`

`$campaign->donor_count` is a denormalised column (seeded to 214). `$recentDonors` is derived from real `succeeded`
donations, of which there are none. The page therefore renders, in sequence:

```
Donors (214)
  [Recent] [Most Generous]
  Be the first to donate.
```

and, in the sidebar, `214 donors · 69% funded` above the same empty list.

This is on the page's trust surface, next to "Verified NGO", "Legal Audit Cleared" and a 12A/80G credentials modal. A
donor who notices it has been given a concrete reason to doubt every other number on the page.

**Fix:** derive the wall and the count from the same source, or render the wall's empty state as "Donor names are
private for this campaign" when `donor_count > 0` but the wall is empty. Do not let the two diverge silently.

## 2.3 Every donate CTA on the homepage is amber; the header's is green

`docs/06-UI-UX-FOUNDATION.md §2`, "Two corrections to the reference palette":

> **The donate button is green, not amber.** … So the roles swap. `brand-500` green carries white text at 5.32:1 and
> becomes the donate button; amber becomes the secondary accent.

Current state on the homepage:

| CTA | Colour |
|---|---|
| Header "Donate" (`layout/public.blade.php:233`) | green `bg-action` |
| Hero "Join Us in Making a Difference" (`home.blade.php:115`) | **amber** `variant="accent"` |
| Impact-stats "Donate Now" (`home.blade.php:183`) | **amber** |
| Campaign card "Donate Now" ×10 (`card.blade.php:68`) | **amber** |
| "Give Monthly" (`home.blade.php:274`) | **amber** |
| CSR / Internship CTAs (`home.blade.php:429,434`) | **amber** |
| Donation form "Continue" / "Donate ₹N" | green `variant` default |

So the same action is amber on the listing and green at the point of payment, and the page's *secondary* CTAs (CSR,
Internship) carry the same weight as its primary one. Amber passes contrast (measured 5.75:1 with dark text) — this is
a hierarchy problem, not a contrast one, but it dismantles the "green means donate" association the whole palette
decision was built around.

**Fix:** green for anything that leads to giving money; amber reserved for genuinely secondary actions (Give Monthly is
arguably a legitimate amber; CSR and Internship should probably be `variant="secondary"`).

## 2.4 Campaign card "Donate Now" does not donate

`components/campaigns/card.blade.php:68` — `:href="route('campaigns.show', $campaign->slug)"`.

The most prominent control on every card promises payment and delivers a page. On the campaign page the donor then has
to find the sidebar and start again. The label sets an expectation the click does not meet — a small trust cost paid
ten times on the homepage and twelve times per listing page.

**Fix:** either relabel to "View Campaign" / "Support this campaign", or link to `#donate` on the detail page so the
donation card is scrolled into view and focused on arrival.

## 2.5 "Legal Audit Cleared" and "Verified NGO" render without their verification colour

`show.blade.php:74` uses `text-emerald-700` (dead). Measured: `rgb(85, 82, 74)` — `--neutral-700`, identical to the
surrounding muted body text. The checkmark-plus-green treatment that makes it read as a *verified* badge is gone; it
renders as another grey metadata item beside "by Shanti Night Shelter Collective".

`show.blade.php:59-62`, the "Verified NGO" overlay badge, has the same problem (`text-emerald-800 border-emerald-300`,
both dead — it falls back to the `trust` variant, which is at least legible). Its icon is worse: the path
`M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z` is a **stroke** path rendered with `fill="currentColor"`, so it paints
as a solid green blob rather than a check-in-circle.

## 2.6 The featured-campaigns rail has ragged card heights

`home.blade.php:144-148`

```html
<div class="w-[18rem] flex-shrink-0"><x-campaigns.card :campaign="$campaign" /></div>
```

The flex wrapper stretches to the tallest sibling (measured 587.2px on all four), but the card inside has no `h-full`,
so it collapses to its own content height. Measured card heights: **587.2 / 566.4 / 566.4 / 587.2**. The "Donate Now"
buttons land at **y=1134.8 / 1114.0 / 1114.0 / 1134.8** — a **20.8px stagger** across a row of four.

The same component in a CSS grid (`/campaigns`, "Recent Campaigns") is fine, because there the card *is* the grid item.

**Fix:** `h-full` on the card root, or make the rail a grid with `auto-cols`.

## 2.7 The featured rail has no scroll affordance

`scrollWidth: 1200px` vs `clientWidth: 358px` at mobile, `1200` vs container at desktop. The fourth card is clipped
mid-width by the container edge with no arrows, no edge fade, no "View all" link, and no `role`/`aria-label` on the
scroll region. Compare `tokens.css:273` — the project already has a `.scroll-rail` helper with a mask-image edge fade,
built for exactly this and used only by the portal nav.

Note the "Recent Campaigns" section directly below *does* have a "View more" link. The more important section does not.

## 2.8 The sticky donation sidebar is taller than the viewport it sticks in

`show.blade.php:83` — `lg:sticky lg:top-24`. Measured card height: **839.8px**.

On a 1366×768 laptop the usable height below the 64px header is ~704px. The card cannot stick; it scrolls away
normally, taking the Continue button with it, and the donor has no persistent CTA on desktop (the sticky bottom bar is
`lg:hidden`). The stated benefit of the sticky sidebar is not delivered on the most common desktop size.

Contributors to the height: the 4-tile "Spread the Word" block (~180px, inflated by the 58px icons from §1.4) and the
80G callout.

**Fix:** move "Spread the Word" out of the sticky card (below the story, or collapse to one row of small icons); or
make the sticky region only the amount + progress + CTA.

## 2.9 The homepage advertises a search endpoint that does not exist

`home.blade.php:450-454` emits:

```json
"potentialAction": { "@type": "SearchAction", "target": "…/campaigns?search={search_term_string}" }
```

`CampaignController::index()` reads only `sort` and `page`. `?search=blankets` returns the unfiltered list. There is
also no search input anywhere on `/campaigns`.

This is both an SEO liability (a sitelinks searchbox that silently no-ops) and a genuine UX gap — 8 campaigns today,
but the listing has pagination at 12/page and four sort modes with no filtering by category or amount.

---

# 3. 🟡 Medium

## 3.1 ~1.7 MB of un-optimised PNGs, ~950 KB of it above the fold

| Asset | Size | Loaded on |
|---|---|---|
| `images/hero-volunteer.png` | **603 KB** | homepage hero (eager — correct, it is the LCP) |
| `images/hero-community.png` | **792 KB** | footer newsletter band — **every page** (lazy) |
| `images/branding/logo-horizontal.png` | **193 KB** | every page |
| `images/branding/logo-mark.png` | **152 KB** | every page |

Confirmed in the network log: **both logos download on every request.** `layout/public.blade.php:160-161` renders two
`<img>` elements and hides one with `hidden sm:block` / `sm:hidden` — CSS `display:none` does not prevent the fetch.
345 KB of logo for a 180×32 and a 32×32 render.

No WebP/AVIF, no `srcset`, no `fetchpriority="high"` on the LCP image. The compiled CSS is a well-behaved 91.9 KB
(15.9 KB gzip); the images are ~20× that.

**Fix:** AVIF/WebP with PNG fallback via `<picture>`; `srcset` on the hero; one logo element with a `<picture>` source
swap or a CSS background; `fetchpriority="high"` on the hero image. Realistic target: under 200 KB above the fold.

## 3.2 128 focusable elements on the homepage

Measured. Each campaign card contributes **7 tab stops**: cover-image link, title link, "Donate Now" link (all three
pointing at the *same* URL), plus WhatsApp / Facebook / X / copy-link. Ten cards render on the homepage = 70 of the
128. On `/campaigns` with a full page of 12 it is 84.

For a keyboard or screen-reader user, reaching the footer means passing three identical links per card. Share controls
are a secondary action given equal keyboard weight to the primary one, on a listing page where the user has not yet
chosen a campaign.

**Fix:** make the whole card one link (stretched-link pattern) with the title as the accessible name; move share out of
the listing card entirely — it is already present, better, on the detail page.

## 3.3 "Browse by Cause" leaves an orphaned row

Six seeded categories in a `lg:grid-cols-4` grid → 4 + 2, with two tiles floating left against ~50% empty row. At
`sm:grid-cols-3` it is 3 + 3 and balanced.

**Fix:** `sm:grid-cols-3 lg:grid-cols-3` (or `lg:grid-cols-6` with smaller tiles). The column count is content-
dependent — the same `match()` trick already used for `$statColsClass` at `home.blade.php:166-172` would solve it
properly.

## 3.4 Two heading treatments across the homepage's sections

| Font | Sections |
|---|---|
| `font-heading` (Montserrat) | Impact stats (§3), Monthly promo (§7), CSR/Internship (§15) |
| default sans (Inter) | Featured Campaigns (§2), Browse by Cause (§4), Recent Campaigns (§5), Who We Serve (§6), How to Donate (§8), Testimonials (§10), Blog (§11), Gallery (§12), Certificates (§14) |

Visible side by side: "Featured Campaigns" (Inter) and "Every contribution creates change" (Montserrat) are 400px
apart. `tokens.css:127-129` notes the heading font is applied "per-section (not a global h1-h6 sweep)" — that is a
reasonable policy, but the current distribution looks accidental rather than chosen.

## 3.5 Every section uses `mb-12` — the page has no rhythm

Fifteen sections, one spacing value. The result reads as a uniform list of equally-weighted bands with no signal about
what matters. `tokens.css:150-152` defines `--space-12 / 16 / 24` explicitly as "section padding, mobile / tablet /
desktop" — a three-step responsive rhythm that nothing on this page uses.

## 3.6 Sections with nothing to say still render

On the campaign page, "Updates" renders a heading plus "No updates yet — check back as this campaign progresses.", and
"Donors" renders a heading, two tab buttons, and "Be the first to donate." Together that is ~200px of visible emptiness
between the story and the FAQ, on a page whose job is to build confidence.

The section nav above still lists Updates and Donors, so the donor is explicitly invited to click into nothing.

**Fix:** drop the section (and its nav entry) when it is empty, as the Products section already correctly does at
`show.blade.php:110`.

## 3.7 Campaign cards with no cover image are 300px of grey

Zero of eight seeded campaigns has a `cover_image_path`. The fallback at `card.blade.php:12-14` is a
`bg-surface-muted` box with the category name centred in `text-content-muted` — at 3-column desktop that is a
~300 × 225px empty rectangle, and it is currently **100% of cards**. On `/campaigns` the grid is more grey than
content.

**Fix:** a per-category illustrated placeholder (the Phase 0 deliverable list in `06-UI-UX-FOUNDATION.md §1` item 6
specifies "8 category placeholder images" — they were never produced), or make the card compact when there is no image
rather than reserving the aspect ratio.

## 3.8 A ₹0 / 0-donor campaign sits in the third featured slot

"Life-Saving Surgery for Arjun, Age 6" renders `₹0` raised, `0 donors`, `0% funded`, empty progress bar — in the
featured rail and again in the listing. Social proof is the mechanism these cards exist for; a zeroed one in a
prominent slot inverts it.

**Fix:** either exclude 0-donor campaigns from *featured* ordering, or give new campaigns a different card treatment
("Just launched — be the first supporter") instead of an empty progress bar.

## 3.9 The section nav has no active state

`show.blade.php:92-102` — Products / Story / Updates / Donors / FAQ, sticky at `top-16`, styled as plain muted text
with a hover colour. No scrollspy, no current-section indicator, no underline. It does not read as navigation, and once
the donor is 2000px into the story it gives no orientation.

The header nav directly above it *does* have a scale-x underline indicator (`layout/public.blade.php:176-179`). Two nav
patterns, one of which was given the polish.

## 3.10 The `w-screen` full-bleed technique will horizontally scroll on desktop

`home.blade.php:36` and `:262` use `w-screen` with `margin-left: calc(50% - 50vw)`. `100vw` **includes the vertical
scrollbar width**; `100%` does not. On any platform with a classic (non-overlay) scrollbar — Windows Chrome/Edge/
Firefox, and Safari with "always show scrollbars" — this produces ~15px of horizontal overflow and a horizontal
scrollbar on the homepage.

Not reproducible in this headless run (`scrollWidth === clientWidth === 1280`, overlay scrollbars), but it is
deterministic on affected platforms.

**Fix:** `width: 100%` with `margin-inline: calc(50% - 50cqw)` on a container-query root, or the standard
`margin-left: calc(-50vw + 50%)` paired with `overflow-x: clip` on `<body>`.

## 3.11 `@livewireScripts` loads on the homepage, which has no Livewire component

`layout/public.blade.php:359` is unconditional. The homepage renders no Livewire component (the donation form and
product catalogue are campaign-page only), yet `livewire.js` is fetched and parsed. Alpine ships inside that bundle and
*is* needed for the drawer and the More dropdown — so this is not a pure win, but the two should be decoupled rather
than the whole Livewire runtime shipping to pay for a dropdown.

---

# 4. 🔵 Enhancements & conversion opportunities

1. **Scrollspy on the campaign section nav** — highlight the section currently in view. `IntersectionObserver` is
   already used in `app.js` for the sticky header; the same pattern applies.
2. **Anchor "Donate Now" to the form.** Card CTA → `/campaigns/{slug}#donate`, with `focus()` on the amount field.
   Removes one full click from every path to payment.
3. **Impact tags on the presets, done properly.** "₹500 feeds a family for a week" is the strongest copy in the
   donation form and it is currently truncated to `1…` (§1.1). Once the grid is fixed, this becomes the form's best
   asset.
4. **Persist the desktop CTA.** With the sticky sidebar not sticking (§2.8), desktop donors below the fold have no
   donate control except the header button, which goes to the *generic* donate page, not this campaign.
5. **Show the goal deadline / urgency.** `ends_at` exists and drives the "Ending soon" sort, but no card or detail
   page renders days-remaining. It is the single highest-leverage scarcity signal available and it is already in the
   schema.
6. **Add search + category filter to `/campaigns`** — and then the JSON-LD in §2.9 becomes true.
7. **Result count on the listing.** "8 campaigns" / "Showing 1–12 of 34" costs nothing and orients the user.
8. **Progress-bar animation on scroll-into-view.** `x-progress-bar` already animates `scaleX` with `duration-slow`
   and the reduced-motion guard is global; it currently renders at its final value with the transition never running.
9. **Count-up on the impact stats.** `home.blade.php:156` already width-locks the tiles "to avoid CLS if a count-up
   animation is ever added". It never was.
10. **Testimonials need attribution weight.** Three grey quote cards with no photo, no campaign link, no date. A photo
    and a link to the campaign the donor supported would make them verifiable rather than decorative.
11. **Breadcrumb inconsistency.** Campaign detail: `Home / {Category} / {Campaign}`. Category page:
    `Home / Campaigns / {Category}`. The detail page skips the Campaigns level that the category page includes, and the
    JSON-LD at `show.blade.php:297-301` encodes the same skip.
12. **The "Kindness Counts" hero sticker** is purely decorative, rotated `-2deg`, and at 1280px lands directly over the
    elderly woman's face. On mobile it lands below the CTA and adds a third competing element. It earns no click and
    costs the hero's focal point.

---

# 5. Content & configuration gaps

The homepage template defines **15 sections**. In the current database state, **8 of them render nothing**:

| Section | Gate | State |
|---|---|---|
| Hero banner | `Banner` | **0 rows** → falls back to hard-coded copy |
| Who We Serve (§6) | `homepage.serve_heading` | **unset** |
| Monthly promo (§7) | `homepage.monthly_heading` | **unset** |
| How to Donate (§8) | `homepage.steps` | **`[]`** |
| Featured In (§9) | `PressMention` | **0 rows** |
| From the Blog (§11) | `Post` | **0 rows** |
| Gallery (§12) | `GalleryPhoto` | **0 rows** |
| Partners (§13) | `Partner` | **0 rows** |
| Certificates (§14) | `Certificate` | **0 rows** |

Also unset: `org.whatsapp` (the float button never renders), `org.phone`, `org.address_line1`, all five `social.*`
keys (the footer's social row is empty), and `org.80g_number` / `org.12a_number` / `org.registration_number` (the
footer's trust strip is empty — while the campaign page's modal hard-codes `AACTV1234F20231` and
`IN/2023/0349210` as literals in the template).

Two consequences worth separating:

- **The live homepage is a shell.** It goes hero → featured → stats → causes → recent → testimonials → CSR/Internship.
  No "How to Donate", no trust logos, no gallery, no certificates. Every trust-stack element that
  `06-UI-UX-FOUNDATION.md §3` calls for is configured out.
- **The design has never been validated against real content.** Zero campaigns have cover images, zero have products,
  zero have updates, and the donor wall is empty everywhere. Several findings above (§3.6, §3.7, §2.2) exist because
  the empty-state path is what actually ships today.

**Hard-coded credentials in a template** (`show.blade.php:152-168`) should move to settings before launch — the values
shown to donors as legal verification are currently placeholder literals in Blade.

---

# 6. Accessibility summary

**Working well:** skip link, `aria-current="page"` throughout the nav, `x-trap.noscroll` on the drawer, the presets
correctly marked `role="radiogroup"` / `role="radio"` / `aria-checked`, `aria-busy` + spinner on the donate button,
`role="progressbar"` with `aria-valuenow`, offset focus rings that survive `:where()` specificity, a global
`prefers-reduced-motion` block, `scroll-margin-top` on `[id]`, single `<h1>` and clean `h1 → h2` order on both pages,
alt text present on every image.

**Gaps found:**

| Issue | Location |
|---|---|
| Modal has no focus trap, no scroll lock, renders under the header | `show.blade.php:143` |
| Scrollable rail has no `tabindex="0"`, `role`, or accessible name | `home.blade.php:144` |
| Donor Recent/Most-Generous tabs are plain buttons — no `role="tablist"` / `aria-selected` | `show.blade.php:214-217` |
| FAQ accordion buttons lack `aria-expanded` / `aria-controls`; the ±  is a text glyph, not marked decorative | `show.blade.php:247-254` |
| Listing sort filters are `<a>` styled as tabs with no `aria-current` | `campaigns/index.blade.php:8-12` |
| Verified-NGO check icon is a stroke path filled solid — renders as a blob | `show.blade.php:60` |
| 128 tab stops on the homepage; 3 duplicate links per card | §3.2 |
| No `pb` compensation for the 69px fixed mobile donate bar — footer content sits under it | `show.blade.php:280` |

No contrast failures were found. The one at-risk pair — hero copy on the photograph (§2.1) — fails because there is no
controlled background to measure against, which is worse than a measurable failure.

---

# 7. Prioritised action plan

**Sprint 1 — mechanical, ~half a day, unblocks everything else**

1. Push `develop` so the existing CI dead-class gate runs; extend its extractor to catch negative-prefixed
   utilities; add a pre-push hook. *(§1.6)*
2. Fix all 35 dead classes. The `emerald-*` ones map to `success-bg` / `success-text` / `trust`; the spacing ones
   round to the nearest scale step. *(§1.1–1.5)*
3. `z-50` → `z-modal`, `z-40` → `z-donate-bar`; add `x-trap.noscroll` to the credentials modal. *(§1.3)*
4. `grid-cols-2` on the preset chips; `h-full` on the campaign card root. *(§1.1, §2.6)*

**Sprint 2 — trust and hierarchy**

5. Vertical scrim on the hero below `lg`. *(§2.1)*
6. Reconcile `donor_count` with the donor wall. *(§2.2)*
7. Green for donate CTAs, amber for secondary. *(§2.3)*
8. Move "Spread the Word" out of the sticky sidebar so it can actually stick. *(§2.8)*
9. Hide empty Updates / Donors sections and their nav entries. *(§3.6)*

**Sprint 3 — performance and content**

10. AVIF/WebP + `srcset` + `fetchpriority`; single logo element. Target <200 KB above the fold. *(§3.1)*
11. Produce the 8 category placeholder images from the Phase 0 deliverable list. *(§3.7)*
12. Populate the 8 dark homepage sections and the org/social settings; move the hard-coded 80G/12A numbers into
    settings. *(§5)*
13. One card = one link; share moves to the detail page only. *(§3.2)*

**Sprint 4 — conversion**

14. Search + category filter on `/campaigns`, then the SearchAction JSON-LD becomes truthful. *(§2.9, §4.6)*
15. Scrollspy, deadline/urgency display, `#donate` anchoring, progress and stat animation. *(§4)*
