/**
 * Count-up on scroll-into-view for the homepage impact stats (48,500+ meals served, etc.) —
 * see docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §1. Registered as an Alpine component
 * (`x-data="countUpStat(target)"`) rather than a one-off inline `x-data` object so the
 * animation logic lives in one place instead of being duplicated into every stat tile's
 * markup.
 *
 * `prefers-reduced-motion` is checked here, not left to the global CSS block in tokens.css
 * — that block forces `animation-duration: 0.01ms` on CSS animations/transitions, but this
 * is a `requestAnimationFrame` loop driving a text value, which CSS has no way to intercept.
 * Skipping the animation outright (not just speeding it up) is what the reduced-motion
 * preference actually asks for.
 *
 * Formats with `en-US` grouping (thousands separated by commas every 3 digits) to match
 * money amounts elsewhere on the site, which go through PHP's `number_format()` — a locale
 * mismatch here (Indian digit grouping, `1,00,000` vs `100,000`) would read as inconsistent
 * with every rupee figure on the same page.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('countUpStat', (target) => ({
        // Starts equal to the real, final value — matching the server-rendered text the
        // span carries before Alpine hydrates — not '0'. `x-text` syncs immediately on
        // init, before `x-intersect` ever fires; starting at '0' here would flash the
        // number down to zero the instant Alpine hydrates, on every stat, regardless of
        // scroll position, then again reset to 0 inside start(). Only start() (triggered by
        // scroll-into-view) is allowed to touch it before the count-up itself.
        display: target.toLocaleString('en-US'),
        started: false,
        start() {
            if (this.started) return;
            this.started = true;

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return; // already showing the final value
            }

            this.display = '0';
            const durationMs = 800;
            const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
            const startTime = performance.now();

            const step = (now) => {
                const progress = Math.min((now - startTime) / durationMs, 1);
                this.display = Math.round(target * easeOutCubic(progress)).toLocaleString('en-US');
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        },
    }));

    /**
     * Scrollspy for the campaign detail page's Products/Story/Updates/Donors/FAQ nav — see
     * docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §3. The header nav one level up already
     * has a scale-x underline that tracks the current *page*; this is the same treatment
     * tracking the current *section* on a long scroll instead.
     *
     * `rootMargin` shrinks the observed viewport to a thin horizontal band just below the
     * sticky header+section-nav stack (mirrors `--header-height` + the nav's own height) and
     * ignores the bottom 70% of the viewport — a section only counts as "active" while its
     * heading is genuinely near the top, not merely somewhere on screen, which is what makes
     * this read as "you are here" rather than flickering between whichever two sections
     * happen to both be partially visible.
     */
    window.Alpine.data('scrollspyNav', (ids) => ({
        active: null,
        init() {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) this.active = entry.target.id;
                    });
                },
                { rootMargin: '-112px 0px -70% 0px', threshold: 0 }
            );
            ids.forEach((id) => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });
        },
    }));
});

/**
 * Sticky-header scroll state.
 *
 * Marks any `[data-sticky-header]` with `data-stuck` once it has actually stuck to the
 * top of the viewport. The header then cross-fades its bottom divider out and its shadow
 * in — see the `[data-sticky-header]` block in resources/css/tokens.css.
 *
 * Works off a sentinel rather than a scroll listener, so it costs two callbacks per
 * direction change instead of work on every scroll frame.
 *
 * The sentinel is inserted at the header's resting position and is 1px tall with a -1px
 * bottom margin, so it occupies no net layout space. While it is on screen the header has
 * not moved; the moment it scrolls out of view the header is pinned. (Observing the header
 * itself with a negative top root margin — the more commonly cited trick — does not work
 * here: both headers sit at y=0, so they are clipped by that margin even at rest and
 * report themselves stuck immediately on load.)
 *
 * Deliberately vanilla. Alpine reaches these pages only through Livewire's bundle, which
 * the portal and guest layouts do not load; this has to work in all three.
 */
function initStickyHeaders() {
    const headers = document.querySelectorAll('[data-sticky-header]');
    if (headers.length === 0) return;

    // Without IntersectionObserver the header simply stays in its resting state: the
    // divider remains visible, so it still reads as separated from the content behind it.
    if (!('IntersectionObserver' in window)) return;

    for (const header of headers) {
        const sentinel = document.createElement('div');
        sentinel.setAttribute('aria-hidden', 'true');
        sentinel.style.cssText = 'height:1px;margin-bottom:-1px;pointer-events:none';
        header.parentNode.insertBefore(sentinel, header);

        new IntersectionObserver(
            ([entry]) => header.toggleAttribute('data-stuck', !entry.isIntersecting),
            { threshold: 0 },
        ).observe(sentinel);
    }
}

/**
 * Reveal the current page in a horizontally scrollable nav.
 *
 * The portal nav is a scrolling rail on narrow screens, so on `/portal/donations` the
 * "Donations" item can start off the right-hand edge — the user lands on a page whose nav
 * gives no indication of where they are. Nudge it into view.
 *
 * `inline: 'nearest'` scrolls the minimum distance needed rather than centring, so items
 * that are already visible do not move. `block: 'nearest'` keeps it from scrolling the
 * page vertically, and `behavior: 'instant'` opts out of the global `scroll-behavior:
 * smooth` — this is a correction applied before first paint, not a navigation.
 */
function revealCurrentNavItem() {
    for (const nav of document.querySelectorAll('nav')) {
        if (nav.scrollWidth <= nav.clientWidth) continue; // not scrollable, nothing to do
        const current = nav.querySelector('[aria-current="page"]');
        if (current) current.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: 'instant' });
    }
}

function init() {
    initStickyHeaders();
    revealCurrentNavItem();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// `wire:navigate` (see docs/14-UI-UX-AUDIT-LIVE-SITE-PAGE-BY-PAGE.md §0) swaps <body> on
// every internal link click instead of a full page load, so `DOMContentLoaded` above only
// ever fires once, for the very first page. Every navigation after that needs the same
// setup re-run against the freshly swapped-in header/nav — `livewire:navigated` fires after
// each one. The header itself isn't `@persist`-ed (it renders server-side `active` state
// per route), so this always finds a fresh, sentinel-less header to attach to — no
// duplicate observers accumulate across navigations.
document.addEventListener('livewire:navigated', init);

/**
 * `wire:navigate` unconditionally scrolls to the top of the new page (or restores a saved
 * scroll position for back/forward) — it has no handling at all for a URL hash fragment.
 * A hard page load, by contrast, always jumps straight to the matching `[id]`. The campaign
 * card's CTA relies on exactly that (`/campaigns/{slug}#donate`, landing the donor on the
 * donation form) — without this, adopting `wire:navigate` site-wide would have silently
 * broken it. Runs after Livewire's own scroll-to-top so it wins; `[id]`'s global
 * `scroll-margin-top` (tokens.css) still clears the sticky header exactly as it does for a
 * same-page anchor click.
 */
function scrollToHashAfterNavigate() {
    if (!location.hash) return;
    const target = document.getElementById(location.hash.slice(1));
    if (target) target.scrollIntoView({ behavior: 'instant' });
}

document.addEventListener('livewire:navigated', scrollToHashAfterNavigate);
