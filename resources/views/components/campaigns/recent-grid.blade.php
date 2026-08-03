@if ($campaigns->isNotEmpty())
    <section class="w-full">
        <h2 class="mb-4 text-2xl font-bold text-content">Recent Campaigns</h2>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($campaigns as $campaign)
                <x-campaigns.card :campaign="$campaign" />
            @endforeach
        </div>
    </section>
@endif
