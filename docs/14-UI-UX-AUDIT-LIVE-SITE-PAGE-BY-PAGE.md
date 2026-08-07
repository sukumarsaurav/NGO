# UI/UX Audit — Live Site, Page by Page

**Date:** 2026-08-07 · **Scope:** every public page on `visiongoodworkglobalfoundation.org`, inspected live
**Focus:** what to improve, with emphasis on motion/animation and general polish — distinct from
`docs/11`/`docs/12`/`docs/13`, which covered dead classes, contrast, and composition-vs-reference. This audit
assumes those are fixed and looks at what's next.

**Starting fact, so the recommendations below are calibrated correctly:** the product currently ships almost
no motion. Grepping the whole template tree finds transitions on exactly the controls that got explicit
attention in earlier fixes (buttons, cards, the modal, form fields) — everything else is a hard cut. There is
no scroll-reveal, no count-up, no skeleton loading state, and navigation between pages is a full hard reload
(no `wire:navigate` anywhere, despite Livewire shipping it for free). `tokens.css` already defines a complete
motion system — `--duration-fast/base/slow`, two easing curves, a global `prefers-reduced-motion` block — that
is barely used. Every recommendation below spends that existing budget rather than inventing a new one.

---

## 0. Global — every page

These apply site-wide, so fixing them once pays out on every page below.

### Current state
- Every internal link is a plain `<a href>` — full page reload, blank flash, scroll position lost, on every
  click.
- Header/footer render identically everywhere; no active-page transition, no loading indicator during
  navigation.
- Livewire's default page-load progress bar isn't configured, isn't styled, and isn't triggered because
  nothing uses `wire:navigate`.
- Toasts/flash messages (newsletter subscribe, contact form submit) appear as plain text with no
  enter/exit animation — confirmed on `/contact` and the footer newsletter form.

### Recommendations
1. **Adopt `wire:navigate` on the header nav, footer nav, and campaign card links.** This is close to a free
   win — Livewire 3's navigate feature swaps only the `<body>`, preserves scroll behaviour intentionally,
   keeps persistent elements (the WhatsApp float, header) from re-mounting, and ships a configurable top
   progress bar. Configure it in brand colour:
   ```css
   [x-navigate-progress-bar] { background: var(--color-action) !important; }
   ```
   This single change makes every page-to-page click on the site feel instant instead of a hard reload —
   the highest-leverage item in this entire audit.
2. **Give flash messages a real transition.** Wrap the newsletter/contact success text in
   `x-show` + `x-transition:enter="transition duration-base ease-out" enter-start="opacity-0 -translate-y-1"`,
   matching the pattern already used for the "More" nav dropdown.
3. **A branded 404/500 page.** Not checked live (couldn't trigger one deliberately without risk), but worth
   confirming these use the same shell (header/footer) rather than Laravel's default error page — a donor
   who mistypes a campaign slug shouldn't land somewhere that looks like the site broke.

---

## 1. Homepage (`/`)

### Current state
Hero → Featured Campaigns → Impact Stats → Browse by Cause → Recent Campaigns → Testimonials →
CSR/Internship cards. Confirmed live: this is the full section list — no Gallery/Partners/Certificates/blog
teasers render (all empty-state gated, see `docs/12` §PR 5.1 — still a content gap, not a code gap).

### Issues
- **Impact stat numbers (48,500+, 1,240+, 6,800+, 95+) render instantly, fully formed, on page load.** These
  are the single most "look how much we've done" moment on the page and they get zero visual treatment —
  no count-up, nothing to make a visitor's eye stop on them.
- **The featured-campaign rail has no entrance.** Four cards materialise in their final position the instant
  the hero image finishes painting.
- **Progress bars on every campaign card render at their final width immediately.** `<x-progress-bar>`
  already animates via `scaleX` transition (`duration-slow`) — but only if the value changes *after* mount.
  On first paint there's nothing to transition from, so the fill just appears static.
- **Testimonials are three static text blocks in a row.** No attribution photo, no carousel, no stagger — they
  read as filler rather than as evidence.
- **The hero CTA and category tiles both use `hover:-translate-y-px` / colour-only hover** — functional, but
  a 1px lift is nearly imperceptible; not wrong, just under-tuned.

### Recommendations
1. **Count-up the impact stats on scroll into view.** `home.blade.php` already width-locks these tiles
   specifically "to avoid CLS if a count-up animation is ever added" (its own comment) — the hook is already
   there, it was never wired up. `IntersectionObserver` fires once per tile (reuse the exact pattern from
   `initStickyHeaders()` in `app.js`), then a small Alpine `x-data` counts from 0 to the target over ~800ms
   with an ease-out curve. Skip entirely under `prefers-reduced-motion` (the global CSS block already forces
   `animation-duration: 0.01ms` — the JS just needs to respect `matchMedia('(prefers-reduced-motion: reduce)')`
   before starting the count rather than animating and having CSS fight it).
2. **Stagger the featured-campaign cards in on scroll.** `x-intersect` (Alpine's built-in, no new dependency)
   + a 60ms stagger per card, `opacity-0 translate-y-2` → `opacity-100 translate-y-0`,
   `duration-base ease-out`. Four cards, ~240ms total stagger — fast enough not to feel like a delay, slow
   enough to register as intentional.
3. **Force a reflow so progress bars actually animate on first paint.** Render the bar at `scaleX(0)` in the
   initial HTML, then flip to the real value one tick after mount (a tiny Alpine `x-init="$nextTick(() =>
   ...)"` or an `IntersectionObserver` if the card is below the fold anyway — combine with #2 above, since
   it's the same trigger).
4. **Testimonials → a lightweight auto-advancing carousel** (or at minimum a manual one) with a donor photo
   placeholder (initials avatar is fine — doesn't need real photography) and a subtle
   `x-transition` cross-fade between slides. Three static paragraphs in a row is the weakest-performing
   pattern for social proof; even the initials-avatar + name + location treatment reads more like a real
   person than a bare paragraph.
5. **Category tiles: replace the 1px hover lift with something felt.** `hover:-translate-y-1 hover:shadow-md`
   (4px, matches the existing `shadow-md` token) plus the icon chip scaling slightly
   (`group-hover:scale-110`) reads as "this is clickable" far more clearly than a 1px shift most users won't
   consciously register.

---

## 2. Campaign listing (`/campaigns`) and category pages (`/causes/{slug}`)

### Current state
Sort pills (Newest / Most funded / Ending soon / Urgent), a grid of cards, pagination.

### Issues
- **Switching sort pills is a full page reload** — click "Most funded," lose scroll position, wait for a
  full round trip, for what is fundamentally a re-sort of data already visible.
- **No count.** "8 campaigns" or "Showing 1–8 of 8" costs one line and orients the user — currently absent
  (flagged in `docs/11` §2.9 for the homepage's search claim; applies here directly too).
- **No filter by category on this page** — only the six-tile grid on the homepage lets you narrow by cause;
  landing directly on `/campaigns` gives no way to do that without going back to `/`.
- **Grid entrance:** all cards appear simultaneously, same issue as the homepage rail.

### Recommendations
1. **Convert sort pills to a Livewire component with `wire:navigate`-free reactive sorting**, or at minimum
   add `wire:navigate` to the pill links so the sort re-render doesn't reload the header/footer/nav — this
   alone removes most of the perceived latency without a rewrite.
2. **Category filter chips alongside the sort pills**, sourced from `CampaignCategory`, an actual gap in
   functionality, not just polish (already flagged in `docs/12` PR 5.3).
3. **Same scroll-stagger treatment as the homepage rail** for the card grid — one shared Alpine/CSS utility,
   reused everywhere cards render in a grid (`home.blade.php`, `/campaigns`, `/causes/{slug}`,
   `/monthly-giving`, related-campaigns block).
4. **A skeleton state during the sort transition** (even a simple opacity-dim on the grid while the new sort
   loads) so re-sorting doesn't look identical to nothing happening.

---

## 3. Campaign detail (`/campaigns/{slug}`)

Composition already brought in line with the reference (`docs/13`). What's left is motion and micro-interaction,
not layout.

### Issues
- **The section nav (Products / Story / Donors / FAQ) has no active-section indicator.** The header nav one
  level up *does* have a scale-x underline that tracks the current page — the campaign page's own in-page nav
  got no equivalent treatment, so scrolling through a long story gives no sense of "you are here."
- **Anchor-jump on nav click is instant**, not smooth, despite `html { scroll-behavior: smooth }` being
  globally set in `tokens.css` — worth confirming this campaign page doesn't override it anywhere (spot check
  passed, smooth scroll does work; noting it as verified rather than assumed).
- **The quantity stepper on product cards (`− 0 +`) has no feedback on click** beyond the number changing —
  no scale-pulse, no colour flash, nothing that registers "that click landed."
- **The donor tabs (Recent / Most Generous) swap content with a hard cut**, no cross-fade, despite the
  underlying `x-show` already being present — it's just missing `x-transition`.
- **FAQ accordion opens instantly**, no height transition — `x-show` toggles `display: none` directly, no
  `x-collapse` (Alpine's official plugin, or a CSS grid-rows trick) for a smooth expand/collapse.
- **The "Verified NGO" / "Legal Audit Cleared" trust signals are static.** These are the page's core
  conversion argument and currently carry zero visual weight beyond colour and a small icon.

### Recommendations
1. **Scrollspy on the section nav.** `IntersectionObserver` watching each `<section id>`'s intersection with
   a narrow band near the top of the viewport, toggling an `aria-current`/active class on the matching nav
   link — same underline-wipe treatment (`scale-x-0` → `scale-x-100`, `duration-fast ease-out`) already
   defined for the header nav, reused rather than reinvented.
2. **Stepper feedback**: on `wire:click="increment"`, a quick `scale-110` pulse on the count (150ms,
   `ease-out`, back to 100%) — purely CSS via an Alpine `x-transition` keyed to the value, no JS animation
   library needed.
3. **`x-transition` on the donor tab panels** — `duration-fast` cross-fade, same pattern as the FAQ answers
   already use elsewhere, just not applied here yet.
4. **`x-collapse` (Alpine's official plugin — one more `<script>` tag, no new build tooling) on the FAQ
   answers** instead of the current `x-show` hard-cut. This is the single most noticeable "this feels cheap"
   moment on the page currently — an FAQ that snaps open is a very dated interaction pattern next to
   everything else that's now been polished.
5. **Give the trust badges (Verified NGO, Legal Audit Cleared, Tax Benefit) a subtle continuous or
   on-scroll-in cue** — not a distracting loop, but a one-time draw-in (icon scales from 0 with a slight
   overshoot, `cubic-bezier` bounce) the first time they scroll into view, so the page's core trust argument
   gets the visual emphasis its role deserves.
6. **Sticky mobile donate bar**: currently appears instantly once the campaign hero scrolls past. A
   `translate-y-full → translate-y-0` slide-in (`duration-base ease-out`) on first appearance would read as
   considerably more polished than the current hard cut.

---

## 4. Donation page (`/donate`) — highest-priority content gap on the site

### Current state
Confirmed live: a heading ("Make a donation"), one line of subtext ("Every contribution helps us continue our
work."), then straight into the amount-selection card. **That's the entire page.** Below the form: a large
empty gap, then the footer.

### Issues
- **This is the generic donation entry point — reached from the header's "Donate" button on every single
  page — and it carries none of the trust-building content a campaign page has.** No impact stats, no
  "Verified NGO" badge, no testimonials, no story, no explanation of where an undirected donation actually
  goes. A donor who clicks the header CTA (rather than a specific campaign) lands on the least persuasive
  page on the entire site.
- **No visual identity at all** — no image, no colour beyond the form card, nothing to distinguish this from
  a blank internal tool.
- Structurally this is the same `<x-campaigns.donation-card>`-style form without a `campaign_id` — it inherits
  none of the surrounding page context a campaign gets.

### Recommendations
1. **This needs real content, not just animation** — at minimum: a short paragraph on what an undirected
   donation funds (operating costs? gets allocated to the most urgent live campaign? this should be a real
   answer, not filler), the same "Verified NGO / 12A & 80G Registered / 100% Fund Transparency" trust strip
   the campaign page has, and the impact-stat tiles from the homepage reused here.
2. **A hero-lite treatment** — doesn't need the full homepage hero, but a simple header band (heading +
   subtext on a tinted or photo background, matching the monthly-giving promo band's treatment) would give
   this page a floor above "blank form."
3. Once the content exists, the same entrance/fade-in treatment as the campaign detail sidebar applies here
   too — but content is the actual gap, not motion.

---

## 5. Monthly Giving (`/monthly-giving`)

### Current state
Heading, one-line intro, grid of recurring-enabled campaigns (reuses `<x-campaigns.card>`).

### Issues
- **No explanation of *why* monthly giving matters**, beyond the single intro sentence. A donor comparing
  one-time vs. recurring has no reinforcement here — no "cancel anytime," "instant monthly receipt," "more
  predictable than one-time gifts" trio of reassurances, despite that exact language already existing
  elsewhere (the donation form's monthly checkbox subtext: "cancel anytime").
- Otherwise inherits every issue already listed for the campaign grid (§2).

### Recommendations
1. **A 3-icon benefit strip below the intro** — "Cancel anytime," "Instant monthly receipt," "Predictable
   support" — matching the "How to Donate" numbered-step tile pattern already built for the homepage. Cheap
   to add, meaningfully raises the page's persuasive floor.
2. Grid entrance/stagger, same as §2.

---

## 6. About, Privacy Policy, Terms (CMS pages via `/{slug}`)

### Current state
Confirmed live on `/about`: a heading and four paragraphs of plain text on an otherwise bare page. No image,
no section breaks, no visual hierarchy beyond bold headings within the prose.

### Issues
- **This is the page a donor checks before trusting the organisation with money, and it currently looks like
  an unstyled text file.** No team photo, no timeline, no mission/vision distinction, no stat reinforcement
  ("since [year], we've reached X families" — numbers that already exist on the homepage and could repeat
  here with more context).
- Applies equally to Privacy Policy / Terms, though those two are conventionally plain and that's
  acceptable — About specifically is a trust surface and deserves more.

### Recommendations
1. **Give the CMS "page" template an optional hero band and pull-quote treatment** — the underlying `Page`
   model likely doesn't need new fields to support a hero image (many CMS pages already have equivalent
   image fields elsewhere in the schema) — worth checking, and if not present, worth adding for `About`
   specifically rather than the generic template.
2. Repeat 2–3 of the homepage's impact numbers here with a sentence of context each ("48,500+ meals served
   since [founding year]") — reusing existing data, not inventing new content.
3. Fade-in on scroll for each paragraph block, subtle (`opacity-0 → 100`, no translate) — About pages
   read better with a small amount of pacing rather than everything visible at once.

---

## 7. Blog (`/blog`, `/blog/{slug}`)

Not exhaustively re-screenshotted this pass (structure already verified in earlier audits to mirror the
homepage's blog-card pattern) — currently empty (0 posts seeded), same content-gap class as Gallery.

### Recommendations
1. Once posts exist: cover-image lazy-load with a fade-in on load (`opacity-0` → `100` on the image's own
   `load` event, not scroll-triggered — avoids a flash of unstyled grey box).
2. Category badge + reading-time estimate on cards — reading time is a single `str_word_count($body) / 200`
   calculation, cheap to add, gives a concrete "this will take 4 minutes" signal blog cards currently lack
   entirely.

---

## 8. Gallery, Partners, Certificates

### Current state
All three confirmed empty on live (0 rows seeded in each). Gallery's empty state: "No photos yet — Check back
soon." Reasonable, honest copy.

### Recommendations (apply once content exists, per `docs/12` PR 5.1/5.2)
1. **Gallery: a proper lightbox**, not just a link out — click a thumbnail, see it full-size in an overlay
   (reuse the `<x-modal>` component built for the campaign page's credentials dialog — same focus-trap,
   same scrim, different content), with a subtle scale-up-from-thumbnail transition rather than a plain
   fade.
2. **Masonry or staggered grid** rather than a strict grid, if the photo set has mixed aspect ratios — worth
   deciding once real photos exist, not before.
3. **Partners/Certificates**: logo grid gets a grayscale → colour hover transition (already specified for
   the homepage's partner strip per earlier work — confirm the standalone `/partners` page matches rather
   than drifting into its own treatment).

---

## 9. CSR Partnership, Internship, Contact (inquiry forms)

### Current state
All three follow the same pattern: heading, short intro, 3-item benefit/requirement row, then a plain form.
Confirmed live on `/csr-partnership`: three benefit blurbs (Verified Impact / Mid-Year Benefits / Fallback
Reporting — text too small to read clearly in the screenshot, worth a font-size check), then the form.

### Issues
- **Forms have no field-level success state.** Submitting redirects/reloads rather than confirming inline —
  worth checking whether a validation error on one field forces a full-page re-render losing the donor's
  other typed answers (the pattern used on the donation form, `wire:model.live`, avoids this — these CMS
  forms may be plain POST forms without that protection).
- **No visual distinction between the three pages** beyond the heading — CSR Partnership, Internship, and
  Contact are three different asks (money/budget, time/skills, general inquiry) that currently look
  identical.
- Small/cramped benefit-row typography noticed on CSR Partnership specifically — worth a direct visual
  re-check at 100% zoom to confirm this isn't just a screenshot artifact.

### Recommendations
1. **If these are plain POST forms (not Livewire), convert to Livewire components** so a validation error
   doesn't cost the donor everything else they'd typed — matches the standard already set by the donation
   form.
2. **Differentiate the three pages visually** — even just a different accent icon per page (briefcase for
   CSR, graduation cap for Internship, envelope for Contact) in the header band gives each a distinct
   identity without a redesign.
3. **Inline success animation on submit** — a checkmark that draws in (`stroke-dashoffset` animation, same
   technique as the copy-link checkmark already used on campaign cards) rather than a full page reload to a
   generic "Thank you" page.

---

## 10. Auth pages (Login/Register) and donor portal

Not re-audited in depth this pass (lower traffic than the donation-conversion pages above, and portal is
behind auth). Noted for completeness:

- Login/Register forms should get the same `<x-form.field>` treatment already used everywhere else (spot
  check suggests they already do, via the shared component — low risk here).
- Portal dashboard (subscriptions, receipts, documents) would benefit from the same skeleton-loading pattern
  recommended for the campaign listing's sort transition — donor-facing tables that currently likely just
  pop into existence once data loads.

---

## 11. Priority order

Ranked by (conversion impact × effort), not just severity:

1. **`/donate` content gap (§4)** — this is the page the header's primary CTA sends every non-campaign-
   specific donor to, and it currently has the weakest trust signal on the site. Content work, not animation,
   but the highest-leverage item here.
2. **`wire:navigate` site-wide (§0)** — one change, felt on every single page, turns every navigation from a
   hard reload into an instant transition.
3. **Count-up impact stats + card stagger-in (§1)** — the hook already exists in the homepage's own code
   comments; cheap to wire up, highest-visibility motion win.
4. **FAQ `x-collapse` + donor-tab cross-fade + scrollspy (§3)** — the campaign detail page is where a
   donor spends the most deliberation time; these are the moments currently reading as "unfinished" next to
   everything else on that page that's now polished.
5. **CSR/Internship/Contact form robustness (§9)** — lower traffic, but a lost partnership or internship
   inquiry because a validation error wiped the form is a real cost, not just polish.
6. Everything else (§2, §5–8, §10) — content-gated or lower-traffic; sequence after the above once content
   (photos, blog posts, real About-page copy) exists to animate around.
