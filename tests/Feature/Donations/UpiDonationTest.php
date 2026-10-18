<?php

declare(strict_types=1);

use App\Actions\Donations\ConfirmUpiDonation;
use App\Actions\Donations\RejectUpiDonation;
use App\Enums\DonationStatus;
use App\Events\DonationSucceeded;
use App\Filament\Admin\Resources\Donations\Pages\ListDonations;
use App\Livewire\Donations\DonationForm;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use App\Support\PaymentMethods;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

/*
 * Direct UPI: the donor pays from their own UPI app to the organisation's bank account, with no
 * gateway. It is a pending CLAIM until an admin matches it to the bank statement — nothing
 * about it may count, receipt or notify before that.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    app(SettingsRepository::class)->set('donation.upi_id', 'pos.5388436@indus');
    app(SettingsRepository::class)->set('org.legal_name', 'Vision Good Work Global Foundation');
});

/** Razorpay without keys, as on the live site today: only direct UPI is available. */
function asProductionWithoutRazorpay(): void
{
    app()->detectEnvironment(fn () => 'production');
    config(['services.razorpay.key' => null, 'services.razorpay.secret' => null]);
}

function fillUpiForm($component, string $amount = '500')
{
    return $component
        ->set('amount', $amount)
        ->set('name', 'Upi Donor')
        ->set('email', 'upi-donor@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true);
}

function pendingUpiDonation(array $overrides = []): Donation
{
    $donor = Donor::factory()->create();

    return Donation::factory()->for($donor)->create(array_merge([
        'amount' => 50000,
        'status' => 'pending',
        'payment_mode' => 'upi',
        'is_offline' => true,
        'donated_at' => null,
        'donation_number' => 'DN-2026-27-000123',
    ], $overrides));
}

// --- the link ------------------------------------------------------------------------------

it('builds a upi:// deep link with payee, amount, currency and note', function () {
    $link = PaymentMethods::upiLink(Money::fromRupees(500), 'DN-2026-27-000123');

    expect($link)->toBe(
        'upi://pay?pa=pos.5388436@indus'
        .'&pn=Vision%20Good%20Work%20Global%20Foundation'
        .'&am=500.00&cu=INR&tn=DN-2026-27-000123'
    );
});

it('keeps the @ literal in the payee address and encodes spaces as %20, not +', function () {
    $link = PaymentMethods::upiLink(Money::fromRupees(1), 'note');

    expect($link)->toContain('pa=pos.5388436@indus')->not->toContain('%40')->not->toContain('+');
});

it('returns no link when no UPI ID is configured', function () {
    app(SettingsRepository::class)->set('donation.upi_id', null);

    expect(PaymentMethods::upiLink(Money::fromRupees(1), 'x'))->toBeNull()
        ->and(PaymentMethods::upiEnabled())->toBeFalse();
});

// --- which methods are offered ---------------------------------------------------------------

it('hides Razorpay in production until its keys exist, and shows it once they do', function () {
    asProductionWithoutRazorpay();
    expect(PaymentMethods::razorpayEnabled())->toBeFalse();

    config(['services.razorpay.key' => 'rzp_live_x', 'services.razorpay.secret' => 'secret']);
    expect(PaymentMethods::razorpayEnabled())->toBeTrue();
});

it('defaults the form to UPI, with no monthly option, when Razorpay is not available', function () {
    asProductionWithoutRazorpay();

    Livewire::test(DonationForm::class)
        ->assertSet('paymentMethod', 'upi')
        ->assertDontSee('Make this a monthly donation')
        ->assertDontSee('Visa')
        ->assertSee('Google Pay');
});

it('offers a choice of methods only when both are available', function () {
    // Testing env: Razorpay (FakeGateway) and UPI are both on.
    Livewire::test(DonationForm::class)
        ->call('nextStep')
        ->assertSee('How would you like to pay?');

    asProductionWithoutRazorpay();

    Livewire::test(DonationForm::class)
        ->call('nextStep')
        ->assertDontSee('How would you like to pay?')
        ->assertSee('by UPI');
});

it('does not load the Razorpay script or list cards while Razorpay is off', function () {
    asProductionWithoutRazorpay();

    $html = $this->get('/donate')->assertOk()->getContent();

    expect($html)
        ->not->toContain('checkout.razorpay.com')
        ->not->toContain('Mastercard')
        ->toContain('UPI');
});

// --- the donor flow ----------------------------------------------------------------------------

it('creates a pending, gateway-free donation and opens the payment screen', function () {
    asProductionWithoutRazorpay();
    Event::fake([DonationSucceeded::class]);

    $component = fillUpiForm(Livewire::test(DonationForm::class))
        ->call('donateUpi')
        ->assertHasNoErrors()
        ->assertSet('step', 3)
        ->assertSee('Pay &#8377;500 by UPI', escape: false)
        ->assertSee('upi://pay?pa=pos.5388436@indus', escape: false);

    $donation = Donation::query()->latest('id')->first();

    expect($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->payment_mode->value)->toBe('upi')
        ->and($donation->is_offline)->toBeTrue()
        ->and($donation->source)->toBe('website_upi')
        ->and($donation->donation_number)->toStartWith('DN-')
        ->and($donation->amount)->toBe(50000)
        ->and($donation->transactions()->count())->toBe(0);

    // The payment note IS the donation number — it is how a bank-statement line is traced back.
    $component->assertSee('tn='.$donation->donation_number, escape: false);

    // A claim, not a donation: nothing counts and nothing fires until an admin confirms.
    Event::assertNotDispatched(DonationSucceeded::class);
    expect($donation->donor->fresh()->total_donated)->toBe(0);
});

it('refuses direct UPI when no UPI ID is configured', function () {
    app(SettingsRepository::class)->set('donation.upi_id', null);
    asProductionWithoutRazorpay();

    fillUpiForm(Livewire::test(DonationForm::class))
        ->call('donateUpi')
        ->assertHasErrors('payment')
        ->assertSet('step', 1);

    expect(Donation::query()->count())->toBe(0);
});

it('applies the same validation as the gateway path', function () {
    asProductionWithoutRazorpay();

    Livewire::test(DonationForm::class)
        ->set('amount', '500')
        ->call('donateUpi')
        ->assertHasErrors(['name', 'email', 'phone', 'acceptedTerms']);

    expect(Donation::query()->count())->toBe(0);
});

it('stores the 12-digit UPI reference the donor submits', function () {
    asProductionWithoutRazorpay();

    $component = fillUpiForm(Livewire::test(DonationForm::class))->call('donateUpi');

    $component
        ->set('upiReference', '4123 4567 8901')   // pasted with spaces
        ->call('submitUpiReference')
        ->assertHasNoErrors()
        ->assertSet('upiReferenceSubmitted', true)
        ->assertSee('noted your payment');

    expect(Donation::query()->latest('id')->first()->upi_reference)->toBe('412345678901');
});

it('rejects a UPI reference that is not exactly 12 digits', function (string $bad) {
    asProductionWithoutRazorpay();

    fillUpiForm(Livewire::test(DonationForm::class))
        ->call('donateUpi')
        ->set('upiReference', $bad)
        ->call('submitUpiReference')
        ->assertHasErrors('upiReference');

    expect(Donation::query()->latest('id')->first()->upi_reference)->toBeNull();
})->with(['', '12345', '1234567890123', 'ABCDEFGHIJKL', '12345678901a']);

it('will not let one bank transaction back two donations', function () {
    asProductionWithoutRazorpay();

    fillUpiForm(Livewire::test(DonationForm::class))
        ->call('donateUpi')
        ->set('upiReference', '412345678901')
        ->call('submitUpiReference')
        ->assertHasNoErrors();

    fillUpiForm(Livewire::test(DonationForm::class), '700')
        ->call('donateUpi')
        ->set('upiReference', '412345678901')
        ->call('submitUpiReference')
        ->assertHasErrors('upiReference');

    expect(Donation::query()->whereNotNull('upi_reference')->count())->toBe(1);
});

// --- admin confirmation ------------------------------------------------------------------------

it('counts a donation, fires the success event and credits the donor only on confirmation', function () {
    Event::fake([DonationSucceeded::class]);
    $donation = pendingUpiDonation(['upi_reference' => '412345678901']);
    $admin = User::factory()->create();

    $confirmed = app(ConfirmUpiDonation::class)->handle($donation, $admin->id);

    expect($confirmed->status)->toBe(DonationStatus::Succeeded)
        ->and($confirmed->donated_at)->not->toBeNull()
        ->and($confirmed->recorded_by_user_id)->toBe($admin->id)
        ->and($confirmed->donor->fresh()->total_donated)->toBe(50000)
        ->and($confirmed->donor->fresh()->donation_count)->toBe(1);

    Event::assertDispatchedTimes(DonationSucceeded::class, 1);
});

it('does not double-count when confirmed twice', function () {
    Event::fake([DonationSucceeded::class]);
    $donation = pendingUpiDonation();
    $admin = User::factory()->create();

    app(ConfirmUpiDonation::class)->handle($donation, $admin->id);
    app(ConfirmUpiDonation::class)->handle($donation, $admin->id);

    expect($donation->donor->fresh()->total_donated)->toBe(50000)
        ->and($donation->donor->fresh()->donation_count)->toBe(1);

    Event::assertDispatchedTimes(DonationSucceeded::class, 1);
});

it('refuses to confirm anything that is not a pending direct-UPI donation', function () {
    $admin = User::factory()->create();

    $gateway = Donation::factory()->for(Donor::factory())->create([
        'status' => 'pending', 'payment_mode' => 'card', 'is_offline' => false,
    ]);
    $cash = pendingUpiDonation(['payment_mode' => 'cash']);

    expect(fn () => app(ConfirmUpiDonation::class)->handle($gateway, $admin->id))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(ConfirmUpiDonation::class)->handle($cash, $admin->id))->toThrow(InvalidArgumentException::class);
});

it('cancels a rejected claim, frees its reference and leaves the donor uncredited', function () {
    $donation = pendingUpiDonation(['upi_reference' => '412345678901']);

    $rejected = app(RejectUpiDonation::class)->handle($donation, 'No credit on statement');

    expect($rejected->status)->toBe(DonationStatus::Cancelled)
        ->and($rejected->upi_reference)->toBeNull()
        ->and($rejected->notes)->toContain('412345678901')->toContain('No credit on statement')
        ->and($rejected->donor->fresh()->total_donated)->toBe(0);
});

// --- the admin panel -----------------------------------------------------------------------

it('lets an admin confirm a pending UPI donation from the donations list', function () {
    Event::fake([DonationSucceeded::class]);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $donation = pendingUpiDonation(['upi_reference' => '412345678901']);

    Livewire::actingAs($admin)
        ->test(ListDonations::class)
        ->assertTableActionVisible('confirmUpi', $donation)
        ->callTableAction('confirmUpi', $donation);

    expect($donation->fresh()->status)->toBe(DonationStatus::Succeeded);
});

it('does not offer confirm or reject on a gateway donation or an already-confirmed one', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $gateway = Donation::factory()->for(Donor::factory())->create([
        'status' => 'pending', 'payment_mode' => 'card', 'is_offline' => false,
    ]);
    $done = pendingUpiDonation(['status' => 'succeeded']);

    Livewire::actingAs($admin)
        ->test(ListDonations::class)
        ->assertTableActionHidden('confirmUpi', $gateway)
        ->assertTableActionHidden('rejectUpi', $gateway)
        ->assertTableActionHidden('confirmUpi', $done);
});

it('keeps a view-only manager from confirming payments', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $donation = pendingUpiDonation();

    expect($manager->can('reviewUpiPayment', $donation))->toBeFalse();
});
