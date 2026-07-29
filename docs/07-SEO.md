# 07 — SEO: Technical & On-Page

**Cross-cutting** · Touches: M08, M10, M04, and Phase 0

## Why this is its own document

Organic search is how a crowdfunding NGO acquires donors it did not already know. Paid acquisition
does not pay back on a ₹500 average donation, and social reach decays within hours of a post. A
campaign page that ranks keeps working for years.

`M10` already carries the Lighthouse targets and a sitemap checklist. That is the floor, not the plan.
This document owns URL structure, structured data, crawl control, the on-page rules per template, and
the admin UX that makes editors produce good metadata without understanding SEO.

**Canonical host and the indexing policy live in [`06-UI-UX-FOUNDATION.md`](06-UI-UX-FOUNDATION.md)
§10.** Everything here assumes them.

---

## 1. URL structure

Stable, readable, lowercase, hyphenated, no IDs, no dates, no file extensions.

| Route | Purpose |
|---|---|
| `/` | Homepage |
| `/campaigns` | All campaigns |
| `/causes/{category}` | Category landing page — e.g. `/causes/animals` |
| `/campaigns/{slug}` | Campaign detail |
| `/monthly-giving` | Recurring-enabled campaigns |
| `/start-fundraiser` | Fundraiser request form |
| `/donate` | General donation |
| `/blog`, `/blog/{slug}` | Blog |
| `/about`, `/contact`, `/privacy-policy`, `/terms-conditions` | CMS pages |
| `/verify/{uuid}` | Document verification (`noindex`) |

### Category pages live under `/causes/`, not `/campaigns/`

An earlier draft of `M08` specified `/campaigns/{category?}` for category browsing. That **collides
with `/campaigns/{slug}`** — Laravel cannot tell `/campaigns/animals` (a category) from
`/campaigns/animals-shelter-flood-relief` (a campaign) without a database lookup on every request and
a guess about precedence. The first campaign whose slug matches a category name silently becomes
unreachable.

Separate namespaces instead: `/causes/{category}` and `/campaigns/{slug}`. Both are clean, both are
indexable, and neither can shadow the other.

**These eight category pages are the most valuable SEO assets in the project** — they are what ranks
for "donate for stray animals india" and similar head terms, and unlike individual campaigns they
never close. Each needs its own H1, 150+ words of unique intro copy (`campaign_categories.intro_body`),
its own meta title and description, and the live campaign grid beneath.

### Slugs are immutable after publish

A published campaign's slug is frozen. It has been shared to WhatsApp, printed on a poster, and
indexed. Changing it is a 404 for everyone holding the old link.

If a slug genuinely must change, the old one 301s to the new one — which requires storage, hence the
`redirects` table in §7. Editing a slug in Filament on a published campaign shows a confirmation
explaining the redirect that will be created.

### Query parameters never create indexable pages

`/campaigns?sort=most-funded&page=3` is the same content in a different order. Every sort and filter
variant self-canonicalises to the clean base URL, and `?sort=` is excluded in Search Console. Only
`?page=` is left crawlable, with a self-referencing canonical per page — paginated pages should be
indexable so deep campaigns get discovered, but they must not compete with page 1.

---

## 2. Sitemaps

A sitemap **index** at `/sitemap.xml` pointing at children, not one monolith:

```
/sitemap.xml            → index
/sitemap-pages.xml      → homepage, CMS pages, /donate, /start-fundraiser, /contact
/sitemap-causes.xml     → the 8 category pages
/sitemap-campaigns.xml  → active + completed campaigns
/sitemap-blog.xml       → published posts
```

- `lastmod` is real — `updated_at`, not the generation timestamp. A sitemap that claims everything
  changed today is a sitemap Google stops trusting.
- Regenerated nightly **and** on publish, per `M10`.
- **Completed campaigns stay in the sitemap.** `M08` keeps them publicly visible as trust signals;
  they also hold their rankings and backlinks. Only `draft`, `pending_review` and `closed` are excluded.
- Never list a `noindex` URL. A URL that is both in the sitemap and `noindex` is a contradiction Search
  Console reports as an error.

`robots.txt` disallows `/portal/`, `/admin/`, `/manager/`, `/verify/`, and `/donate/success|failed|pending`,
and points at the sitemap index.

---

## 3. Structured data (JSON-LD)

One `<script type="application/ld+json">` per entity, rendered from a Blade component so the shape is
consistent and testable.

| Page | Schema |
|---|---|
| All pages | `Organization` (subtype `NGO`) — name, legal name, logo, address, contact, `sameAs` socials, and the registration numbers as `identifier` |
| Homepage | `WebSite` + `SearchAction` |
| Campaign detail | `DonateAction` + `ImageObject` + `FAQPage`, plus `BreadcrumbList` |
| Category page | `CollectionPage` + `BreadcrumbList` |
| Blog post | `Article` — headline, image, `datePublished`, `dateModified`, `author` as `Person` |
| About | `AboutPage` |
| Contact | `ContactPage` |
| FAQ blocks | `FAQPage` where real Q&A copy exists |

**The campaign FAQ accordion is real `FAQPage` markup** — the questions come from `campaign_faqs` and
are genuine visible Q&A, which is exactly the condition Google requires. Mark up only what renders.

**Do not mark up catalogue items as `Product` / `Offer`.** They look like e-commerce and are not:
nothing is sold, shipped, or owned by the donor. `Product` markup on a donation invites merchant
listings, price-comparison surfaces, and a structured-data manual action. The campaign stays a
`DonateAction`; the catalogue is presentation.

**Do not mark up the progress bar as `MonetaryAmount` on a `DonateAction`** unless the figures are
live-accurate at render time. They are cached for 5 minutes (`M10`), and structured data that
contradicts the visible page is a manual-action risk. Describe the campaign, not the running total.

**`Organization` carries the trust stack into search results.** The 12A, 80G and society registration
numbers, the address, and the phone number are exactly the entity signals that let Google associate
the site with a real, verifiable organisation. This matters more than usual here: donation sites are
YMYL-adjacent, and thin, anonymous ones are actively suppressed.

Validate every type in the Rich Results Test before Sprint 11 closes. Untested JSON-LD is decoration.

---

## 4. On-page rules per template

Titles ≤ 60 characters, descriptions 140–160. Both are editable per record and both **fall back to a
generated pattern** rather than rendering empty — an editor who skips the SEO tab must still ship a
valid page.

| Template | Title pattern | H1 | Description fallback |
|---|---|---|---|
| Homepage | `{org.name} — Donate to Verified NGO Campaigns in India` | Org tagline | `seo.meta_description` |
| Category | `Donate for {Category} — Verified Campaigns \| {org.name}` | `Donate for {Category}` | First 155 chars of `intro_body` |
| Campaign | `{title} — Donate Now \| {org.name}` | `{title}` | First 155 chars of story, tags stripped |
| Blog post | `{title} \| {org.name} Blog` | `{title}` | `excerpt` |
| CMS page | `{title} \| {org.name}` | `{title}` | First 155 chars of body |
| `/donate` | `Donate to {org.name} — 80G Tax Benefit` | `Make a Donation` | Static |

### Rules

- **One `<h1>` per page**, never on the logo, never skipping levels down to `<h4>`.
- **Campaign stories need substance.** A campaign published with under ~300 words of unique story is
  thin content that will not rank and does not convert either. `CampaignResource` warns below that
  threshold — a warning, not a block, because urgent disaster campaigns legitimately go live fast.
- **Every image carries meaningful alt text.** Filament requires it on campaign covers rather than
  defaulting to the filename.
- **Descriptive image filenames** — `stray-dog-shelter-delhi.webp`, not `IMG_4821.webp`. Medialibrary
  renames on upload from the campaign slug.
- **Internal linking is deliberate, not incidental:** every campaign links up to its category page,
  sideways to related campaigns in the same category, and a blog post about a cause links to that
  cause's category page. Category pages are the hubs; they must be reachable from the homepage in one
  click (the browse-by-cause grid already does this).
- **Blog authorship** — `Article` schema needs a real `author`. `users` gains a `bio` and the blog
  renders a byline. An unattributed blog on a donation site is a weak E-E-A-T signal.

---

## 5. Admin SEO UX

Editors will not read this document. The interface has to carry the rules.

Every SEO-bearing resource (`CampaignResource`, `PostResource`, `PageResource`,
`CampaignCategoryResource`) gets an **SEO tab** containing:

- `meta_title` with a live character counter, amber past 60
- `meta_description` with a counter, amber outside 140–160
- **A Google SERP preview** rendering title, URL and description as they will appear
- **A WhatsApp/OG preview** rendering the card as it will appear when shared — this is the single most
  useful widget on the tab, because WhatsApp is how these campaigns actually spread and a broken
  preview is invisible to the person who caused it
- Slug field, locked with an explanation once published
- Alt-text field on the cover image, required
- A plain-language checklist: story length, alt text present, meta filled, OG image generated

Placeholders show the generated fallback so an editor can see what will ship if they type nothing.

---

## 6. Technical SEO checklist

**Crawl & index**
- [ ] Canonical host enforced, single-hop 301 from `www` and `http` (`06-UI-UX-FOUNDATION.md` §10)
- [ ] Self-referencing canonical on every indexable page
- [ ] `noindex` applied per `06-UI-UX-FOUNDATION.md` §10; no `noindex` URL in any sitemap
- [ ] Sitemap index + four children, real `lastmod`, regenerated nightly and on publish
- [ ] `robots.txt` correct and pointing at the sitemap index
- [ ] Sort/filter params self-canonicalise; `?page=` crawlable with per-page canonicals
- [ ] Empty category page renders a real empty state, not a bare grid — an empty page with a live
      campaign count of zero is a soft 404
- [ ] Deleted or closed campaigns keep returning 200 with a closing update; genuinely removed URLs 410
- [ ] 404 page is useful — search, category links, donate CTA

**Performance** (Core Web Vitals; targets in `M10`, plus the one it omits)
- [ ] LCP < 2.5 s on throttled 4G — hero server-rendered, `fetchpriority="high"`
- [ ] CLS < 0.1 — explicit image dimensions, width-locked counters, fixed header height
- [ ] **INP < 200 ms** — not in `M10`; it replaced FID as a Core Web Vital and the category filter and
      donate button are the two interactions that will be measured
- [ ] No render-blocking third-party JS; analytics deferred
- [ ] WebP with fallback, lazy-loading below the fold
- [ ] HTML response cached 5 min for anonymous visitors on homepage and category pages

**Mobile & markup**
- [ ] Mobile-first indexing parity — no content hidden from mobile that exists on desktop
- [ ] Semantic landmarks, `BreadcrumbList` matching visible breadcrumbs
- [ ] Per-page OG and Twitter tags; OG image 1200×630 generated non-queued (`M08`)
- [ ] WhatsApp preview verified on a real device for a campaign published minutes earlier

**Verification & monitoring**
- [ ] Google Search Console + Bing Webmaster verified, sitemap submitted
- [ ] GA4 with a `donation_completed` conversion event
- [ ] Post-launch: Search Console checked weekly for coverage and CWV regressions

**If Hindi ships** (`00-PROJECT-OVERVIEW` allows manual EN+HI CMS entries)
- [ ] Reciprocal `hreflang` between the EN and HI versions of each page, plus `x-default`
- [ ] `<html lang>` correct per page
- [ ] Never machine-translate and index the result

---

## 7. Schema additions

### `redirects` (new)

Required by the immutable-slug rule. Without it, a slug edit is a permanent 404 on every shared link.

`id`, `from_path` VARCHAR(255) UNIQUE, `to_path` VARCHAR(255), `status_code` SMALLINT default 301,
`hits` INT UNSIGNED default 0, `last_hit_at` TIMESTAMP NULL, `created_at`.

Resolved in middleware **after** the router fails, before the 404 renders — so it costs one query only
on genuine misses. Rows are created automatically when a published slug changes, and manually by an
admin for links printed in old material. `hits` shows which old URLs still get traffic.

Guard against loops and chains: on insert, if `to_path` already exists as a `from_path`, collapse to
the final destination.

### `campaign_categories.intro_body`

`TEXT NULL` — the unique 150+ word intro that makes a category page rank rather than read as a bare
listing. Plus `meta_title` and `meta_description` VARCHAR NULL.

### `users.bio`

`TEXT NULL` — blog author bio, feeding the byline and `Article`'s `author`.

---

## 8. Where this lands in the roadmap

SEO is not a sprint. It is a set of constraints applied while each surface is built, plus one
verification pass.

| Phase / Sprint | SEO work |
|---|---|
| **Phase 0** | URL map agreed (§1); `<x-seo-meta>` and `<x-json-ld>` components; SERP + OG preview components |
| **Sprint 6** (donations) | `/donate` metadata; success/failed/pending set `noindex` from day one |
| **Sprint 9–10** (campaigns) | `/causes/{category}` and `/campaigns/{slug}` routes; category `intro_body`; campaign SEO tab with previews; `redirects` table + middleware; slug lock; `DonateAction` + `BreadcrumbList` |
| **Sprint 11** (public site) | Sitemap index, `robots.txt`, canonicals, all remaining JSON-LD, blog authorship, image filenames and alt enforcement, Search Console + Bing |
| **Sprint 15** (hardening) | Full technical audit against §6, Rich Results Test on every type, CWV including INP on a real mid-range Android, crawl with a spider to catch orphans and broken internal links |

Doing the URL decisions in Phase 0 and the redirect infrastructure in Sprint 9 is the part that
matters. Everything else in this document can be retrofitted; URLs and redirects cannot be, because by
the time you would want to change them people are already linking to them.
