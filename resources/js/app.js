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
