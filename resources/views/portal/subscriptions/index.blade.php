<x-layout.portal title="My monthly donations">
    <h1 class="mb-6 text-2xl font-bold text-content">My monthly donations</h1>

    @if (session('status'))
        <div class="mb-6 rounded-md bg-success-bg p-3 text-sm text-success-text">
            {{ session('status') }}
        </div>
    @endif

    @if ($subscriptions->isEmpty())
        <x-empty-state title="No monthly donations">
            Monthly giving spreads your support across the year and can be cancelled at any time.
            <x-slot:action>
                <x-button :href="route('campaigns.monthly-giving')">Set up monthly giving</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach ($subscriptions as $subscription)
                <div class="rounded-lg border border-line-divider bg-surface p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold text-content">
                                &#8377;{{ number_format($subscription->amount / 100, 2) }} / {{ $subscription->interval->label() }}
                            </p>
                            <p class="text-sm text-content-muted">
                                {{ $subscription->completed_cycles }} cycle(s) completed &middot;
                                &#8377;{{ number_format($subscription->total_collected / 100, 2) }} given so far
                            </p>
                        </div>
                        <x-badge :variant="$subscription->status->value === 'active' ? 'success' : ($subscription->status->value === 'halted' ? 'danger' : 'neutral')">
                            {{ $subscription->status->label() }}
                        </x-badge>
                    </div>

                    @if ($subscription->status->value === 'active' && $subscription->next_charge_at)
                        <p class="mb-4 text-sm text-content-muted">Next charge: {{ $subscription->next_charge_at->format('d M Y') }}</p>
                    @endif

                    <div class="flex gap-3">
                        @if ($subscription->status->value === 'active')
                            <form method="POST" action="{{ route('portal.subscriptions.pause', $subscription) }}">
                                @csrf
                                <x-button variant="secondary">Pause</x-button>
                            </form>
                        @endif
                        @if ($subscription->status->value === 'paused')
                            <form method="POST" action="{{ route('portal.subscriptions.resume', $subscription) }}">
                                @csrf
                                <x-button>Resume</x-button>
                            </form>
                        @endif
                        @if (in_array($subscription->status->value, ['active', 'paused', 'halted']))
                            <form method="POST" action="{{ route('portal.subscriptions.cancel', $subscription) }}" onsubmit="return confirm('Cancel this monthly donation? This can\'t be undone.');">
                                @csrf
                                <x-button variant="danger">Cancel</x-button>
                            </form>
                        @endif
                    </div>

                    @if ($subscription->charges->isNotEmpty())
                        <details class="mt-4">
                            <summary class="cursor-pointer text-sm font-semibold text-link">Charge history</summary>
                            <table class="mt-2 min-w-full text-sm">
                                <tbody class="divide-y divide-line-divider">
                                    @foreach ($subscription->charges->sortByDesc('cycle_number') as $charge)
                                        <tr>
                                            <td class="py-2 text-content-muted">Cycle {{ $charge->cycle_number }}</td>
                                            <td class="py-2 text-content">&#8377;{{ number_format($charge->amount / 100, 2) }}</td>
                                            <td class="py-2">
                                                <span class="{{ $charge->status->value === 'succeeded' ? 'text-success-text' : 'text-danger-text' }}">
                                                    {{ $charge->status->label() }}
                                                </span>
                                            </td>
                                            <td class="py-2 text-content-muted">{{ $charge->charged_at?->format('d M Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-layout.portal>
