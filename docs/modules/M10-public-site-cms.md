# M10 — Public Website & CMS

**Phase 6, Sprint 11** · Depends on: M08

## Purpose

The public website — everything a visitor sees before they decide to trust this organisation with
their money. Plus a CMS so the NGO can change any of it without a developer.

The reference site's structure is the model, because its structure is doing a specific job:
establishing credibility fast, then removing every obstacle between "I care" and "I've donated."

## Data

`pages`, `posts`, `testimonials`, `press_mentions`, `impact_stats`, `banners`, `contact_messages` —
see `02-DATABASE-SCHEMA.md` §10. Plus the `homepage` settings group in `M02`.

Layout, components, tokens and the responsive contract live in
[`06-UI-UX-FOUNDATION.md`](../06-UI-UX-FOUNDATION.md) and are delivered in Phase 0, before Sprint 1.
This sprint consumes them; it does not create them.

## Site map

Authoritative URL map, structured data and on-page rules: [`07-SEO.md`](../07-SEO.md) §1–4.

```
/                          Homepage
/campaigns                 Browse all (sort, pagination)
/causes/{category}         Category landing page                → M08
/campaigns/{slug}          Campaign detail                      → M08
/monthly-giving            Recurring-enabled campaigns          → M06
/start-fundraiser          Fundraiser request form              → M08
/donate                    General donation                     → M05
/donate/success|failed     Outcome pages
/blog                      Blog index
/blog/{slug}               Post
/about                     CMS page
/privacy-policy            CMS page
/terms-conditions          CMS page
/contact                   Contact form
/verify/{uuid}             Document/receipt verification        → M04
/portal/*                  Member & donor portal
/login /register           Auth                                 → M01
/sitemap.xml /robots.txt
```

## Homepage sections

Twelve sections, each editable by an admin without a developer. Every one names its storage — an
earlier version of this doc claimed all twelve were CMS-managed while five had no table behind them.

| # | Section | Source |
|---|---|---|
| 1 | **Hero** — rotating banners, optionally linked to a campaign | `banners` |
| 2 | **Featured campaigns** — carousel | `campaigns.is_featured` |
| 3 | **Impact stats** — the counters | `impact_stats` |
| 4 | **Browse by cause** — the 8 category tiles | `campaign_categories` |
| 5 | **Recent campaigns** — grid with a "view more" link | `campaigns` |
| 6 | **Who we serve** — mission text with image | `homepage.serve_*` settings |
| 7 | **Monthly donation promo** — recurring-giving CTA | `homepage.monthly_*` settings |
| 8 | **How to donate** — the 4-step explainer | `homepage.steps` (json) |
| 9 | **Featured in** — press logos | `press_mentions` |
| 10 | **Testimonials** — donor quotes | `testimonials` |
| 11 | **Newsletter** — email capture | `subscribers` |
| 12 | **Footer** — links, contact, social, payment logos, registration numbers | `settings` |

Sections 6–8 use the settings singleton rather than a generic page builder. Drag-and-drop section
management is a week of work for copy that changes twice a year.

## Header & footer

The primary navigation is **hard-coded in the Blade layout, not CMS-managed** — five links that
change once a year do not justify a menu manager. Recorded as a decision so nobody builds one.

Structure, sticky behaviour, the mobile drawer, and the footer's four regions are specified in
`06-UI-UX-FOUNDATION.md` §3. The registration numbers in the footer (12A, 80G, society) are a
trust-stack element, not boilerplate — a donor verifying an NGO looks for exactly those.

## The trust stack

Donation conversion is mostly a trust problem, not a UX problem. These elements are doing that work
and should be treated as functional requirements rather than decoration:

- **80G / Tax Benefit badge** — the most concrete reason to donate here rather than elsewhere
- **Verified campaign badge** — "every campaign is handpicked and verified"
- **Press mentions** — third-party validation
- **Testimonials** naming real people and cities
- **Impact updates** on campaigns — proof that past money did something
- **Transparent progress bars** — raised vs goal, visible donor count
- **Payment-method logos** and a visible security indicator
- **Registration numbers** in the footer — 12A, 80G, society registration

## CMS

**Pages** — slug, title, rich body, meta title/description, published flag. Used for About, Privacy
Policy, Terms, and any ad-hoc page. Reserved slugs (those matching real routes) are blocked at
validation.

**Posts (blog)** — slug, title, excerpt, body, cover image, category, tags, author, published date,
view count, SEO fields. Blog matters for organic search — informational content ranks where campaign
pages can't. `category` is cast to a `PostCategory` enum: free text produces "Education" /
"education" / "Educaton" and a filter that silently drops posts.

**Contact messages** — name, email, phone, subject, message, plus IP and user agent for spam triage.
Honeypot field and a 3/hour/IP rate limit. Read-only in Filament with a read/unread flag; replies
happen in the admin's own mail client, and building an inbox here is scope nobody asked for.

**Banners** — the homepage hero. Desktop and mobile images, optional CTA or linked campaign, sort
order, and a publish window for scheduled campaigns.

**Testimonials** — name, location, avatar, quote, rating, sort order.

**Press mentions** — outlet name, logo, URL, date.

**Impact stats** — label, value, suffix (`+`, `K+`), icon, sort order. Manually maintained; auto-
computing "lives impacted" from the database would be dishonest.

## Performance & SEO

Targets, verified in Sprint 11 and again in Sprint 15:

| Metric | Target |
|---|---|
| Lighthouse Performance (mobile) | ≥ 90 |
| Lighthouse SEO | ≥ 95 |
| Lighthouse Accessibility | ≥ 90 |
| Largest Contentful Paint | < 2.5 s on throttled 4G |
| Cumulative Layout Shift | < 0.1 |

**How:**
- WebP with fallback, explicit `width`/`height` on every image (kills CLS)
- Lazy-load everything below the fold
- Cache campaign listings and homepage sections (5 min TTL, busted on publish)
- Defer non-critical JS; inline critical CSS
- No jQuery, no heavy carousel library — Alpine plus CSS scroll-snap
- `spatie/laravel-sitemap` regenerating nightly and on publish
- Canonical URLs, per-page OG tags, JSON-LD (`Organization`, `NGO`, `Article`, `DonateAction`)
- Hero slide one server-rendered as a plain `<img>` with `fetchpriority="high"` — a carousel that
  initialises in JS makes the LCP element depend on script execution and is the most common cause of
  a failing mobile score
- Category browsing uses real URLs (`/causes/{category}`), not a JS-filtered grid — see M08
- **INP < 200 ms** — omitted from the table above; it replaced FID as a Core Web Vital, and the
  category filter and donate button are the two interactions it measures

Full technical and on-page SEO specification, including the sitemap index, JSON-LD types, per-template
title/meta patterns, and the admin SEO tab: [`07-SEO.md`](../07-SEO.md).

### Indexing policy

| Path | Directive |
|---|---|
| `/`, `/campaigns/*`, `/blog/*`, CMS pages, `/donate`, `/start-fundraiser`, `/contact` | Index, in sitemap |
| `/donate/success`, `/donate/failed`, `/donate/pending` | `noindex`, excluded from sitemap |
| `/verify/{uuid}` | `noindex, nofollow` — reachable by QR, never indexed |
| `/portal/*`, `/admin/*`, `/manager/*` | `noindex` + `robots.txt` disallow |

**Most Indian donors arrive on a mid-range Android phone over 4G.** Test on a throttled connection
with CPU throttling, not on a desktop with fibre. A homepage that's fast on your laptop and takes
eight seconds on a Redmi is a homepage that loses donations.

## Responsive

Mobile-first, designed at 360 px. Sticky "Donate Now" on campaign pages, hamburger navigation, and a
floating WhatsApp button (the reference site has one, and it converts well in India).

Touch targets minimum 44×44 px. The donate button in particular should be impossible to mis-tap.

## Accessibility

Not optional, and not only about compliance — older donors are a significant giving demographic and
many of them use larger text or a screen reader. **This applies to `/portal` as well**, which is
Blade + Livewire and used by exactly that demographic; the full checklist is in
`06-UI-UX-FOUNDATION.md` §12.

- Semantic HTML, one `<h1>` per page, logical heading order
- Alt text on every meaningful image
- 4.5:1 contrast minimum
- Visible focus rings; full keyboard navigation
- Form labels properly associated; errors announced
- No information conveyed by colour alone (the progress bar needs a text percentage too)

## Build checklist

- [ ] `pages`, `posts`, `testimonials`, `press_mentions`, `impact_stats`, `banners`,
      `contact_messages` migrations + resources
- [ ] `homepage` settings group (M02) + `HomepageContentSeeder`
- [ ] Homepage with all 12 sections, each wired to the source named above
- [ ] Hero: server-rendered first slide, Alpine rotation after load, separate mobile image
- [ ] Blog index, enum-backed category filter, post page
- [ ] Static pages rendered from the CMS
- [ ] Contact form with honeypot, 3/hour/IP rate limit, admin notification email
- [ ] WebP conversion + lazy loading + explicit dimensions; OG/card conversions `nonQueued()`
- [ ] Sitemap **index** + four child sitemaps with real `lastmod`, `robots.txt`, canonicals, indexing
      policy applied (`07-SEO.md` §2)
- [ ] All JSON-LD types per `07-SEO.md` §3, each validated in the Rich Results Test
- [ ] Per-template title/meta patterns with generated fallbacks (`07-SEO.md` §4)
- [ ] Admin SEO tab with character counters, SERP preview and WhatsApp/OG preview (`07-SEO.md` §5)
- [ ] Blog byline + `users.bio` for `Article` authorship
- [ ] Per-page OG tags; WhatsApp preview verified on a real device
- [ ] Lighthouse targets met on homepage and a campaign page
- [ ] Usable at 360 px; tested on a real mid-range Android
- [ ] Keyboard navigation and screen-reader pass
- [ ] Google Analytics + Search Console

## Edge cases

- **No published banners** → render a static fallback hero from `homepage.serve_*` copy plus the
  default OG image. The homepage must never open with a blank region above the fold.
- **Banner with no mobile image** → fall back to `image_path` with `object-fit: cover`, accepting the
  crop rather than the letterbox.
- **No featured campaigns** → fall back to the most recent active campaigns, never an empty carousel.
- **No campaigns at all** (fresh install) → friendly empty state, not a broken grid.
- **Missing images** → category-based placeholders throughout.
- **CMS page slug colliding with a route** → blocked at validation with an explanation.
- **Very long campaign titles** → truncate in cards with an ellipsis and a `title` attribute.
- **Blog post with no cover** → default OG image from settings.
- **Analytics not configured** → the tracking snippet simply doesn't render; no console errors.
- **Slow network** → skeleton loaders on campaign grids rather than a blank screen.
- **JS disabled** → core content and the donation form remain reachable. The gateway needs JS, but
  the visitor should at least see the campaign and a phone number.
