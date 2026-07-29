# 06 — UI/UX Foundation

**Phase 0 · Week 0** · Blocks: every sprint that renders HTML

## Why this document exists

The other docs specify the system in prose. Prose is enough for a webhook handler and not enough for
a donation form. Four separate surfaces render HTML in this project — the public site, the donor and
member portal, the admin panel, and the manager panel — and without a shared foundation they will
drift into looking like four different products built by four different people.

That matters more here than on most projects. `M10 §The trust stack` states the thesis plainly:
donation conversion is mostly a trust problem. A site that looks inconsistent reads as a site that
might not be careful with money.

This document defines the design foundation, the layout contract derived from the reference site, and
the two interaction specs that were previously spread across three modules and contradicted each
other.

**Two references, doing different jobs — do not mix them up:**

| | |
|---|---|
| **Structure** | <https://trueimpactfoundation.org/> — page layout, nav, homepage section order, footer. Not a pixel target. |
| **Brand** | <https://bright-minds-haven.vercel.app/> — colour palette and typography only (§2). Its navigation and page structure are **not** the model. |

---

## 1. What Phase 0 delivers

One week, before Sprint 1, producing artefacts every later sprint consumes:

| # | Deliverable | Consumed by |
|---|---|---|
| 1 | Tailwind design tokens (`tailwind.config.js` + CSS variables) | Everything |
| 2 | Filament v5 custom theme sharing those tokens | Sprints 1–16 admin work |
| 3 | Blade component library (§4) | Sprints 6, 9, 10, 11 |
| 4 | Public layout shell — header, mobile nav, footer, WhatsApp float | Sprint 6 onward |
| 5 | Wireframes: donation form, campaign detail, portal dashboard | Sprints 6, 9, 13 |
| 6 | 8 category placeholder images + default OG image | Sprints 9–11 |
| 7 | Schema amendments (`banners`, `contact_messages`, `homepage` settings) | Sprint 11 |

Items 1–4 are code and ship to `develop`. Items 5–6 are assets. Item 7 lands in
`02-DATABASE-SCHEMA.md` and `M02` before Sprint 1 starts.

> **Why not defer this to Sprint 11 as originally planned?** Because Sprint 6 builds the public
> donation page — the highest-stakes screen in the product — and Sprint 9–10 build campaign pages.
> Both would be built against a throwaway layout and then rebuilt. The donation form should be built
> once, on the real layout, by someone who has already decided what a button looks like.

---

## 2. Design tokens

One source of truth, consumed by the public site, the portal, and both Filament panels.

### Colour

**Source: <https://bright-minds-haven.vercel.app/> — approved as the brand reference.** Its palette is
adopted with two corrections documented below. Semantic names, not literal ones: `primary` may change,
but `text-brand-600` used in forty templates must not have to.

> **The values live in [`08-DESIGN-SYSTEM.md`](08-DESIGN-SYSTEM.md) §2, and the executable form in
> [`resources/css/tokens.css`](../resources/css/tokens.css).** This section keeps the *decisions* —
> which colour does which job, and why two of the reference's choices had to be corrected. Ramps,
> neutrals, status colours, and every measured contrast ratio are in the design system, once.

In summary: green `#1f7a4d` is the action colour and the donate button, amber `#e08a1e` is the
secondary accent carrying dark text only, brick `#b23a2e` is reserved for urgent campaign badges, and
the page sits on a warm off-white `#faf9f6`. Type is **Inter**, paired with Noto Sans Devanagari.

### Two corrections to the reference palette

**1. The donate button is green, not amber.** The reference site renders its primary "Donate Now" CTA
as white on `#e08a1e` — **2.68:1, a clear WCAG failure on the single most important control in the
product.** It cannot be fixed by darkening: the amber has to reach `#a5610b` before white clears
4.5:1, and by then it is brown.

So the roles swap. `brand-500` green carries white text at 5.32:1 and becomes the donate button;
amber becomes the secondary accent. Green also happens to read as *verified / safe* rather than
*attention*, which is the correct register for a control whose job is to feel trustworthy.

**Amber only ever carries dark text** (`#21261f`, 5.75:1). If a filled amber button with white text is
ever needed, use `accent-700` `#945b14`. **Never put text of any colour on `accent-600`** — it clears
neither white nor dark, and it is listed above only so nobody rediscovers it the hard way.

**2. Input borders use `border`, not `divider`.** The reference's `outline-variant` `#d9d4c6` sits at
1.41:1 on the page background. WCAG requires 3:1 for the boundary of an interactive control, so form
fields — every field on the donation form — use `#86816f` at 3.71:1. Keep `#d9d4c6` for decorative
section rules, where no minimum applies.

### One clash to stay aware of

`alert-500` `#b23a2e` (the reference's `tertiary`) and `danger` `#c0392b` are both mid-weight reds and
are near-indistinguishable side by side. Rather than re-pick one, constrain the usage: **brick appears
only on "Urgent" campaign badges**, which is semantically adjacent to an alarm anyway. It never
appears on anything neutral or positive. Everything genuinely error-shaped uses `danger`.

### Rules

**Contrast is a hard requirement, not a preference.** Every foreground/background pair must clear
4.5:1 (3:1 for text ≥ 24px, and 3:1 for interactive-control boundaries). All pairs in the tables above
were measured in Phase 0; re-measure any colour added later.

**Text over photography needs a scrim.** The reference site sets grey body copy directly over its hero
photograph, which is unreadable across much of the image. Hero copy sits on a gradient overlay
(`rgba(33,38,31,0.55)` upward) or in a solid panel — never directly on an uncontrolled image.

**Never signal with colour alone.** The progress bar carries a numeric percentage; the campaign
status pill carries a word; form errors carry text, not a red border.

### Type

A single sans-serif family with a Devanagari-capable companion, because the CMS supports Hindi
content (`00-PROJECT-OVERVIEW` — manual EN+HI entries) and a Latin-only webfont renders Hindi in an
unstyled fallback.

- Body: **Inter**, matching the brand reference. Self-hosted as a variable font — no Google Fonts CDN
  request on the critical path.
- Devanagari: Noto Sans Devanagari, subset, `font-display: swap`.
- Scale: `xs 12 · sm 14 · base 16 · lg 18 · xl 20 · 2xl 24 · 3xl 30 · 4xl 36 · 5xl 48`.
- Body copy never below 16px on mobile — iOS Safari zooms on focus for inputs under 16px, which
  reads as a bug on the donation form.

### Spacing, radius, shadow, z-index, motion

All defined with real values in [`08-DESIGN-SYSTEM.md`](08-DESIGN-SYSTEM.md) §4–7 — including the
z-index scale that resolves the "these two fixed elements must not overlap" problem in §3 below, which
a convention cannot solve and a scale can.

### The same tokens in Filament

Filament v5 accepts a custom theme. Generate it in Sprint 1 and point its colour registration at the
same palette. An admin panel that shares the public site's brand colour costs an hour in Phase 0 and
is impossible to retrofit cheaply once forty resources exist.

---

## 3. Layout contract

Derived from the reference site's structure. This is the skeleton every public page inherits.

### Header

Five primary items, plus auth. **Hard-coded in the Blade layout, not CMS-managed** — this is a
deliberate decision, recorded so nobody builds a menu manager for five links that change once a year.

```
[logo]   Explore Campaigns · Monthly Giving · Start a Fundraise · About · Blog   [Login] [Donate]
```

- `Donate` is a filled button, visually distinct from every other nav item, present at all breakpoints.
- Under `lg`, everything except the logo and `Donate` collapses into a hamburger drawer. **`Donate`
  never collapses into the drawer** — the primary conversion action does not hide behind a menu.
- Sticky on scroll with a shadow transition; `height` fixed so it never causes layout shift.

### Footer

Four regions, matching the reference site:

1. **Identity** — logo, one-paragraph description, social icons (from `social.*` settings)
2. **Explore** — Campaigns, Monthly Giving, Start a Fundraise, How to Donate
3. **Information** — About, Blog, Contact, Privacy Policy, Terms & Conditions
4. **Contact** — address, phone, email (from `org.*` settings)

**Bottom bar:** tagline, payment-method badges, registration numbers, copyright.

The registration numbers are a trust-stack element, not boilerplate. Render `org.registration_number`,
`org.12a_number`, and `org.80g_number` explicitly labelled. A donor checking whether an NGO is real
looks for exactly these.

Payment badges are static SVGs in `resources/images/payments/` (UPI, Visa, Mastercard, RuPay,
Net Banking). Do not hotlink gateway-hosted logos.

### Floating elements

- **WhatsApp button** — bottom-right, from `org.whatsapp`. Hidden if the setting is empty.
- **Sticky Donate bar** — campaign detail pages only, below `md`, appearing after the hero scrolls
  out. Contains amount and a single button. This is the highest-converting element on mobile.

Both are `position: fixed` and must not overlap. WhatsApp sits above the sticky bar when both render —
`z-float` (400) over `z-donate-bar` (300) in the scale at `08-DESIGN-SYSTEM.md` §6.

### Homepage section order

Twelve sections, in this order, matching the reference site's conversion logic — establish
credibility, then remove obstacles:

| # | Section | Source |
|---|---|---|
| 1 | Hero banners (rotating) | `banners` table |
| 2 | Featured campaigns | `campaigns.is_featured` |
| 3 | Impact stats | `impact_stats` |
| 4 | Browse by cause (8 tiles) | `campaign_categories` |
| 5 | Recent campaigns grid | `campaigns` |
| 6 | Who we serve | `homepage.*` settings |
| 7 | Monthly giving promo | `homepage.*` settings |
| 8 | How to donate (4 steps) | `homepage.*` settings |
| 9 | Featured in (press logos) | `press_mentions` |
| 10 | Testimonials | `testimonials` |
| 11 | Newsletter | `subscribers` |
| 12 | Footer | `settings` |

> **Correction to M10.** M10 previously stated all twelve sections were CMS-managed with nothing
> hard-coded. Five of them had no storage at all. Sections 6, 7 and 8 are now backed by a `homepage`
> settings group; section 1 by a new `banners` table. See §7.

### Campaign detail page

The highest-value page on the site, and the one the reference site has thought hardest about. Layout
verified against a live campaign page, not inferred.

**Two columns at `lg+`. Left scrolls, right sticks.**

```
┌──────────────────────────────────────┬──────────────────────────┐
│  H1 campaign title                   │  ┌ STICKY DONATION CARD ┐│
│                                      │  │ [Tax Benefit][Updates]││
│  ┌────────────────────────────────┐  │  │                      ││
│  │  Media carousel                │  │  │ RAISED SO FAR  DONORS││
│  │  video + images, arrows, dots  │  │  │ ₹1,98,083         168││
│  └────────────────────────────────┘  │  │ 20% Complete         ││
│                                      │  │ ▓▓▓▓░░░░░░ Goal ₹10L ││
│  ── sticky anchor nav ─────────────  │  │                      ││
│  Products  │  Story  │  Updates      │  │ [ ₹ 3000          ]  ││
│                                      │  │ (3000)(5000)(7000)…  ││
│  ▸ PRODUCTS — needs catalogue        │  │                      ││
│  ▸ KNOW YOUR NGO                     │  │ Pay via  UPI 💳 🏦    ││
│  ▸ Campaign impact stats             │  │                      ││
│  ▸ Summary paragraph                 │  │ [ ₹3000 Donate Now ] ││
│  ▸ STORY (video + rich text)         │  │                      ││
│  ▸ UPDATES timeline                  │  │ Share  f  x  in  ✆   ││
│  ▸ DONORS (Recent │ Most Generous)   │  └──────────────────────┘│
│  ▸ FAQ accordion                     │                          │
│  ▸ Related campaigns                 │                          │
└──────────────────────────────────────┴──────────────────────────┘
```

**The donation card is the page.** It sticks through the entire scroll, carries the live amount in its
button label, and shows payment-method logos inline — the donor never has to go looking for how to
give. Below `lg` it collapses into the sticky bottom bar described above, and the full card renders
inline after the media carousel.

**Anchor sub-nav** (`Products · Story · Updates`) sticks below the header and highlights the active
section on scroll. Anchors are real IDs so they are linkable and shareable.

**Section order matters and is not arbitrary** — catalogue first (concrete asks convert), then
credibility (who runs this, what they have done), then narrative, then proof of delivery, then social
proof, then objection handling. Do not reorder it to put the story first because it reads better; the
reference site's order is doing conversion work.

**Products — the needs catalogue.** Each card: image, name, `11 / 1500 Donated`, a percentage, unit
price, and a `− 1 +` stepper. Selecting quantities updates the donation card's running total and the
CTA label live. A donor can combine catalogue items with a free amount; the card shows both as
separate summary lines.

**KNOW YOUR NGO** — beneficiary name, `VERIFIED` badge, `100% FUND TRANSPARENCY` badge. Trust-stack
elements, treated as functional per `M10`.

**Campaign impact stats** — per-campaign counters from `campaign_stats`, distinct from the site-wide
homepage numbers.

**Donors** — `Recent` / `Most Generous` tabs, avatar initial, name, amount. Anonymous donors show as
"Anonymous" and their name must not appear in the HTML source either (`M08`).

**FAQ accordion** — from `campaign_faqs`, global set plus per-campaign additions. This is also the
`FAQPage` structured data (`07-SEO.md` §3).

### Responsive breakpoints

Mobile-first, designed at 360px — a Redmi, not a MacBook.

| Breakpoint | Layout |
|---|---|
| 360–639 | 1 column · hamburger · sticky donate bar |
| 640–1023 (`sm`/`md`) | 2-column campaign grid |
| 1024+ (`lg`) | 3-column grid · full nav · sidebar layouts |

Touch targets minimum 44×44px. The donate button larger still.

---

## 4. Component library

Built in Phase 0, used everywhere after. Each is a Blade component in `resources/views/components/`.

> Full specs — anatomy, variants and every interaction state — are in
> [`08-DESIGN-SYSTEM.md`](08-DESIGN-SYSTEM.md) §10. The table below is the inventory.

| Component | Notes |
|---|---|
| `<x-campaign-card>` | Image, title, beneficiary, tax badge, progress, donor count, Donate, **share buttons** |
| `<x-progress-bar>` | Raised/goal with a **text percentage** — never colour alone |
| `<x-donate-button>` | Three sizes, consistent everywhere |
| `<x-trust-badge>` | Variants: `80g`, `verified`, `secure`, `urgent` |
| `<x-stat-tile>` | Impact counters; reserves final width to avoid CLS |
| `<x-testimonial-card>` | |
| `<x-category-tile>` | Icon + label, active state |
| `<x-share-buttons>` | Facebook, WhatsApp, LinkedIn, X, copy-link |
| `<x-form.input>` `<x-form.select>` `<x-form.checkbox>` `<x-form.money>` | Label, hint, error, `aria-describedby` wired correctly |
| `<x-empty-state>` | Icon, message, optional CTA |
| `<x-skeleton>` | Card and list variants for slow connections |
| `<x-alert>` | `info` / `success` / `warning` / `danger` |

**Share buttons belong on the card, not only the detail page.** M08 originally placed share controls
on campaign detail only; the reference site puts them on every card in the grid. In India WhatsApp
sharing *is* the distribution channel for campaigns — asking a donor to open a campaign before they
can share it costs shares.

### Extraction rule

Per `05-CONVENTIONS`, Tailwind utilities inline in Blade; extract to a component only after the
fourth repeat. The list above has already passed that threshold by inspection of M08 and M10.

---

## 5. The donation form

Previously specified across three modules that pulled in opposite directions:

- `M05` — "Keep this page fast and short. Every extra field costs conversions."
- `M07` — 80G issuance is **blocked** without donor PAN and full address.
- `M07` — anonymous + 80G is impossible, and "the UI must explain this at donation time."
- `M06` — plus a "make this monthly" toggle.

Four requirements, no resolution. This is the resolution. **It supersedes the field list in
`M05 §UI`.**

### Where it renders

Two surfaces, one component:

- **`/donate`** — the standalone page, for general-fund giving.
- **A modal on the campaign page**, opened by the sticky donation card's CTA. Amount, campaign and any
  catalogue quantities are already chosen by then, so the modal opens straight into the identity
  fields with the total shown at the top. This is how the reference site does it, and it is right:
  navigating a donor away from a page they are already convinced by loses donations.

The field logic below is identical in both. Build it once as a Livewire component.

### Structure

One step, no wizard. Progressive disclosure keeps the default path short while making the
80G/anonymity conflict impossible to hit by accident.

```
┌─ Campaign context (when campaign-scoped) ────────────────┐
│  Cover thumb · title · progress bar                       │
└───────────────────────────────────────────────────────────┘

  Amount
  [ ₹500 ][ ₹1,000 ][ ₹2,500 ][ ₹5,000 ]  [ Other ₹____ ]
     └─ from donation.preset_amounts

  [ ] Make this a monthly donation
       └─ rendered only when the campaign allows_recurring,
          or always on the general /donate page
       └─ above ₹15,000 (the UPI Autopay per-transaction ceiling),
          show the e-mandate note inline — not after the donor
          has already been sent to their bank

  Name *          Email *          Phone *

  [x] I want an 80G tax-exemption receipt      ← DEFAULT ON
       └─ reveals: PAN * · Address * · City * · State * · Pincode *

  [ ] Donate anonymously
       └─ when 80G is also checked, show inline:
          "An 80G receipt must carry your name and PAN, so it can't be
           issued for an anonymous donation. Uncheck one to continue."

  Message (optional)

  [ ] I accept the Terms & Conditions      ← required, unticked by default

  [ Donate ₹1,000 ]     ← label carries the live amount

  🔒 Secure payment · 80G eligible · UPI · Cards · Net Banking
```

On the campaign modal, a summary block sits above the fields showing the catalogue lines and the free
amount separately, then the total — so a donor who selected three items can still see what they chose
at the moment they commit.

### Rules

1. **80G defaults to checked — confirmed by the client** (see §14). It is the single most concrete
   reason to donate here rather than elsewhere. Defaulting it off and chasing PAN by email afterwards —
   M07's fallback path — converts far worse than asking once, in context, with a reason attached.
   Sprint 6 instruments checked/unchecked rates and form abandonment so this is revisitable with data.
2. **PAN and address are required *only* when the 80G box is checked.** A donor who unchecks it gets
   the three-field short form M05 asks for.
3. **80G + anonymous is blocked client-side and server-side.** The inline explanation appears
   immediately on the conflicting click; the submit button disables until one is resolved.
   `StoreDonationRequest` re-validates — client-side UX is not validation.
4. **The submit button shows the amount.** `Donate ₹1,000`, updating live. Removes any ambiguity
   about what is about to be charged.
5. **Cash-over-₹2,000 ineligibility** (M05) is admin-side only and never appears on this form.
6. **The amount is a suggestion until the server validates it** (M05 §Server-side amount validation).
   Nothing here changes that.
7. **Trust marks sit adjacent to the submit button**, not in the header. They do their work at the
   moment of hesitation.
8. **Terms acceptance is required and never pre-ticked.** Pre-ticked consent is not consent.
9. **UTM parameters are captured into a hidden field** on first page load and stored on the donation
   (`donations.utm_data`). Attribution cannot be reconstructed after the fact, and without it there is
   no way to tell which campaign post actually raised money.
10. **Exit-intent guard on the modal.** Closing it with an amount entered shows one dismissible prompt
    — "You're one step away from supporting this campaign" — with *Continue* and *Leave*. Once per
    session, never on the standalone page, and never blocking. It is a reminder, not a trap; a second
    prompt is a dark pattern.

### Retroactive PAN capture stays

M07's prompt-later flow remains for donations that arrive without PAN — offline entries, donors who
unchecked the box and changed their mind. It is now the exception path rather than the primary one.

---

## 6. Payment interaction states

`M05 §Donation flow` diagrams the server path and ends at "redirect to `/donate/success`". Razorpay
Checkout.js is a **modal**, not a redirect, and three real states were unhandled. **This section
supersedes step 9 of that diagram.**

```
                    [ Donate ] clicked
                            │
                POST /donate → InitiateDonation
                            │
                 button → spinner, disabled
                            │
                  Checkout.js modal opens
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
   A. dismissed        B. paid            C. failed
   (ondismiss)              │             (payment.failed)
        │                   │                   │
   stay on page      POST /donate/callback      │
   re-enable button          │                  │
   "Payment cancelled  ┌─────┴─────┐            │
    — try again"       │           │            │
        │         succeeds      fails/timeout   │
        │              │           │            │
        │              ▼           ▼            ▼
        │      /donate/success  /donate/pending  /donate/failed
        │                          │
        │                   wire:poll 3s, 20s cap
        │                          │
        │                ┌─────────┴─────────┐
        │           webhook lands        still pending
        │                │                    │
        │           → /success        "Payment is being confirmed.
        │                              We'll email your receipt
        │                              within a few minutes."
```

### Required behaviour

| State | Behaviour |
|---|---|
| **A — modal dismissed** | Never navigate away. Re-enable the button, keep every field filled, show a neutral message. The donor did not fail; they hesitated. |
| **B — paid, callback OK** | `/donate/success`: amount, campaign, receipt-on-its-way note, share buttons, "give monthly" nudge. |
| **B — paid, callback lost** | `/donate/pending` polling donation status for 20s, then the reassurance copy above. **Never** tell a donor who has paid that something failed. |
| **C — payment failed** | `/donate/failed`: plain reason, retry button pre-filled with the same amount, support phone. Per M05, do not blame the donor. |
| **Checkout.js fails to load** | Detect script-load failure; show the support phone and a "we've saved your details" note. |
| **Stuck pending** | Nightly `AbandonStalePendingDonations` job: `pending` older than 30 minutes → `abandoned`. Without it the donations table fills with noise and the admin dashboard's pending count is meaningless. |

The `/donate/pending` page appears in M05's build checklist but was unreachable from its flow
diagram. It is now reachable, and it is the page that handles the second-most-common real-world
scenario after "donor closes the tab".

### Recurring mandates are a different flow entirely

Everything above describes the **one-time** path, where Checkout.js opens a modal. A mandate
(`M06 §Setup flow`) is a **full navigation** — the donor leaves the site for Razorpay's authentication
page, then approves in their UPI app or with their bank, and comes back. Applying the modal state
machine to it produces a page that waits forever for a callback that arrives as a redirect.

| State | Behaviour |
|---|---|
| **Redirecting out** | Interstitial before the jump: "Taking you to your bank to approve ₹1,000/month. You can cancel any time." Never a silent redirect — an unexplained bounce to a bank domain reads as a phishing attempt. |
| **Returns authorised** | `/donate/success` variant: "Your monthly donation is set up," next charge date, and a link to manage it in the portal. |
| **Returns declined** | Plain reason, retry, and an offer of one-time giving instead. |
| **Never returns** | The subscription sits in `created`. Per `M06`, one gentle reminder email at 24 h, expire at 48 h. This is the common case — bank auth pages are slow and donors abandon them. |
| **Authorised but webhook late** | The return page polls subscription status briefly, then reassures: "We're confirming with your bank — we'll email you when it's active." Same principle as `/donate/pending`: never tell someone who just authorised that it failed. |

**Validate the amount against the mandate ceiling before leaving the site.** UPI Autopay caps at
₹15,000 per transaction without additional authentication (`M06`). A donor who sets up ₹20,000/month
and only discovers the problem on the bank's page is a donor who does not come back. The monthly
toggle checks the limit inline and suggests e-mandate above it.

---

## 7. Schema amendments

Three additions, specified here and mirrored into `02-DATABASE-SCHEMA.md §10` and `M02`.

### `banners` (new)

Backs homepage section 1. M10 promised CMS-managed rotating banners; no table existed.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT PK | |
| `title` | VARCHAR(190) | |
| `subtitle` | VARCHAR(255) NULL | |
| `image_path` | VARCHAR(255) | desktop, ~1920×720 |
| `mobile_image_path` | VARCHAR(255) NULL | ~750×900; falls back to `image_path` |
| `cta_label` | VARCHAR(60) NULL | |
| `cta_url` | VARCHAR(255) NULL | |
| `campaign_id` | BIGINT FK→campaigns NULL, `onDelete('set null')` | shortcut instead of `cta_url` |
| `sort_order` | SMALLINT default 0 | |
| `is_published` | BOOLEAN default 1 INDEX | |
| `starts_at` / `ends_at` | TIMESTAMP NULL | scheduled campaigns |

A separate mobile image is not a nicety. A 1920×720 hero letterboxes to an unreadable strip at 360px,
and the hero is the page's LCP element.

### `contact_messages` (new)

`/contact` is in M10's sitemap with no table, no owner, and no admin surface — it would have shipped
as a form posting into nothing. Owned by M10.

`id`, `name`, `email`, `phone` NULL, `subject` NULL, `message` TEXT, `ip_address`, `user_agent`,
`is_read` BOOLEAN default 0 INDEX, `read_at` NULL, `created_at`.

Honeypot field, 3/hour/IP rate limit, and an email notification to `org.email` on submit. Read-only
Filament resource — replies happen in the admin's own mail client, not here.

### `posts.category` → enum

M10's build checklist requires a blog category filter. `VARCHAR(80)` free text produces
"Education" / "education" / "Educaton" and a filter that silently drops posts. Use a PHP enum cast
(`PostCategory`) — a lookup table is more than v1 needs.

### `homepage` settings group (M02)

Backs sections 6, 7 and 8.

`homepage.serve_heading` · `homepage.serve_body` (text) · `homepage.serve_image` (file) ·
`homepage.monthly_heading` · `homepage.monthly_body` · `homepage.steps` (json — four
`{title, body, icon}` objects) · `homepage.newsletter_heading`

Using the existing settings singleton rather than a generic section builder is deliberate. A
drag-and-drop page builder is a week of work for copy that changes twice a year.

---

## 8. Performance rules that constrain the design

`M10` sets Lighthouse mobile ≥ 90, LCP < 2.5s, CLS < 0.1. Three design decisions follow directly, and
they are cheaper to honour now than to retrofit.

**The hero must not be a JavaScript carousel on first paint.** Server-render slide one as a plain
`<img>` with explicit `width`/`height` and `fetchpriority="high"`; Alpine hydrates the rotation after
load. A carousel that initialises in JS makes the LCP element depend on script execution and is the
single most common cause of a failing mobile Lighthouse score.

**Impact counters must reserve their final width.** Numbers animating from 0 to "2K+" reflow the row
on every frame unless the container is width-locked. Prefer `min-width` on the tile plus
`prefers-reduced-motion` respect — or skip the count-up entirely.

**Campaign filtering is server-rendered with real URLs**, not a Livewire grid. `/causes/{category}` —
`/causes/animals` is an indexable landing page; a JS-filtered grid is not. Alpine handles only the
active-pill state. This also removes ~30KB and a round-trip per filter click from the highest-traffic
page. *(Amends `M08 §Build checklist`. Note the namespace: category pages are **not**
`/campaigns/{category}`, which would collide with `/campaigns/{slug}` — see `07-SEO.md` §1.)*

**Image derivatives are generated non-queued.** One campaign upload must yield card (4:3), hero
(16:9) and OG (exactly 1200×630). `spatie/laravel-medialibrary` queues conversions by default — and
with a 60-second cron-driven queue (`04-DEPLOYMENT`), a campaign published by an admin shows missing
images for up to a minute. If it is shared to WhatsApp in that window, the blank preview is cached
more or less permanently. Mark the OG and card conversions `nonQueued()`.

**Every image ships with explicit dimensions and a category fallback.** M08 promises category-based
placeholders — that is eight designed images, delivered in Phase 0, not improvised in Sprint 9.

---

## 9. Portal information architecture

`/portal` is referenced by M03, M04, M05, M06 and M09 and specified by none of them. It is the only
authenticated surface a non-staff user ever sees. Owned here.

```
/portal                    Dashboard
/portal/donations          History + receipt downloads          → M05
/portal/subscriptions      Active monthly giving, pause/cancel  → M06
/portal/documents          ID card, letters, certificates       → M04
/portal/notices            Inbox with read tracking             → M09
/portal/profile            Editable details, PAN                → M03
```

**Dashboard** — greeting, lifetime giving total, active-subscription card, latest receipt, unread
notice count, ID-card download if a member. Every tile links deeper; nothing terminal.

**Navigation** — sidebar at `lg+`, bottom tab bar below. Items render conditionally: a donor with no
membership never sees Documents; a member who has never donated never sees Subscriptions. An empty
section is worse than an absent one.

**Role differences** — donors see donations, subscriptions, receipts, profile. Members additionally
see documents and notices. Both use the same layout; visibility is per-item.

**Pending states** — PDF generation is queued and the queue can be 60 seconds behind. A requested
document shows a "preparing…" state with `wire:poll`, then a download button. Never a dead link and
never a spinner without an explanation of what is being waited for.

**Accessibility applies here too.** `M10`'s checklist covers only the public site, but `/portal` is
Blade + Livewire and used by real donors, many of them older. Same standard: semantic HTML, labelled
inputs, visible focus, 4.5:1 contrast, keyboard-navigable.

---

## 10. Canonical host & indexing policy

**Canonical host: `https://visiongoodworkglobalfoundation.org`** (apex, no `www`). Every other form —
`www.`, `http://`, and any trailing-slash variant — 301-redirects to it in a single hop. Pick one now
and never serve both: duplicate hosts split link equity, and more concretely, a QR code printed on an
ID card that resolves through a redirect chain is a QR code that fails on a weak connection.

This host is also what OG tags, JSON-LD `url` fields, canonical links, sitemap entries, receipt PDF
footers, and `DOCUMENT_QR_BASE_URL` must all agree on. All of them derive from `APP_URL` — never
hard-code the domain in a Blade template or a PDF partial.

The rest of the indexing rules are not covered in `M10`'s SEO checklist and are easy to get wrong once.

| Path | Directive |
|---|---|
| `/`, `/campaigns/*`, `/blog/*`, CMS pages | Index, in sitemap |
| `/donate`, `/start-fundraiser`, `/contact` | Index, in sitemap |
| `/donate/success`, `/failed`, `/pending` | `noindex`, excluded |
| `/verify/{uuid}` | `noindex, nofollow` — reachable by QR, never indexed |
| `/portal/*` | `noindex` + `robots.txt` disallow |
| `/admin/*`, `/manager/*` | `noindex` + `robots.txt` disallow |

Verification pages are deliberately low-PII (`M04`), but a search index full of certificate URLs is
still a privacy surface nobody asked for.

---

## 11. Testing the front end

`05-CONVENTIONS` mandates 100% Pest coverage on payments — all of it server-side. The riskiest
failure mode in this product is client-side: Checkout.js not loading, the modal being dismissed, a
form unusable at 360px. None of that is reachable by a feature test.

Add a browser layer (Pest v4 browser tests, or Dusk) covering, at minimum:

- [ ] Donate happy path end-to-end against `FakeGateway`
- [ ] Modal dismissed → stays on page, fields retained, button re-enabled
- [ ] 80G checked + anonymous checked → submit blocked, explanation visible
- [ ] Unchecking 80G removes PAN/address from the required set
- [ ] Donation form fully usable at 360×640
- [ ] Keyboard-only completion of the donation form
- [ ] Campaign page renders correct OG tags

These run in Sprint 6, alongside the flow they cover — not deferred to Sprint 15.

---

## 12. Accessibility checklist

Applies to the public site **and** `/portal`. Both Filament panels inherit whatever Filament provides;
do not fight it, but do not assume it either.

- [ ] One `<h1>` per page; heading levels never skip
- [ ] Every meaningful image has alt text; decorative images have `alt=""`
- [ ] 4.5:1 contrast on all text (3:1 for ≥24px)
- [ ] Visible focus rings — never `outline: none` without a replacement
- [ ] Full keyboard navigation, including the mobile drawer and the Checkout trigger
- [ ] Labels associated with inputs; errors linked via `aria-describedby` and announced
- [ ] No information by colour alone
- [ ] Touch targets ≥ 44×44px
- [ ] `prefers-reduced-motion` respected by the carousel and the counters
- [ ] Skip-to-content link
- [ ] Screen-reader pass on homepage, campaign page, donation form, portal dashboard

---

## 13. Phase 0 build checklist

- [ ] `tailwind.config.js` with the §2 token set — both ramps, the corrected donate-button role, the
      `border`/`divider` split, and `accent-600` excluded from any text context
- [ ] Self-hosted Inter (variable) + Noto Sans Devanagari subset
- [ ] Filament v5 custom theme using the same palette
- [ ] Public layout: header, sticky behaviour, mobile drawer, footer, WhatsApp float
- [ ] All components in §4, each rendered in a `/dev/components` gallery route (local only)
- [ ] Wireframes: donation form, campaign detail, portal dashboard
- [ ] 8 category placeholder images + default OG image
- [ ] `banners` and `contact_messages` added to `02-DATABASE-SCHEMA.md §10` and migration order
- [ ] `homepage` settings group added to `M02`
- [ ] `posts.category` changed to an enum cast
- [ ] `M05 §UI` superseded by §5; `M05` flow step 9 superseded by §6
- [ ] `M10` homepage-section table corrected to name real sources
- [ ] Roadmap resequenced (Phase 0 added, Sprint 11 scope reduced)

---

## 14. Decisions

### Resolved

**Brand palette — decided.** Adopted from <https://bright-minds-haven.vercel.app/>, with the two
corrections in §2: the donate button is green rather than amber (the reference's amber CTA fails WCAG
at 2.68:1), and form-input borders use the darker `border` token. Type is Inter.

**80G checkbox defaults to on — confirmed.** The donation form pre-checks "I want an 80G
tax-exemption receipt" and reveals PAN and address, per §5 rule 1. The consequences, recorded so this
is not relitigated mid-sprint:

- The default path asks for PAN, address, city, state and pincode — five fields more than the minimum.
  This is accepted deliberately. 80G is the most concrete reason to give here rather than elsewhere,
  and asking once, in context, with the reason attached, converts better than M07's alternative of
  chasing PAN by email after the money has arrived.
- A donor who does not want a receipt unchecks one box and gets the three-field short form.
- M07's retroactive PAN-capture flow stays, demoted to the exception path — offline entries, and
  donors who change their mind.
- Sprint 6 must instrument this. Log 80G-checked versus unchecked and the form-abandonment rate, so
  the decision can be revisited with data rather than opinion after ~500 donations.

### Still open

1. **Hindi content** — confirmed for v1? It changes the font budget and the PDF font embedding
   (`04-DEPLOYMENT §Hindi PDF support`).
2. **Payment-method badges** — which methods are live on the Razorpay account, so the footer shows
   only real ones.
3. **Logo files** — the reference mark is a house glyph in a green circle; the NGO's own logo is needed
   in SVG (light and dark) plus a favicon before the layout is final.

---

Previous: [`05-CONVENTIONS.md`](05-CONVENTIONS.md) · Next: [`modules/`](modules/)
