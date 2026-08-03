/**
 * Vision Good Work Global Foundation — Tailwind configuration
 *
 * Every value here maps to a CSS custom property in resources/css/tokens.css.
 * Nothing is defined twice: this file gives Tailwind names to tokens, it does not
 * introduce values. Documented in docs/08-DESIGN-SYSTEM.md.
 *
 * The scales below REPLACE Tailwind's defaults rather than extending them. That is
 * deliberate — if `bg-emerald-400`, `z-50` and `rounded-3xl` remain available, they
 * will be used, and the system stops being a system. Utilities that do not exist
 * cannot drift.
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',
        './vendor/filament/**/*.blade.php',
    ],

    // Dark mode is OFF by decision — see docs/08-DESIGN-SYSTEM.md §11.
    // Filament v5 ships it enabled; both panel providers call ->darkMode(false).
    darkMode: 'selector',

    theme: {
        // --- COLOURS -----------------------------------------------------------------
        // Semantic names first. Blade uses `bg-action`, never `bg-brand-500`.
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            white: 'var(--neutral-0)',

            // Semantic — use these
            background: 'var(--color-background)',
            surface: {
                DEFAULT: 'var(--color-surface)',
                muted: 'var(--color-surface-muted)',
            },
            content: {
                DEFAULT: 'var(--color-text)',
                muted: 'var(--color-text-muted)',
                placeholder: 'var(--color-text-placeholder)',
                disabled: 'var(--color-text-disabled)',
                inverse: 'var(--color-text-inverse)',
            },
            line: {
                DEFAULT: 'var(--color-border)',   // control edges — 3.71:1
                divider: 'var(--color-divider)',  // decorative only — 1.41:1
            },
            action: {
                DEFAULT: 'var(--color-action)',
                hover: 'var(--color-action-hover)',
                active: 'var(--color-action-active)',
                on: 'var(--color-on-action)',
            },
            link: {
                DEFAULT: 'var(--color-link)',
                hover: 'var(--color-link-hover)',
            },
            // §10.2 wants a focused field's *border* to move to the focus colour, on top of
            // the ring tokens.css draws. The token existed; Tailwind had no name for it.
            focus: 'var(--color-focus)',
            trust: {
                DEFAULT: 'var(--color-trust-bg)',
                text: 'var(--color-trust-text)',
            },
            highlight: {
                DEFAULT: 'var(--color-highlight)',
                on: 'var(--color-on-highlight)',  // dark, never white
            },
            // Status. `DEFAULT` is the solid colour (icon, border, filled button);
            // `bg` + `text` are the measured tint pair used by the Alert component
            // and the urgent badge. Never pair a tint bg with the solid text colour
            // without measuring it.
            danger:  { DEFAULT: 'var(--color-danger)',  bg: 'var(--color-danger-bg)',  text: 'var(--color-danger-text)'  },
            success: { DEFAULT: 'var(--color-success)', bg: 'var(--color-success-bg)', text: 'var(--color-success-text)' },
            warning: { DEFAULT: 'var(--color-warning)', bg: 'var(--color-warning-bg)', text: 'var(--color-warning-text)' },
            info:    { DEFAULT: 'var(--color-info)',    bg: 'var(--color-info-bg)',    text: 'var(--color-info-text)'    },
            alert:   { DEFAULT: 'var(--color-alert)',   bg: 'var(--color-alert-bg)',   text: 'var(--color-alert-text)'   },
            scrim: 'var(--color-scrim)',

            // Primitive ramps — for charts and illustrations that need a specific step.
            // Reaching for these in a component template is a code-review finding.
            brand: {
                50: 'var(--brand-50)',
                100: 'var(--brand-100)',
                200: 'var(--brand-200)',
                300: 'var(--brand-300)',
                400: 'var(--brand-400)',
                500: 'var(--brand-500)',
                600: 'var(--brand-600)',
                700: 'var(--brand-700)',
                800: 'var(--brand-800)',
                900: 'var(--brand-900)',
            },
            accent: {
                50: 'var(--accent-50)',
                100: 'var(--accent-100)',
                200: 'var(--accent-200)',
                300: 'var(--accent-300)',
                400: 'var(--accent-400)',
                500: 'var(--accent-500)',
                600: 'var(--accent-600)', // NO TEXT ON THIS — 3.88 white / 3.98 dark
                700: 'var(--accent-700)',
                800: 'var(--accent-800)',
                900: 'var(--accent-900)',
            },
        },

        // --- SPACE -------------------------------------------------------------------
        spacing: {
            0: '0',
            px: '1px',
            1: 'var(--space-1)',
            2: 'var(--space-2)',
            3: 'var(--space-3)',
            4: 'var(--space-4)',
            6: 'var(--space-6)',
            8: 'var(--space-8)',
            12: 'var(--space-12)',
            16: 'var(--space-16)',
            24: 'var(--space-24)',
        },

        // --- TYPE --------------------------------------------------------------------
        fontFamily: {
            sans: 'var(--font-sans)',
            deva: 'var(--font-deva)',
        },
        fontSize: {
            xs:   ['var(--text-xs)',   { lineHeight: 'var(--leading-xs)' }],
            sm:   ['var(--text-sm)',   { lineHeight: 'var(--leading-sm)' }],
            base: ['var(--text-base)', { lineHeight: 'var(--leading-base)' }],
            lg:   ['var(--text-lg)',   { lineHeight: 'var(--leading-lg)' }],
            xl:   ['var(--text-xl)',   { lineHeight: 'var(--leading-xl)' }],
            '2xl':['var(--text-2xl)',  { lineHeight: 'var(--leading-2xl)' }],
            '3xl':['var(--text-3xl)',  { lineHeight: 'var(--leading-3xl)' }],
            '4xl':['var(--text-4xl)',  { lineHeight: 'var(--leading-4xl)' }],
            '5xl':['var(--text-5xl)',  { lineHeight: 'var(--leading-5xl)' }],
        },

        // --- SHAPE & DEPTH -----------------------------------------------------------
        borderRadius: {
            none: '0',
            sm: 'var(--radius-sm)',
            md: 'var(--radius-md)',
            lg: 'var(--radius-lg)',
            full: 'var(--radius-full)',
        },
        boxShadow: {
            none: 'none',
            sm: 'var(--shadow-sm)',
            md: 'var(--shadow-md)',
            lg: 'var(--shadow-lg)',
        },

        // --- LAYERS ------------------------------------------------------------------
        // Named layers only. `z-50` does not exist here, on purpose.
        zIndex: {
            base: 'var(--z-base)',
            'sticky-nav': 'var(--z-sticky-nav)',
            header: 'var(--z-header)',
            'donate-bar': 'var(--z-donate-bar)',
            float: 'var(--z-float)',
            drawer: 'var(--z-drawer)',
            modal: 'var(--z-modal)',
            toast: 'var(--z-toast)',
        },

        // --- MOTION ------------------------------------------------------------------
        transitionDuration: {
            fast: 'var(--duration-fast)',
            base: 'var(--duration-base)',
            slow: 'var(--duration-slow)',
        },
        transitionTimingFunction: {
            out: 'var(--ease-out)',
            'in-out': 'var(--ease-in-out)',
        },

        // --- BREAKPOINTS -------------------------------------------------------------
        // Mobile-first, designed at 360px. There is no `xs`: below `sm` IS the base case.
        screens: {
            sm: '640px',
            md: '768px',
            lg: '1024px',
            xl: '1280px',
        },

        extend: {
            maxWidth: {
                container: 'var(--container-max)',
                prose: '68ch',
            },
            minHeight: {
                touch: '44px', // minimum touch target on public surfaces
            },
            minWidth: {
                touch: '44px',
            },
            aspectRatio: {
                card: '4 / 3',   // campaign card cover
                hero: '16 / 9',  // campaign hero
                og: '1200 / 630',// social preview — exact, do not round
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
