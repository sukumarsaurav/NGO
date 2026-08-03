@if ($campaigns->isNotEmpty())
    <section class="w-full">
        <h2 class="mb-4 text-2xl font-bold text-content">Featured Campaigns</h2>
        <div class="flex gap-4 overflow-x-auto pb-2">
            @foreach ($campaigns as $campaign)
                <div class="w-[18rem] flex-shrink-0">
                    <x-campaigns.card :campaign="$campaign" />
                </div>
            @endforeach
        </div>
    </section>
@endif
