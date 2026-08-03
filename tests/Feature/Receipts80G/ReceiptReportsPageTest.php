<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Filament\Admin\Pages\ReceiptReports;
use App\Models\User;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');

    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Reports Page Donor',
        donorEmail: 'reportspage@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_reportspage_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));
    app(GenerateDonationReceipt::class)->handle($donation);
});

it('loads the receipt reports page without error', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiptReports::class)
        ->assertSuccessful();
});

it('exposes register, gaps, and missingPan without throwing', function () {
    $component = Livewire::actingAs($this->admin)->test(ReceiptReports::class);

    expect(fn () => $component->instance()->register())->not->toThrow(Throwable::class);
    expect(fn () => $component->instance()->gaps())->not->toThrow(Throwable::class);
    expect(fn () => $component->instance()->missingPan())->not->toThrow(Throwable::class);
});

it('denies access to a user without the view_receipts permission', function () {
    $regular = User::factory()->create();

    expect(ReceiptReports::canAccess())->toBeFalse();

    $this->actingAs($regular);
    expect(ReceiptReports::canAccess())->toBeFalse();
});
