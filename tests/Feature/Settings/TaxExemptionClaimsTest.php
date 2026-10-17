<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\Generate80GReceipt;
use App\Livewire\Donations\DonationForm;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use App\Support\TaxExemption;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Livewire\Livewire;

/*
 * A new Section 8 company is not automatically 12A/80G registered. Every tax claim on the
 * public site, and the machinery that issues 80G certificates, must follow the
 * registration numbers an admin has actually entered — and return on their own when those
 * numbers are added. See App\Support\TaxExemption.
 */

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

function setOrg(string $key, ?string $value): void
{
    app(SettingsRepository::class)->set($key, $value);
}

it('treats the organisation as not registered when no numbers are set', function () {
    expect(TaxExemption::is80gActive())->toBeFalse()
        ->and(TaxExemption::is12aRegistered())->toBeFalse();
});

it('treats 80G as active only with a number and an unexpired window', function () {
    setOrg('org.80g_number', 'AB/80G/1');
    expect(TaxExemption::is80gActive())->toBeTrue();

    setOrg('org.80g_valid_to', now()->addYear()->toDateString());
    expect(TaxExemption::is80gActive())->toBeTrue();

    setOrg('org.80g_valid_to', now()->subDay()->toDateString());
    expect(TaxExemption::is80gActive())->toBeFalse();
});

it('does not make tax claims on the donate page before registration', function () {
    $html = $this->get('/donate')->assertOk()->getContent();

    expect($html)
        ->not->toContain('12A &amp; 80G Registered')
        ->not->toContain('80G tax-exemption receipt')
        ->not->toContain('80G Tax Savings')
        ->toContain('Registered Section 8 Company');
});

it('restores the tax claims on the donate page once 80G and 12A numbers are entered', function () {
    setOrg('org.80g_number', 'AB/80G/1');
    setOrg('org.12a_number', 'AB/12A/1');

    $html = $this->get('/donate')->assertOk()->getContent();

    expect($html)
        ->toContain('12A &amp; 80G Registered')
        ->toContain('80G tax-exemption receipt')
        ->toContain('80G Tax Savings');
});

it('hides the 80G checkbox, PAN block and 80G footer line in the form before registration', function () {
    Livewire::test(DonationForm::class)
        ->assertSet('want80g', false)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertDontSee('I want an 80G')
        ->assertDontSee('80G eligible');
});

it('offers the 80G checkbox in the form once an active registration exists', function () {
    setOrg('org.80g_number', 'AB/80G/1');

    Livewire::test(DonationForm::class)
        ->assertSet('want80g', true)
        ->call('nextStep')
        ->assertSee('I want an 80G')
        ->assertSee('80G eligible');
});

it('refuses to issue an 80G certificate when no 80G number is on file', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Donor',
        donorEmail: 'donor-'.uniqid().'@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    $initiation->donation->donor->update([
        'pan' => 'ABCDE1234F', 'address_line1' => '1 Road', 'city' => 'Lucknow',
        'state' => 'Uttar Pradesh', 'pincode' => '226010',
    ]);
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));

    expect(app(Generate80GReceipt::class)->ineligibilityReason($donation))
        ->toContain('no 80G registration number');

    setOrg('org.80g_number', 'AB/80G/1');

    expect(app(Generate80GReceipt::class)->ineligibilityReason($donation))->toBeNull();
});

it('hides the UPI panel until a UPI ID is set, then shows it', function () {
    $this->get('/donate')->assertOk()->assertDontSee('Prefer to pay directly by UPI');

    setOrg('donation.upi_id', 'pos.5388436@indus');

    $this->get('/donate')->assertOk()
        ->assertSee('Prefer to pay directly by UPI')
        ->assertSee('pos.5388436@indus')
        ->assertSee('images/payments/upi-qr.jpg');
});
