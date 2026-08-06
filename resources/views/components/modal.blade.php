{{--
    Modal — centred dialog with a scrim, backing panel and focus trap.

    Extracted from the mobile drawer's already-correct pattern
    (components/layout/public.blade.php's `#mobile-menu`), not written from scratch, so this
    component starts from behaviour that is already known to work rather than adding a third
    modal implementation to the codebase. Before this component existed there were two: the
    drawer (correct) and the campaign page's NGO-credentials dialog (no focus trap, no
    scroll lock, and layered at a raw `z-50` — *below* the header's `z-header` (200), so the
    header rendered on top of the "modal" scrim). See
    docs/11-UI-UX-AUDIT-HOME-CAMPAIGNS.md §1.3 and docs/12-REMEDIATION-PLAN-HOME-CAMPAIGNS.md
    PR 1.3 / D3.

    `x-trap.noscroll="$name"` holds focus inside the panel while open, locks body scroll, and
    returns focus to the trigger on close — none of which the dialog it replaces had.

    The caller owns the boolean Alpine property named by `name` (declared on an ancestor
    `x-data`), matching the drawer's `drawerOpen` convention. Usage:

        <div x-data="{ showNgoModal: false }">
            <button type="button" @click="showNgoModal = true">Open</button>
            <x-modal name="showNgoModal" title="NGO Legal Verification">
                …body…
            </x-modal>
        </div>
--}}
@props(['name', 'title', 'maxWidth' => 'max-w-md'])

<div
    x-show="{{ $name }}"
    x-cloak
    x-trap.noscroll="{{ $name }}"
    @keydown.escape.window="{{ $name }} = false"
    x-transition:enter="transition-opacity duration-base ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity duration-fast ease-in-out"
    x-transition:leave-end="opacity-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $name }}-title"
    class="fixed inset-0 z-modal flex items-center justify-center bg-scrim p-4"
>
    <div @click.outside="{{ $name }} = false" class="w-full {{ $maxWidth }} rounded-lg border border-line-divider bg-surface p-6 shadow-lg">
        <div class="flex items-start justify-between gap-4 border-b border-line-divider pb-3">
            <h3 id="{{ $name }}-title" class="font-heading text-lg font-bold text-content">{{ $title }}</h3>
            <button
                type="button"
                @click="{{ $name }} = false"
                aria-label="Close"
                class="-mr-2 -mt-2 inline-flex min-h-touch min-w-touch shrink-0 items-center justify-center rounded-md text-content-muted transition-colors duration-fast hover:bg-surface-muted hover:text-content"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <div class="pt-4">
            {{ $slot }}
        </div>
    </div>
</div>
