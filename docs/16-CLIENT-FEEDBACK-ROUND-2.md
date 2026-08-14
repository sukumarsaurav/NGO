# Client Feedback, Round 2 — Mobile Logo and Footer

**Date:** 2026-08-14 · **Source:** client review of the live site
**Status:** both items done.

Feedback verbatim:

> Logo is not clear in mobile view and footer is not optimized

Two items, both purely code — no content or client input was needed for either.

---

## 1. Logo is not clear in mobile view

### What I found

The client is right, and the cause is not a resolution problem. Below the `sm` breakpoint the
header rendered `logo-mark.png`, swapped in by a `<picture>` element, at 32×32 CSS pixels.

That file is not a mark. It is the **entire lockup** — the multicolour emblem *and* the
"VISION / GOOD WORK GLOBAL / FOUNDATION" wordmark — set inside a thin circular ring. At 32px:

- the organisation's name was an unreadable smudge roughly four pixels tall;
- the ring and its inner whitespace consumed about 40% of the box, so the artwork that *was*
  there rendered smaller still.

A visitor arriving on a phone could not read who the site belonged to.

There was also no space problem forcing that compromise. On a 375px viewport the header bar
holds only the Donate button and the hamburger, which together take ~128px. Around 215px sat
unused to the right of the logo.

### A second, separate defect in the asset

`logo-horizontal.png` was 1137×219, but its alpha bounding box was only 1094×173 — **~21% of the
file was transparent padding**. Because every usage sizes the logo by height (`h-8 w-auto`), that
padding was silently shrinking the visible logo inside whatever box we set, everywhere it appeared.

### Work done

- **Re-cut `logo-horizontal.png`** to its ink and resized to 760×120 (3× the largest on-screen
  width). The same CSS height now renders the lockup ~27% larger, and the file dropped from
  **193 KB → 93 KB**.
- **Header uses the horizontal lockup at every breakpoint.** `<picture>` and the mobile-only mark
  are gone, which also removes a second image request (the mark was a further 152 KB asset that
  existed for that one breakpoint).
- `h-8` — 32px tall, 203px wide against 215px of free bar at 375px — is the largest step on the
  project's spacing scale that clears the Donate button. `gap-3` on the header row guarantees the
  separation; `max-w-full object-contain` is the floor below 375px, so narrower phones scale the
  lockup down proportionally instead of letting it slide under the button. Verified at 320px.
- `width`/`height` attributes added, so the header no longer reflows when the image lands.

`logo-mark.png` is still used by the donor portal header (`layout/portal.blade.php`), also at 32px,
where it has the same legibility problem. Out of scope for this round — see below.

---

## 2. Footer is not optimized

### What I found

On a 375px viewport the footer was **1200px tall — a screen and a half** of scrolling past the end
of every page. Four distinct problems:

1. **One column on mobile.** Every column stacked full-width, one link per row, so fourteen links
   became fourteen rows.
2. **Unbalanced columns.** "Explore" had four links; "Information" had ten. On desktop that one
   column ran far past the other three.
3. **The bottom bar read as debris.** Five unlabelled payment words, three unlabelled registration
   numbers and the copyright line shared one flex row, which centre-stacked on mobile into a pile
   reading "UPI Visa Mastercard RuPay Net Banking".
4. **Legal pages were buried.** Privacy Policy and Terms were rows 9 and 10 of the long column,
   not in the bottom bar where visitors look for them.

Separately, the newsletter band shipped **`hero-community.png`, 792 KB, on every page of the site**.
The file was a JPEG misnamed `.png`, stored as a 1024×1024 square, and rendered `object-cover` in a
short wide band behind an 80% overlay — so most of that square was cropped away before anyone saw
it, and the overlay hid the detail in what remained.

### Work done

**Layout**

- **Two columns from the smallest screen up** (`grid-cols-2`). The labels are short enough to sit
  in ~160px, and this alone halves the link area.
- **Rebalanced into four columns of roughly four**: Explore / About Us / Get Involved / Contact.
  The long column was split and the legal pages moved to the bottom bar. **No link was dropped.**
- Desktop uses a **12-column track** so the brand block can take 4 and the content columns 2 each;
  a plain `lg:grid-cols-4` would have forced the brand block to the width of a list of short links.
  All four content columns now render the same height.
- The link groups are declared as data and rendered by one loop, rather than four hand-written
  near-identical columns.

**Tap targets**

- Footer links were bare inline anchors with `space-y-2` — the tap target was the ~17px of glyph.
  Now `space-y-1` on the list plus `py-1` on each link: same column height, but the whole row is
  tappable.

**Bottom bar**

- Payment marks and registration numbers each get a label ("We accept", "Registered") and render as
  bordered chips. They are trust signals — a donor verifying an NGO looks for exactly these, see
  `06-UI-UX-FOUNDATION.md` §3 — so they are worth setting as deliberate content.
- Privacy Policy and Terms now sit beside the copyright line.

**Weight**

- `hero-community.png` (792 KB) → **`hero-community.jpg`, 1280×720, 107 KB**, re-cut to the 16:9
  slice that is actually visible. An **86% reduction on every page**. The `.png` is deleted and both
  usages (footer newsletter band, and the About page hero in `public/pages/show.blade.php`) point at
  the new file.
- `width`/`height` and `decoding="async"` added to both footer images.

### Result

| | Before | After |
|---|---|---|
| Footer height, 375px viewport | 1200px | 1030px |
| Link rows on mobile | 14 | 7 |
| Longest desktop column | 10 links | 4 links |
| Newsletter band image | 792 KB | 107 KB |
| Logo asset | 193 KB | 93 KB |
| Logo requests per page | 2 | 1 |

---

## A note on the spacing scale

`tailwind.config.js` **replaces** Tailwind's spacing scale rather than extending it: only
`0, px, 1, 2, 3, 4, 6, 8, 12, 16, 24` exist. A class outside it compiles to nothing, silently.
The first pass of this work used `h-7`, `h-9`, `h-10`, `gap-y-10` and `gap-x-5`, all of which
evaporated. `npm run check:classes` caught all six — **run it after `npm run build`**, since it
checks the templates against the compiled stylesheet.

---

## Follow-ups not done in this round

1. **Donor portal header logo.** `layout/portal.blade.php` still renders `logo-mark.png` at 32×32
   and has the identical legibility problem the client reported for the public header. The public
   site was what the feedback was about, so the portal was left alone — but it should get the same
   treatment.
2. **`hero-volunteer.png` is 603 KB** and also a misnamed JPEG. It loads on the homepage, outside
   the footer, so it was left alone. The same re-cut would apply.
3. **Both logo files are still PNGs of what is fundamentally vector artwork.** `12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md`
   already recommends SVG; that would take the header logo from 93 KB to a few KB and make it sharp
   at any size. It needs the original vector file from the client's designer.
