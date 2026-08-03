<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Filament\Admin\Resources\Receipts\Pages\ListReceipts;
use App\Filament\Admin\Resources\Receipts\Pages\ViewReceipt;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    Mail::fake();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Resource Test Donor',
        donorEmail: 'resourcetest@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    $this->donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_resource_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));
    $this->receipt = app(GenerateDonationReceipt::class)->handle($this->donation);
});

it('lists receipts', function () {
    Livewire::actingAs($this->admin)
        ->test(ListReceipts::class)
        ->assertSuccessful();
});

it('views a receipt', function () {
    Livewire::actingAs($this->admin)
        ->test(ViewReceipt::class, ['record' => $this->receipt->getRouteKey()])
        ->assertSuccessful();
});

it('cancels a receipt from the admin table action', function () {
    Livewire::actingAs($this->admin)
        ->test(ListReceipts::class)
        ->callTableAction('cancel', $this->receipt, data: ['reason' => 'Test cancellation from admin'])
        ->assertHasNoTableActionErrors();

    expect($this->receipt->fresh()->is_cancelled)->toBeTrue();
});

it('reissues a receipt from the admin table action', function () {
    Livewire::actingAs($this->admin)
        ->test(ListReceipts::class)
        ->callTableAction('reissue', $this->receipt, data: ['reason' => 'Test reissue from admin'])
        ->assertHasNoTableActionErrors();

    expect(Receipt::query()->where('donation_id', $this->donation->id)->count())->toBe(2);
});
