<?php

declare(strict_types=1);

use App\Filament\Admin\Widgets\AttentionNeededWidget;
use App\Filament\Admin\Widgets\CampaignProgressWidget;
use App\Filament\Admin\Widgets\DonationStatsWidget;
use App\Filament\Admin\Widgets\MemberGrowthWidget;
use App\Filament\Admin\Widgets\MonthlyTrendWidget;
use App\Filament\Admin\Widgets\ReceiptSummaryWidget;
use App\Filament\Admin\Widgets\RecentDonationsWidget;
use App\Models\Donation;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Reports\AttentionNeededCounts;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

it('renders every dashboard widget without error', function (string $widget) {
    Livewire::test($widget)->assertSuccessful();
})->with([
    DonationStatsWidget::class,
    MonthlyTrendWidget::class,
    CampaignProgressWidget::class,
    RecentDonationsWidget::class,
    MemberGrowthWidget::class,
    AttentionNeededWidget::class,
    ReceiptSummaryWidget::class,
]);

it('reports today\'s donation total matching a raw DB sum', function () {
    Donation::factory()->create(['status' => 'succeeded', 'donated_at' => now(), 'amount' => 150000]);

    $rawToday = (int) DB::table('donations')
        ->where('status', 'succeeded')
        ->whereBetween('donated_at', [now()->startOfDay(), now()->endOfDay()])
        ->sum('amount');

    expect($rawToday)->toBe(150000);

    Livewire::test(DonationStatsWidget::class)
        ->assertSee('₹'.number_format($rawToday / 100, 2));
});

it('flags a halted subscription in the attention needed widget', function () {
    Subscription::factory()->create(['status' => 'halted']);

    $counts = app(AttentionNeededCounts::class)->get();

    expect($counts['halted_subscriptions'])->toBeGreaterThanOrEqual(1);
});
