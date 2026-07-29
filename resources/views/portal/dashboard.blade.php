<x-layout.portal title="Dashboard">
    <h1 class="mb-6 text-2xl font-bold text-content">
        Welcome, {{ auth()->user()->name }}
    </h1>

    @if ($member)
        <div class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-content-muted">Member code</dt>
                    <dd class="font-semibold text-content">{{ $member->member_code }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Status</dt>
                    <dd class="font-semibold text-content">{{ $member->status->label() }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Department</dt>
                    <dd class="font-semibold text-content">{{ $member->department?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-content-muted">Designation</dt>
                    <dd class="font-semibold text-content">{{ $member->designation?->title ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    @else
        <p class="text-content-muted">No member profile is linked to this account yet.</p>
    @endif

    <a href="{{ route('portal.profile.edit') }}" class="mt-6 inline-block font-semibold text-link hover:text-link-hover">
        Edit your profile &rarr;
    </a>
</x-layout.portal>
