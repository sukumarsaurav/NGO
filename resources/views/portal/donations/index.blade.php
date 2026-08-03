<x-layout.portal title="My donations">
    <h1 class="mb-6 text-2xl font-bold text-content">My donations</h1>

    @if (session('status'))
        <div class="mb-6 rounded-md bg-success-bg p-3 text-sm text-success-text">
            {{ session('status') }}
        </div>
    @endif

    @if ($donor && ! $donor->pan)
        <div class="mb-6 rounded-lg border border-warning bg-warning-bg p-4">
            <p class="mb-1 text-sm font-semibold text-warning-text">Add your PAN to get 80G tax-exemption receipts</p>
            <p class="mb-3 text-xs text-warning-text">
                Without a PAN we can only send an acknowledgement receipt — not the certificate you can use for
                your tax deduction. Add it once and we'll generate 80G receipts for this year's and last year's
                eligible donations automatically.
            </p>
            <form method="POST" action="{{ route('portal.donations.pan') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                {{-- These were placeholder-only fields. §10.2: "A field whose only label is
                     its placeholder loses its label the moment the user types." --}}
                <x-form.field class="sm:col-span-2" name="pan" label="PAN" required class="uppercase" />
                <x-form.field class="sm:col-span-2" name="address_line1" label="Address" required autocomplete="address-line1" />
                <x-form.field class="sm:col-span-2" name="address_line2" label="Address line 2" hint="Optional" autocomplete="address-line2" />
                <x-form.field name="city" label="City" required autocomplete="address-level2" />
                <x-form.field name="state" label="State" required autocomplete="address-level1" />
                <x-form.field class="sm:col-span-2" name="pincode" label="Pincode" required autocomplete="postal-code" inputmode="numeric" />
                <x-button class="sm:col-span-2">
                    Save and generate 80G receipts
                </x-button>
            </form>
        </div>
    @endif

    @if ($donations->isEmpty())
        <x-empty-state title="No donations yet">
            When you give, your donations and receipts will appear here.
            <x-slot:action>
                <x-button :href="route('campaigns.index')">Explore campaigns</x-button>
            </x-slot:action>
        </x-empty-state>
    @else
        @php $financialYears = $donations->pluck('financial_year')->unique()->sort()->reverse(); @endphp
        @if ($financialYears->isNotEmpty())
            <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                <span class="text-content-muted">Annual statement:</span>
                @foreach ($financialYears as $financialYear)
                    <a href="{{ route('portal.donations.annual-statement', $financialYear) }}" class="rounded-full border border-line px-3 py-1 font-semibold text-link hover:text-link-hover">
                        FY {{ $financialYear }}
                    </a>
                @endforeach
            </div>
        @endif
        <div class="overflow-hidden rounded-lg border border-line-divider bg-surface shadow-sm">
            <table class="min-w-full divide-y divide-line-divider">
                <thead>
                    <tr class="text-left text-sm text-content-muted">
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3">Receipts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line-divider">
                    @foreach ($donations as $donation)
                        <tr>
                            <td class="px-4 py-3 text-content-muted">{{ ($donation->donated_at ?? $donation->created_at)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-content">&#8377;{{ number_format($donation->amount / 100, 2) }}</td>
                            <td class="px-4 py-3">
                                <x-badge :variant="$donation->status->value === 'succeeded' ? 'success' : 'neutral'">
                                    {{ $donation->status->label() }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @forelse ($donation->receipts as $receipt)
                                    @if ($receipt->file_path)
                                        <a href="{{ route('portal.donations.receipt', $receipt) }}" class="mr-2 font-semibold text-link hover:text-link-hover">
                                            {{ $receipt->series->value === '80g' ? '80G receipt' : 'Receipt' }}
                                        </a>
                                    @endif
                                @empty
                                    <span class="text-content-muted">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layout.portal>
