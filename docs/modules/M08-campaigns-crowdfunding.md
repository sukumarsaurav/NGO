# M08 — Campaigns & Crowdfunding

**Phase 5, Sprints 9–10** · Depends on: M05

## Purpose

The crowdfunding engine — the core of the public-facing experience and the closest match to the
reference site. Campaigns organised by cause, each with a story, a goal, a live progress bar, a donor
wall, and impact updates.

## Data

`campaign_categories`, `campaigns`, `campaign_products`, `campaign_stats`, `campaign_faqs`,
`campaign_updates`, `fundraiser_requests`, `donation_items` — see `02-DATABASE-SCHEMA.md` §8.

### Categories

Seeded to match the reference site: Animals, Children, Elderly, Education, Faith, Women, Disaster
Relief, Specially Abled. Each has an icon and appears in the homepage browse grid.

### Campaign lifecycle

```
draft ──► pending_review ──► active ──┬──► completed   (goal reached or end date passed)
                                      ├──► paused      (temporarily stopped)
                                      └──► closed      (stopped permanently)
```

Only `active` campaigns accept donations. `completed` campaigns stay publicly visible — a funded
campaign with its updates is the best possible trust signal for the next donor.

## Denormalised totals

`campaigns.raised_amount` and `donor_count` are denormalised. The alternative — summing donations on
every campaign card render — makes the homepage do nine aggregate queries and doesn't survive contact
with real traffic.

**Kept in sync three ways:**

1. `UpdateCampaignTotals` listener on `DonationSucceeded` — immediate increment.
2. Decrement on refund.
3. `RecalculateAllCampaignTotals` nightly job — full recomputation from source, correcting any drift.

The nightly reconciler is not optional. Denormalised counters drift; a listener that fails silently
during a deploy leaves a campaign under-reporting forever otherwise. It also catches manual DB edits,
which will happen.

`offline_raised_amount` is a separate manual field for cheques and bank transfers recorded outside
the donation flow. Displayed total = `raised_amount + offline_raised_amount`.

## Campaign page

Two columns at `lg+`: content scrolls on the left, a **sticky donation card** holds the right. Full
layout, section order and the ASCII wireframe: [`06-UI-UX-FOUNDATION.md`](../06-UI-UX-FOUNDATION.md) §3.

- Media carousel — video and images, arrows and dots
- Title, subtitle, "by {beneficiary_name}"
- Sticky anchor sub-nav: `Products · Story · Updates`
- **Sticky donation card** — Tax Benefit and Updates pills, raised/goal/donor count, progress bar,
  editable amount with preset chips, payment-method logos, a CTA carrying the live total, share icons
- **Products** — the needs catalogue, below
- **Know your NGO** — beneficiary, `VERIFIED` and `100% FUND TRANSPARENCY` badges
- **Campaign impact stats** — per-campaign counters from `campaign_stats`
- Story (rich text, images, video)
- Updates timeline, newest first
- Donor wall — `Recent` / `Most Generous` tabs, "Anonymous" where chosen
- **FAQ accordion** — global set from `campaign_faqs` plus per-campaign additions
- Related campaigns from the same category
- Sticky "Donate Now" bar below `md`, appearing once the hero scrolls out — the highest-converting
  element on mobile

Section order is deliberate: catalogue → credibility → narrative → proof of delivery → social proof →
objection handling. Do not reorder it to lead with the story.

## Products — the needs catalogue

The single biggest departure from a conventional crowdfunding page, and the reason the reference
site's campaign pages convert. Instead of "donate ₹900", the donor is offered **a medicine kit**, and
told that 11 of the 1,500 needed have been funded.

Each `campaign_products` row renders as a card: image, name, `11 / 1500 Donated`, a percentage bar,
`PRICE ₹900`, and a `− 1 +` stepper. Adjusting a stepper updates the sticky card's running total and
CTA label live. Catalogue items and a free amount can be combined; the card shows them as separate
summary lines.

### The money rules

**Quantities come from the client. Money never does.** `InitiateDonation` receives
`[{product_id, quantity}, …]` plus an optional free amount, then:

1. Re-reads `unit_price` from `campaign_products` for every line
2. Computes `line_total` and `items_amount` server-side
3. Validates the free amount against `donation.min_amount`
4. Sets `donations.amount = items_amount + free_amount`, and sends *that* to the gateway

This is `M05 §Server-side amount validation` extended to line items. A tampered price field produces a
rejected request, not a ₹1 medicine kit.

**`unit_price` is snapshotted onto `donation_items`.** Prices change between campaigns and seasons; a
receipt reprinted a year later must show what the donor actually paid.

**`units_funded` is denormalised** and maintained exactly like `raised_amount`: incremented by the
`DonationSucceeded` listener, decremented on refund, and fully recomputed by the nightly reconciler.

### Deliberately not built

- **No stock reservation, no cart expiry.** This is not e-commerce. Two donors funding the last
  medicine kit simultaneously both succeed — the NGO buys two kits, which is a good outcome. Blocking
  a donation to protect an inventory count would be absurd.
- **Over-funding is allowed.** A product at 1,600 of 1,500 shows "107%", consistent with the
  goal-exceeded rule below.
- **No refund-to-item flow.** A refund decrements the totals; it does not attempt to un-buy a kit.

**Cover images** need three derivatives from one upload: card (4:3), hero (16:9) and OG (exactly
1200×630). Generate the card and OG conversions **non-queued** — medialibrary queues conversions by
default, and with the 60-second cron-driven queue on Hostinger a freshly published campaign shows
missing images for up to a minute. If it is shared to WhatsApp inside that window, the blank preview
is cached more or less permanently.

**SEO matters more here than anywhere else.** Campaign pages are what get shared and what rank.
Every campaign needs: a clean slug, `meta_title`, `meta_description`, an OG image at 1200×630, and
JSON-LD structured data. A campaign that renders beautifully but produces a blank WhatsApp preview
will underperform badly — WhatsApp is how most Indian donation campaigns actually spread.

Two rules that are easy to get wrong and expensive to reverse, both specified in
[`07-SEO.md`](../07-SEO.md): **a published slug is frozen** (it has been shared and printed; changing it
404s every existing link, so a change creates a 301 via the `redirects` table), and **category pages
live at `/causes/{slug}`, never under `/campaigns/`**.

## Campaign updates

The "live impact updates" the reference site promises. Title, body, image, published date.

With `notify_donors = true`, publishing emails every donor to that campaign — the delivery on the
promise made at donation time, and the strongest driver of repeat giving. Send once per donor even if
they gave multiple times.

## Fundraiser requests

The "Start a Fundraise" form. Public submission → admin review → approval creates a draft campaign.

```
new ──► under_review ──┬──► approved  → creates draft campaign, notifies requester
                       └──► rejected  → courteous email with the reason
```

The form collects: name, email, phone, organisation, cause category, title, description, goal amount,
and supporting documents (registration certificate, 80G, photos).

Rate limit submissions (3/hour/IP) — public forms attract spam.

## UI

**Public**
- `/campaigns` — grid with sort (newest, most funded, ending soon, urgent) and pagination
- `/causes/{category}` — category landing page: unique intro copy, own meta, filtered grid beneath
- `/campaigns/{slug}` — the detail page above
- `/monthly-giving` — campaigns with `allows_recurring`
- `/start-fundraiser` — the request form
- Homepage — featured carousel, category grid, recent campaigns

**Admin**
- `CampaignResource` — rich-text story editor, cover image, gallery, goal, category, flags (featured,
  urgent, tax benefit, allows recurring), SEO fields, schedule. Table shows a progress column with a
  visual bar. Filters: status, category, featured, funded percentage.
- `CampaignUpdateResource` — nested under campaigns, with the notify-donors toggle
- `FundraiserRequestResource` — review queue with approve/reject actions and notes
- Dashboard widget: top campaigns by amount raised

## Build checklist

- [ ] `campaign_categories`, `campaigns`, `campaign_updates`, `fundraiser_requests` migrations + models
- [ ] `campaign_products`, `campaign_stats`, `campaign_faqs`, `donation_items` migrations + models
- [ ] Needs-catalogue UI: product cards with per-item progress and quantity steppers, live total
      feeding the sticky donation card
- [ ] Server-side line-total recomputation in `InitiateDonation` — quantities from the client, prices
      from the database, never the reverse
- [ ] `unit_price` snapshotted onto `donation_items`
- [ ] `units_funded` incremented on success, decremented on refund, recomputed nightly
- [ ] `CampaignProductResource` nested under campaigns; `campaign_stats` and `campaign_faqs` as
      repeaters on `CampaignResource`
- [ ] Global FAQ seeder (the six organisation-level questions), rendered on every campaign
- [ ] Donor wall with `Recent` / `Most Generous` tabs
- [ ] Sticky donation card + anchor sub-nav with scroll-spy
- [ ] Category seeder with the 8 causes and icons
- [ ] Actions: `CreateCampaign`, `PublishCampaign`, `PostCampaignUpdate`, `RecalculateCampaignTotals`,
      `ApproveFundraiserRequest`
- [ ] `UpdateCampaignTotals` listener on `DonationSucceeded`
- [ ] Refund decrements totals
- [ ] `RecalculateAllCampaignTotals` nightly job
- [ ] Public campaign listing at `/campaigns`, category pages at **`/causes/{category}`** —
      server-rendered with real, indexable URLs; Alpine handles only the active-pill state. Not a
      Livewire-filtered grid: `/causes/animals` is a landing page that can rank, a JS-filtered grid is
      not, and this is the highest-traffic page on the site. **Category pages must not live under
      `/campaigns/{...}`** — that collides with `/campaigns/{slug}` and silently makes any campaign
      whose slug matches a category name unreachable. See `07-SEO.md` §1.
- [ ] `campaign_categories.intro_body` — 150+ words of unique copy per category, plus per-category meta.
      The eight category pages are the most durable SEO assets in the project: unlike campaigns, they
      never close.
- [ ] `redirects` table + middleware, and a slug lock on published campaigns (`07-SEO.md` §7)
- [ ] Share buttons on every campaign **card**, not only the detail page — WhatsApp sharing is how
      campaigns actually spread in India, and requiring a click-through first costs shares
- [ ] Campaign detail page with all sections
- [ ] Donor wall respecting anonymity
- [ ] Updates timeline + notify-donors flow
- [ ] Social share with correct OG meta per campaign
- [ ] JSON-LD structured data
- [ ] `/monthly-giving` page
- [ ] Fundraiser request form + review workflow + rate limiting
- [ ] `CampaignResource` with progress column
- [ ] SEO per `07-SEO.md` §4: title/meta patterns with fallbacks, alt text required on covers,
      descriptive image filenames, internal links up to category and across to related campaigns
- [ ] Lighthouse SEO ≥ 90 on a campaign page

## Edge cases

- **Goal exceeded** → keep accepting donations; show "120% funded". Do not close automatically; many
  campaigns legitimately overshoot and the extra money is useful.
- **Two donors fund the last unit of a product at once** → both succeed. No locking, no reservation;
  the NGO buys two kits. Protecting an inventory count by refusing a donation would be absurd.
- **Product deactivated while it sits in someone's selection** → the quantity is dropped at submit
  with a clear message naming the item, and the remaining lines proceed. Never silently recalculate a
  total the donor has already seen.
- **Product price changed between page load and submit** → the server price wins, and if the total
  moved, the donor is shown the new total and must confirm before the gateway opens.
- **All products funded** → the catalogue still renders at 100% as proof of delivery; the free-amount
  path stays open.
- **Campaign with no products** → the Products tab and anchor link do not render at all. The page must
  read as complete, not as one with an empty section.
- **End date passes with goal unmet** → status `completed`, stop accepting, keep the page live with a
  closing update.
- **Donation to a paused campaign** → block at the form with a clear message. If a payment is already
  in flight, accept it and flag for review.
- **Campaign deleted with donations attached** → soft delete only; never hard delete. Donations must
  remain traceable to their campaign forever.
- **Slug collision** → append an incrementing suffix.
- **Denormalised total drift** → nightly reconciler, plus an admin report flagging any campaign whose
  stored total differs from the computed sum.
- **Anonymous donor on the donor wall** → show "Anonymous", never the name, and never expose the name
  in the HTML source or the JSON API either.
- **Very long story content** → truncate with "read more" on mobile; lazy-load embedded images.
- **Campaign with no cover image** → category-based fallback image, never a broken image.
- **Spam fundraiser requests** → rate limit, honeypot field, admin bulk-reject.
- **Campaign ends while recurring subscriptions to it are active** → see M06; redirect new charges to
  the general fund and tell the donor.
