# Client Feedback — Remediation Plan

**Date:** 2026-08-08 · **Source:** client review of the live site
**Scope:** the four correction items raised, plus the "more real images" theme that motivates item 3.

Feedback verbatim:

> Everything looks good, but I think we should use more real images, especially on the landing
> page. Adding more authentic photos will help build trust and make the website feel more genuine.
>
> 1. Colours should be aligned with logo
> 2. In contact details mention Instagram / Facebook and YouTube ID
> 3. Real images (from food drive, campaign and other activities)
> 4. Change 'Together we can bring change' to 'Together, We can create a better Tomorrow'

**The headline finding:** only item 1 and item 4 are purely code. Item 2 is ~80% content (the code path
already exists and is wired up — the values are simply empty in the database). Item 3 is almost
entirely content — the client must supply photographs; the code side is small. So the critical path
runs through **getting photos and social URLs from the client**, not through engineering.

---

## 1. Colours should be aligned with logo

### What I found

I sampled the logo's actual pixels rather than eyeballing it. The wordmark green is **`#0c722b`**
(hue 138°, saturation 0.81). The site's primary brand green is **`#1f7a4d`** (hue 150°, saturation
0.75).

That is a real, visible gap — 12° of hue. The site's green is measurably **more teal/blue** and more
muted than the logo's, which is a purer, warmer, more saturated green. Side by side, the header logo
and the header's own buttons are two different greens. The client is right.

The **accent orange is already fine**: site `#e08a1e` (hue 33°) vs logo `#f5921d` (hue 32°). No
meaningful difference — leave it alone.

The logo mark also contains a full multicolour ring (orange, lime, teal, blue, navy, purple, pink,
red). That is *not* a reason to scatter those colours across the UI, but it is a legitimate source
for a categorical palette if we ever want the six "Browse by Cause" tiles colour-coded. Optional,
noted below.

### The risk, and why it turns out to be low

`resources/css/tokens.css` documents an exact WCAG contrast ratio next to every brand step (e.g.
`--brand-500: #1f7a4d;   /* white 5.32 */`). Those ratios are load-bearing — buttons put white text
on `brand-500`, trust badges put `brand-800` on `brand-100`, and so on. Naively swapping in the
logo's green would silently break accessibility.

**I tested this before proposing it.** Holding the logo's hue and saturation fixed and solving for
the lightness that preserves each documented ratio produces a ramp that meets or beats every current
value:

| Step | Current | Ratio | Proposed | Ratio |
|---|---|---|---|---|
| `brand-500` | `#1f7a4d` | 5.32 | `#0d7c2f` | **5.33** |
| `brand-600` | `#19643f` | 7.15 | `#0b6526` | **7.23** |
| `brand-700` | `#145133` | 9.31 | `#09521f` | **9.38** |
| `brand-800` | `#103d26` | 12.23 | `#073e18` | **12.27** |
| `brand-900` | `#0b291a` | 15.60 | `#042a10` | **15.63** |

So this is a safe change: the palette becomes visibly logo-accurate with **zero contrast regression**.

### Work

- Recompute the full `--brand-50` … `--brand-900` ramp in `tokens.css` at the logo's hue/saturation,
  using the table above for 500–900 and interpolating 50–400 to match.
- Update the ratio comments to the newly measured values (they are documentation that must stay true).
- Re-verify every derived token that references the ramp (`--color-action`, `--color-link`,
  `--color-focus`, `--color-trust-*`, `--color-success*`) — these are aliases, so they follow
  automatically, but the *pairings* (e.g. `trust-text` on `trust-bg`) need a fresh contrast check.
- Run `npx vite build && node scripts/check-dead-classes.mjs`, then visually diff header, buttons,
  trust badges, footer band, and the About/monthly-giving hero bands.

**Effort:** small (~1 focused pass). **Risk:** low, given the ratios above are already proven.

### Optional, ask first

Colour-coding the six cause tiles from the logo mark's ring. Nice touch, but it is scope the client
did not ask for, and it can make a page look busier. I would not do this unless they want it.

---

## 2. Instagram / Facebook / YouTube in contact details

### What I found

The plumbing is **already fully built**:

- `SettingsSeeder` defines `social.facebook`, `social.instagram`, `social.twitter`,
  `social.linkedin`, `social.youtube`.
- The Filament admin has a **Social tab** (`OrganisationSettings.php`) with URL inputs for all five.
- The footer already reads them and renders a link row.

**But all five values are `null` in the production database.** The footer does
`array_filter([...])`, so with everything empty the entire social row disappears. That is why the
live site shows no social links anywhere — confirmed by fetching the homepage: zero occurrences of
"instagram", "facebook", or "youtube".

So the *primary* fix is data entry, not code. The three URLs the client supplied:

| Network | URL |
|---|---|
| Instagram | `https://www.instagram.com/visiongoodworkglobal` |
| YouTube | `https://youtube.com/@visiongoodworkglobal` |
| Facebook | `https://www.facebook.com/share/1TP1e9KajR/` |

*(I stripped the `igsh=` / `si=` tracking parameters — those are per-share referral tokens, not part
of the profile address, and they look untidy in a stored setting. The Facebook one is a share-link
redirect rather than a clean vanity URL; worth asking the client for the real page URL, e.g.
`facebook.com/VisionGoodWorkGlobal`, so the stored value stays stable if the share link ever expires.)*

### Genuine code work that remains

The client said "**in contact details**" specifically, and there the current build falls short:

- The footer renders socials in the **branding column**, not the **Contact** column, and renders them
  as **plain text words** ("Facebook", "Instagram") rather than recognisable icons.
- The **`/contact` page has no social links at all** — I grepped it; there is no reference to any
  network. This is exactly the "contact details" surface the client means.

So:

- Add a social row to `/contact`'s details block, with proper brand icons (inline SVG, consistent
  with how every other icon in this codebase is done — no icon-font dependency).
- Move/duplicate the footer socials into the Contact column and switch text labels to icons with
  `aria-label`s for screen readers.
- Extract a small `<x-social-links>` component rather than writing the markup three times — this hits
  the codebase's existing "fourth repeat" extraction convention.
- Keep everything settings-driven and `array_filter`-guarded, so a network the client later leaves
  blank simply vanishes instead of rendering a dead icon.

**Effort:** small. **Risk:** very low.

---

## 3. Real images from food drives, campaigns and activities

### What I found — and why the client's instinct is correct

The site currently ships **two stock/AI-generated hero images**. I opened
`hero-volunteer.png`: it is a synthetic composite of a volunteer and an elderly woman, with the
foundation's logo digitally pasted onto the t-shirt. It is precisely the kind of image that reads as
inauthentic to a donor deciding whether to trust an NGO with money. `hero-community.png` is the same
class of asset.

Those two files appear in **four** places:

| File | Used by |
|---|---|
| `hero-volunteer.png` | homepage hero (fallback branch) |
| `hero-community.png` | footer newsletter band, About page hero band |

They are also **heavy** — 792 KB and 603 KB PNGs. Real photographs should be delivered as
compressed WebP/JPEG, or they will make the landing page noticeably slower on Indian mobile networks.

### The good news: admin upload paths already exist

There is already a Filament resource for **Banners** (with `image_path`, `mobile_image_path`,
`title`, `subtitle`, `cta_label`) and for **Gallery Photos**. The homepage hero already prefers a
DB-driven banner and only falls back to the stock image when no banner exists — so **uploading a real
banner through the admin replaces the hero with zero code changes**.

Every image slot in the system, for the client's shot list:

| Model | Field | Surface |
|---|---|---|
| `Banner` | `image_path`, `mobile_image_path` | homepage hero |
| `Campaign` | `cover_image_path` | campaign cards + detail |
| `CampaignProduct` | `image_path` | needs-catalogue items |
| `CampaignUpdate` | `image_path` | campaign updates |
| `GalleryPhoto` | `image_path` | `/gallery` (currently empty) |
| `Post` | `cover_image_path` | blog (currently empty) |
| `Member` | `photo_path` | team |
| `Partner` / `PressMention` | `logo_path` | partner & press strips |
| `Testimonial` | `avatar_path` | testimonials |

### Work

**Client-side (blocking — this is the critical path):**

Request a batch of real photographs, ideally: 2–3 landscape hero-quality shots (food drive,
distribution, volunteers in action), 6–10 activity photos for the gallery, and a cover photo per
active campaign. Landscape, minimum ~1600px wide, with permission to publish. Flag consent
explicitly — photographs of beneficiaries, especially children, need the client's confirmation that
they have the right to publish them.

**Code-side (small, and worth doing regardless):**

- Add an image-optimisation step so uploads are converted/resized rather than served as 800 KB PNGs.
- Once gallery photos exist, build the gallery lightbox already specced in
  `docs/14 §8` (reusing the existing `<x-modal>`).
- Replace the two stock files once real equivalents land, and delete them so they cannot silently
  reappear as a fallback.
- Consider a homepage activity/gallery teaser strip — currently the homepage has no photo content at
  all between the hero and the footer, which is a large part of why it "feels" less genuine.

**Effort:** code small; content is the long pole. **Risk:** low, but blocked on the client.

---

## 4. Tagline change

### What I found

The live hero reads:

```
TOGETHER,
WE CAN BRING
CHANGE
```

This lives in `resources/views/public/home.blade.php:104-106`, in the **fallback branch** — the branch
used only when no `Banner` row exists. Since production has no banners, this is what everyone sees.

**This matters for how we implement the change.** If we only edit the Blade fallback and the client
later uploads a hero banner through the admin, the banner's own `title` wins and the new tagline
silently disappears. So:

- Update the hardcoded fallback to the new wording, **and**
- Note in the handover that any Banner created in the admin must use the same title, since the banner
  title overrides it.

There is also a **second, different string** — an eyebrow above the impact stats at
`home.blade.php:175` reading "Together we can". It is not the phrase the client quoted, but leaving
it as-is next to a changed hero would read as inconsistent. I would fold it into the same edit.

### Proposed rendering

Preserving the existing three-line structure and the emphasised final line:

```
TOGETHER,
WE CAN CREATE A
BETTER TOMORROW
```

One caveat to raise: "BETTER TOMORROW" is noticeably longer than "CHANGE", and the hero H1 is
`text-6xl` at desktop. This needs a check at mobile, tablet and desktop widths to confirm it does not
wrap awkwardly or overflow the scrim. Possible mitigation is dropping the final line to `text-5xl`,
which I would only do if it actually breaks.

**Effort:** trivial edit, plus responsive verification. **Risk:** low — layout only.

---

## Sequencing

| # | Item | Blocked on client? | Effort |
|---|---|---|---|
| 4 | Tagline | No | Trivial |
| 1 | Logo-aligned colours | No | Small |
| 2 | Socials in contact details | Partly — Facebook canonical URL | Small |
| 3 | Real images | **Yes — needs photos** | Small code, large content |

**Recommended order:** ship 4 → 1 → 2 as a single batch now (all unblocked, all verifiable
immediately), then do 3 as a follow-up once photographs arrive. That gets the client visible movement
on three of four items without waiting on a photo shoot.

Every change verifies through the existing gate before merge: `npx vite build`,
`node scripts/check-dead-classes.mjs`, `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`,
`./vendor/bin/pest`.

---

## Status — updated 2026-08-08

Client decisions taken during the session:

| Item | Decision | State |
|---|---|---|
| Tagline (§4) | — | **Done**, committed |
| Colours (§1) | — | **Done**, committed |
| Socials (§2) | — | **Done**, committed |
| Demo data | *Remove now* | Command built + tested; **awaiting production run** |
| Impact stats | *Placeholder — client will send real figures* | Left in place, see caveat below |
| Video | *YouTube + Instagram strip* | YouTube **done**; Instagram blocked |
| Real photos (§3) | — | Blocked on client |

**Caveat raised and accepted:** removing the demo campaigns while keeping the placeholder
impact stats leaves the site claiming "48,500+ meals served" with no campaigns listed. The
client opted to keep the stats and supply real figures. Until those arrive, the live site
publishes unverified impact numbers — worth revisiting if the real figures are slow to come.

### What the Instagram strip actually requires

Recorded here because it is entirely client-side setup; no amount of engineering unblocks it.
(These are Meta's requirements as of the last time I checked — they change periodically, so
verify before starting.)

1. The Instagram account must be a **Business or Creator** account (not personal).
2. It must be **linked to the Facebook Page**.
3. Someone with admin rights on that Page must create a **Meta Developer app**.
4. The app needs **App Review** approval for Instagram media permissions.
5. The app issues a **long-lived access token that expires roughly every 60 days**.

Once a token exists, the engineering side is: a cached fetch (never hit Meta per page view),
a scheduled refresh job before each 60-day expiry, and a graceful empty state so an expired
token hides the strip instead of breaking the page.

**This is genuine ongoing maintenance for a decorative strip.** The three social links now in
the footer and on `/contact` already give visitors a route to the live feeds with zero
dependency on Meta's API. Worth confirming the strip is still wanted before doing the setup.

## Open questions for the client

1. **Facebook URL** — the supplied link is a `share/` redirect. Is there a canonical page URL?
2. **Photo consent** — confirmation that supplied photographs, particularly any showing
   beneficiaries or children, may be published publicly.
3. **Cause-tile colour-coding** — use the logo mark's multicolour ring for the six category tiles,
   or keep them uniformly green? (Not requested; asking because the logo makes it available.)
4. **Twitter/X and LinkedIn** — the feedback names only three networks. Do these exist and should
   they appear, or should those settings stay empty?
