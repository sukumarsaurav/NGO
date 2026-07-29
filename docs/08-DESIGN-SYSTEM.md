# 08 — Design System

**Phase 0 deliverable** · Executable source: [`resources/css/tokens.css`](../resources/css/tokens.css)
and [`tailwind.config.js`](../tailwind.config.js)

## Audit result

A design *foundation* existed in `06-UI-UX-FOUNDATION.md` §2–4: a colour palette with verified
contrast ratios, a type scale, a layout contract, and a list of thirteen component names. That is
roughly a third of a design system, and the strongest third — the palette work is sound and is carried
forward here unchanged.

What was missing is what actually prevents drift across four surfaces and sixteen sprints:

| # | Gap | Consequence if left |
|---|---|---|
| 1 | **No z-index scale** | §3 says the WhatsApp float and the sticky donate bar "must not overlap" — with no scale, that is resolved by whoever writes `z-50` last. Seven fixed layers exist. |
| 2 | **No radius / shadow / spacing values** | "Three radii, three shadows" names them without defining them. Not tokens. |
| 3 | **No component state specs** | Thirteen component *names*, zero definitions of hover, active, focus, disabled, loading, error. This is where consistency actually lives. |
| 4 | **No focus-ring token** | "Visible focus rings" with no value means every developer invents one — and the obvious choice is broken (see §8). |
| 5 | **No motion tokens** | Durations and easing invented per component; `prefers-reduced-motion` handled ad hoc. |
| 6 | **No form-field anatomy** | Label, hint, error, required marker placement undefined — on a product whose highest-stakes screen is a form. |
| 7 | **No icon system** | No library, size scale, or stroke weight. |
| 8 | **No dark-mode decision** | Filament v5 ships dark mode **on by default**. Unstated, the admin panel gets a dark theme built from Filament's default greys while the public site is light-only. |
| 9 | **No token layering rule** | The palette mixes primitives (`brand-500`) and semantics (`border`) with no stated relationship. |
| 10 | **No button hierarchy** | Three fill colours available, no rule for which to use when. |
| 11 | **No governance or do-not list** | Nothing says who may add a token, so everyone does. |

This document closes all eleven. Colour values move here from `06 §2`, which keeps the *reasoning*
(why green rather than amber, how the ratios were measured) and points here for the numbers — one
source per fact.

---

## 1. Token architecture

Three layers. Components never reach past the layer above them.

```
PRIMITIVE            SEMANTIC                  COMPONENT
raw values           meaning                   usage
──────────           ────────                  ─────────
brand-500  ────────► color-action      ───────► btn-primary-bg
#1f7a4d              color-link                link-color
                     color-focus

space-4    ────────► space-inline-sm   ───────► btn-padding-x
```

**Blade and Tailwind classes use the semantic layer.** `bg-action`, not `bg-brand-500`. The primitive
ramp exists so semantics can be re-pointed — if the NGO rebrands, `--color-action` changes once and
forty templates follow. A template that hard-codes `brand-500` opts out of that.

The only place primitives are legitimate is inside `tokens.css` itself, and in charts or illustrations
that need a specific ramp step.

---

## 2. Colour

Adopted from the approved brand reference with two corrections. The reasoning is in
[`06-UI-UX-FOUNDATION.md`](06-UI-UX-FOUNDATION.md) §2; the values are here.

### Primitive ramps

| Step | `brand` (green) | legible on it | `accent` (amber) | legible on it |
|---|---|---|---|---|
| 50 | `#f2f7f4` | dark 14.24 | `#fdf8f2` | dark 14.60 |
| 100 | `#e0ece6` | dark 12.71 | `#fbefe0` | dark 13.60 |
| 200 | `#bcd7ca` | dark 10.07 | `#f6dcbc` | dark 11.66 |
| 300 | `#8fbca6` | dark 7.28 | `#f0c48e` | dark 9.54 |
| 400 | `#579b7a` | dark 4.68 | `#e8a756` | dark 7.41 |
| **500** | **`#1f7a4d`** | **white 5.32** | **`#e08a1e`** | **dark 5.75** |
| 600 | `#19643f` | white 7.15 | `#b87119` | ⛔ **neither** |
| 700 | `#145133` | white 9.31 | `#945b14` | white 5.58 |
| 800 | `#103d26` | white 12.23 | `#70450f` | white 8.24 |
| 900 | `#0b291a` | white 15.60 | `#4c2f0a` | white 12.22 |

**`accent-600` carries no text, ever.** 3.88 against white, 3.98 against dark — legible on neither. It
is listed so nobody rediscovers it the hard way. It may be used as a non-text surface (a border, a
bar fill) where 3:1 suffices.

### Neutrals & status

| Token | Hex | Ratio on `#faf9f6` | Role |
|---|---|---|---|
| `background` | `#faf9f6` | — | Page base |
| `surface` | `#ffffff` | — | Cards, panels, modals |
| `surface-muted` | `#f0eee7` | — | Alternating sections, table stripes |
| `text-primary` | `#21261f` | 14.65 | Body copy |
| `text-muted` | `#55524a` | 7.41 | Secondary copy, hints |
| `text-placeholder` | `#6b685f` | 5.57 on white | Input placeholders |
| `text-disabled` | `#8d8a80` | 3.28 | Disabled labels — see note |
| `border` | `#86816f` | 3.71 | **Interactive control boundaries** |
| `divider` | `#d9d4c6` | 1.41 | Decorative rules only, never a control edge |
| `danger` | `#c0392b` | 5.17 | Errors |
| `success` | `#1f7a4d` | 5.05 | Confirmations (= `brand-500`) |
| `warning` | `#945b14` | — | Warnings (= `accent-700`, white text 5.58) |
| `info` | `#1f5f7a` | — | Neutral information, white text 7.06 |
| `alert` | `#b23a2e` | — | **Urgent campaign badge only**, white text 5.94 |

> **`text-disabled` at 3.28 is below 4.5 deliberately.** WCAG 1.4.3 exempts inactive controls, and a
> disabled field that meets full contrast does not read as disabled. Never use this token for
> anything a user is expected to read.

> **`alert` and `danger` are near-indistinguishable reds.** Constrained by usage rather than
> re-picked: brick appears *only* on "Urgent" campaign badges, which is semantically adjacent to an
> alarm. Everything error-shaped uses `danger`.

### Status tints

Each status has a **solid** colour (icon, border, filled button) and a **tint pair** (surface + text)
for the Alert component and badges. Pairing a tint background with the solid text colour is a common
and untested habit — these pairs are measured.

| Status | Solid | Tint bg | Tint text | Ratio |
|---|---|---|---|---|
| `danger` | `#c0392b` | `#f9e7e4` | `#8a2a1f` | 7.23 |
| `success` | `#1f7a4d` | `#f2f7f4` | `#145133` | 8.60 |
| `warning` | `#945b14` | `#fdf8f2` | `#70450f` | 7.81 |
| `info` | `#1f5f7a` | `#eef5f8` | `#1f5f7a` | 6.40 |
| `alert` (urgent) | `#b23a2e` | `#f9e7e4` | `#7a2820` | 8.17 |

Scrim behind modals and the drawer: `rgb(33 38 31 / 0.55)`.

### Semantic mapping

| Semantic token | Value | Used by |
|---|---|---|
| `color-action` | `brand-500` | Donate button, primary buttons |
| `color-action-hover` | `brand-600` | |
| `color-action-active` | `brand-700` | |
| `color-link` | `brand-600` | Body links (7.15 on background) |
| `color-link-hover` | `brand-700` | |
| `color-focus` | `brand-600` | Focus rings — see §8 |
| `color-trust` | `brand-800` on `brand-100` | 80G / Verified badges, 10.08 |
| `color-highlight` | `accent-500` | Secondary CTAs, category tiles, progress fill |

---

## 3. Typography

**Inter** (variable, self-hosted) + **Noto Sans Devanagari** (subset) for Hindi CMS content. No Google
Fonts CDN request on the critical path.

| Token | px / rem | Line height | Weight | Use |
|---|---|---|---|---|
| `text-xs` | 12 / 0.75 | 1.4 | 500 | Badges, table meta, legal fine print |
| `text-sm` | 14 / 0.875 | 1.45 | 400 | Hints, captions, dense admin tables |
| `text-base` | 16 / 1 | 1.6 | 400 | **Body. Never smaller on mobile.** |
| `text-lg` | 18 / 1.125 | 1.55 | 400 | Lead paragraphs, campaign summary |
| `text-xl` | 20 / 1.25 | 1.4 | 600 | Card titles, section subheads |
| `text-2xl` | 24 / 1.5 | 1.3 | 600 | `h3` |
| `text-3xl` | 30 / 1.875 | 1.25 | 700 | `h2` |
| `text-4xl` | 36 / 2.25 | 1.2 | 700 | `h1` mobile |
| `text-5xl` | 48 / 3 | 1.1 | 700 | `h1` desktop, hero |

**16px is a hard floor for any input on mobile.** iOS Safari zooms the viewport when focusing an input
under 16px, which on the donation form reads as a bug and loses donations.

Tabular numerals (`font-variant-numeric: tabular-nums`) on every money figure, progress percentage and
donor count, so digits do not jitter as values update.

---

## 4. Spacing

4px base scale. Named steps only — no arbitrary values in Blade.

| Token | px | Typical use |
|---|---|---|
| `space-1` | 4 | Icon-to-label gap |
| `space-2` | 8 | Inside badges and pills |
| `space-3` | 12 | Form label to input |
| `space-4` | 16 | **Default gap.** Card padding mobile, button padding-x |
| `space-6` | 24 | Card padding desktop, gap between form fields |
| `space-8` | 32 | Between card groups |
| `space-12` | 48 | Section padding mobile |
| `space-16` | 64 | Section padding tablet |
| `space-24` | 96 | Section padding desktop |

**Section rhythm:** every homepage and landing section uses `space-12 / space-16 / space-24` vertical
padding at mobile / tablet / desktop. Uniform rhythm is most of what makes a long page feel designed.

**Container:** `max-width: 1200px`, gutter `space-4` at mobile, `space-6` at `md+`.

---

## 5. Radius, border, elevation

| Token | Value | Applies to |
|---|---|---|
| `radius-sm` | 6px | Inputs, selects, textareas, small badges |
| `radius-md` | 10px | Buttons |
| `radius-lg` | 16px | Cards, modals, donation card |
| `radius-full` | 9999px | Pills, avatars, category tiles, WhatsApp float |

| Token | Value | Meaning |
|---|---|---|
| `shadow-sm` | `0 1px 2px rgb(33 38 31 / 0.06)` | Resting card |
| `shadow-md` | `0 4px 12px rgb(33 38 31 / 0.10)` | Hover, sticky header once scrolled |
| `shadow-lg` | `0 12px 32px rgb(33 38 31 / 0.16)` | Modals, drawer, floating bars |

**Border or shadow, not both.** A card sits on `surface` with `shadow-sm` and no border. A form panel
sits on `background` with a `divider` border and no shadow. Mixing them is the single most common
source of "this looks slightly off" across a codebase.

Shadows are tinted with the text colour (`33 38 31`), not pure black — pure black shadows read grey
and dead against a warm background.

---

## 6. Z-index scale

Seven fixed layers exist in this product. `06 §3` states the WhatsApp float and the sticky donate bar
"must not overlap"; that is a z-index problem and needs a scale, not a convention.

| Token | Value | Layer |
|---|---|---|
| `z-base` | 0 | Page content |
| `z-sticky-nav` | 100 | Campaign anchor sub-nav (Products/Story/Updates) |
| `z-header` | 200 | Site header |
| `z-donate-bar` | 300 | Mobile sticky donate bar |
| `z-float` | 400 | WhatsApp button — **above the donate bar**, per `06 §3` |
| `z-drawer` | 500 | Mobile nav drawer + its scrim |
| `z-modal` | 600 | Checkout modal, confirmations |
| `z-toast` | 700 | Notifications — always on top of everything |

**No raw `z-index` values anywhere.** If a new layer is needed, it gets a token and a row in this
table. The gaps of 100 exist so a layer can be inserted without renumbering.

---

## 7. Motion

| Token | Value | Use |
|---|---|---|
| `duration-fast` | 120ms | Hover, focus, colour changes |
| `duration-base` | 200ms | Dropdowns, accordions, toasts |
| `duration-slow` | 320ms | Drawer, modal, sticky-bar entrance |
| `ease-out` | `cubic-bezier(0.16, 1, 0.3, 1)` | Things entering |
| `ease-in-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | Things moving or leaving |

**Animate only `transform` and `opacity`.** Animating `height`, `top` or `width` triggers layout on
every frame and will cost the CLS budget (`M10`: < 0.1).

**`prefers-reduced-motion: reduce` collapses every duration to 0ms** via one global rule in
`tokens.css` — not per component. The hero carousel stops auto-advancing and impact counters render
their final value immediately.

---

## 8. Focus & interaction states

### The focus ring, and the bug in the obvious version

Every interactive element gets a visible focus ring on `:focus-visible`. The intuitive choice —
`color-focus` (`brand-600`) drawn directly around a control — **fails on the most important control in
the product**: a green ring on the green donate button measures **1.35:1** and is effectively
invisible.

So the ring is always drawn with an offset:

```
outline: 2px solid var(--color-focus);
outline-offset: 2px;          /* the gap renders in the parent's background */
```

On a filled button the 2px gap exposes the page background between button and ring, and the ring reads
at 6.79:1. Verified pairs:

| Context | Ratio |
|---|---|
| Ring on `background` | 6.79 |
| Ring on `surface` | 7.15 |
| Ring directly on `brand-500` (**never do this**) | 1.35 ⛔ |

`:focus-visible`, not `:focus` — mouse users should not see rings on click. Never `outline: none`
without a replacement in the same rule.

### State matrix

Applies to every interactive component unless its spec overrides.

| State | Treatment |
|---|---|
| Default | Token values |
| Hover | One ramp step darker (`-600`), `duration-fast` |
| Active | Two steps darker (`-700`), no transition (instant feels responsive) |
| Focus-visible | Offset ring above, **in addition to** any hover treatment |
| Disabled | `text-disabled`, `surface-muted` fill, `cursor: not-allowed`, no hover |
| Loading | Spinner replaces label, width locked to prevent reflow, `aria-busy="true"` |
| Error | `danger` border + text message. **Never colour alone** — the message is required |

---

## 9. Icons

**Heroicons** (outline 24px, solid 20px) — ships with Filament, so the admin and public sides use one
set rather than two.

| Size | px | Use |
|---|---|---|
| `icon-sm` | 16 | Inline with `text-sm`, badge icons |
| `icon-base` | 20 | Buttons, form affordances |
| `icon-lg` | 24 | Nav, section headers |
| `icon-xl` | 32 | Category tiles, empty states |

Icons are inlined as SVG, never icon fonts. Decorative icons get `aria-hidden="true"`; an icon that is
the only content of a control gets an `aria-label`. Icon stroke is 1.5px at all sizes — 2px reads
heavy next to Inter.

---

## 10. Components

Thirteen components, each with anatomy, variants and states. This section is what `06 §4` listed by
name only.

### 10.1 Button

| Variant | Fill | Text | Use |
|---|---|---|---|
| `primary` | `color-action` | white | **Donate.** One per view. |
| `secondary` | `brand-50` | `brand-700` (8.60) | Cancel, back, secondary actions |
| `tertiary` | transparent | `color-link` | Low-emphasis, inline |
| `accent` | `accent-500` | `text-primary` (5.75) | "Start a fundraise", category CTAs |
| `danger` | `danger` | white (5.44) | Destructive, admin only |

Sizes: `sm` 32px, `base` 40px, `lg` 48px, `xl` 56px (donate CTA only). Minimum touch target 44×44 —
the `sm` size is admin-only and never on a public touch surface.

- Radius `radius-md`, padding-x `space-4`, weight 600.
- **The donate button always carries its amount**: `Donate ₹1,000`, updating live.
- Icons sit left of the label at `space-2`, except a trailing chevron.
- **Never two `primary` buttons in one view.** If two things look equally important, neither is.

### 10.2 Form field

Anatomy, top to bottom, with fixed spacing:

```
Label *                           ← text-sm, 600, text-primary; * in danger
                                     space-3
┌────────────────────────────┐    ← 44px min, radius-sm, 1px border
│ Placeholder                │       focus: ring + border → color-focus
└────────────────────────────┘       error: border → danger
                                     space-2
Helper text or error              ← text-sm; muted normally, danger on error
```

- Error text is linked via `aria-describedby` and announced with `role="alert"`.
- Required is marked with `*` **and** `required` — never colour or placement alone.
- Placeholders are examples, never labels. A field whose only label is its placeholder loses its
  label the moment the user types.
- **16px minimum font size** (§3).

### 10.3 Campaign card

Image (4:3, lazy, explicit dimensions) → title (2 lines, ellipsis, `title` attribute) → beneficiary
(`text-sm muted`) → tax badge → progress bar → raised / goal / donor count → Donate → share row.

`surface`, `radius-lg`, `shadow-sm`, hover `shadow-md` + 2px lift via `transform`. Whole card is a
link; the Donate and share controls are nested interactive elements, so the card uses a stretched-link
overlay rather than a wrapping `<a>` — nested anchors are invalid and break keyboard order.

### 10.4 Progress bar

Track `surface-muted`, fill `accent-500`, height 8px, `radius-full`. **Always paired with a text
percentage** — colour and length alone fail the no-colour-only rule and are meaningless to a screen
reader. `role="progressbar"` with `aria-valuenow/min/max`. Over 100% renders "107% funded" with the
bar capped full.

### 10.5 Trust badge

| Variant | Fill | Text | Icon |
|---|---|---|---|
| `80g` | `brand-100` | `brand-800` (10.08) | shield-check |
| `verified` | `brand-100` | `brand-800` | badge-check |
| `secure` | `surface-muted` | `text-muted` | lock |
| `urgent` | `#f9e7e4` | `#7a2820` (8.17) | bolt |

`radius-full`, `space-2` padding-x, `text-xs` weight 500. These are labels, not buttons — never
clickable, never with hover states.

### 10.6 Stat tile

Value (`text-3xl`, 700, tabular-nums) + label (`text-sm`, muted). **Container has a `min-width` so
count-up animation cannot reflow the row** — the classic CLS offender. Respects reduced motion by
rendering the final value immediately.

### 10.7 Category tile

Icon (`icon-xl`) + label, `radius-full` or `radius-lg`, `surface` with `divider` border. Active state
fills `brand-50` with `brand-700` text and a 2px `color-action` border — **not colour alone**; the
active tile also carries `aria-current="page"`.

### 10.8 Share buttons

Facebook, WhatsApp, LinkedIn, X, copy-link. `icon-base` in `radius-full` `surface-muted` buttons.
WhatsApp is always first — it is the distribution channel that matters in India. Copy-link shows a
"Copied" toast, never a silent success.

### 10.9 Alert

Four variants — `info`, `success`, `warning`, `danger` — as a tinted surface with a 4px left border in
the status colour, an icon, and body text. Never colour alone: each carries an icon *and* a text
label. Dismissible alerts keep focus management (focus moves to the next element, not to `<body>`).

### 10.10 Empty state

Icon (`icon-xl`, muted) + heading (`text-lg`) + one line of body + optional primary action. Every
list, grid and report has one. `M10` requires this on campaign grids and `M12` on empty reports; it is
a component, not a per-page improvisation.

### 10.11 Skeleton

`surface-muted` blocks at the exact dimensions of the content they replace — a skeleton that is not
the final size causes the layout shift it exists to prevent. Subtle shimmer via `opacity`, disabled
under reduced motion.

### 10.12 Modal

`radius-lg`, `shadow-lg`, `z-modal`, scrim `rgb(33 38 31 / 0.55)`. Focus trapped inside; focus
returns to the trigger on close; `Esc` closes; background scroll locked. Max-width 560px, full-width
minus `space-4` below `sm`.

The **checkout modal** is the one that matters — its content spec is `06 §5` and its exit-intent
behaviour is `06 §5` rule 10.

### 10.13 Toast

`z-toast`, top-right desktop / top-full mobile, auto-dismiss 5s (never for errors), `role="status"`
for success and `role="alert"` for errors. Maximum three stacked; older ones drop.

---

## 11. Dark mode

**The public site and portal are light-only. Filament's dark mode is disabled.**

This is a decision, not an omission. Filament v5 ships dark mode enabled by default; left alone, the
admin panel would render a dark theme assembled from Filament's stock greys that has nothing to do
with this palette, while `/portal` — built in Blade against the same brand — stays light. Two
surfaces, one brand, two unrelated appearances.

Disabling it costs one line in each panel provider. Revisiting it later is a deliberate project with
a full second set of contrast measurements, not a toggle.

```php
// AdminPanelProvider + ManagerPanelProvider
->darkMode(false)
```

---

## 12. Filament alignment

The admin and manager panels register the *same* primitives:

```php
->colors([
    'primary' => Color::hex('#1f7a4d'),   // brand-500
    'danger'  => Color::hex('#c0392b'),
    'warning' => Color::hex('#945b14'),   // accent-700 — carries white text
    'success' => Color::hex('#1f7a4d'),
    'info'    => Color::hex('#1f5f7a'),
])
```

Note `warning` maps to `accent-700`, not `accent-500`. Filament renders warning badges with white
text, and white on `accent-500` is the 2.68:1 failure this whole palette was corrected to avoid.

Admin tables run at `text-sm` with `space-3` cell padding — denser than the public site by design;
an operator scanning 200 donations needs different density from a donor reading one campaign.

---

## 13. Do not

The fastest sources of drift, listed so review can point at them:

1. **No arbitrary Tailwind values** — `p-[13px]`, `text-[#1f7a4d]`, `z-[999]`. If it is not a token,
   it does not ship.
2. **No raw hex in Blade or component classes.** Semantic tokens only.
3. **No `!important`.** It means two things claim the same property; fix the specificity.
4. **No `outline: none`** without a replacement ring in the same rule.
5. **No new shadow, radius, duration or z-index** invented at the component level.
6. **No colour-only signalling** — status, errors, progress and active states all carry text or an icon.
7. **No text on `accent-600`.**
8. **No two primary buttons in one view.**
9. **No animating `height`, `width` or `top`** — `transform` and `opacity` only.
10. **No component built inline in a page template** when it appears more than three times. Extract it.

---

## 14. Governance

**Adding a token** requires a PR touching `tokens.css`, `tailwind.config.js` and the relevant table
here, plus a measured contrast ratio if it is a colour. Three files is deliberate friction — tokens
should be rare.

**Adding a component** requires an entry in §10 with anatomy, variants and states, and a rendering in
the `/dev/components` gallery at 360 / 768 / 1280px.

**The gallery is the regression test.** `06 §13` makes it a Phase 0 deliverable; it is also where a
reviewer checks that a change did not alter twelve other things. A component that is not in the
gallery is not in the system.

**Review checklist** (add to the PR template in `05-CONVENTIONS.md` §PR checklist):

- [ ] No arbitrary values, no raw hex, no raw z-index
- [ ] New colour pairs measured and recorded
- [ ] Focus-visible ring present and visible against its own background
- [ ] Interactive states: hover, active, focus, disabled, loading, error
- [ ] Touch targets ≥ 44×44 on public surfaces
- [ ] Renders at 360px without horizontal scroll
- [ ] Reduced-motion respected
- [ ] Gallery updated

---

Previous: [`07-SEO.md`](07-SEO.md) · Executable tokens:
[`resources/css/tokens.css`](../resources/css/tokens.css)
