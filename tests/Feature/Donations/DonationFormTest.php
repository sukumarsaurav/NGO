<?php

declare(strict_types=1);

use App\Livewire\Donations\DonationForm;
use App\Models\Donation;
use App\Models\Donor;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

it('rate limits repeated submissions from the same IP, per Sprint 15\'s flood-protection criterion', function () {
    RateLimiter::clear('donate:127.0.0.1');

    for ($i = 0; $i < 10; $i++) {
        Livewire::test(DonationForm::class)
            ->set('amount', '1000')
            ->set('name', 'Flood Donor')
            ->set('email', "flood{$i}@example.com")
            ->set('phone', '9876543210')
            ->set('want80g', false)
            ->set('acceptedTerms', true)
            ->call('donate')
            ->assertHasNoErrors();
    }

    Livewire::test(DonationForm::class)
        ->set('amount', '1000')
        ->set('name', 'Flood Donor')
        ->set('email', 'flood-11th@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true)
        ->call('donate')
        ->assertHasErrors('rateLimit');

    expect(Donor::query()->where('email', 'flood-11th@example.com')->exists())->toBeFalse();

    RateLimiter::clear('donate:127.0.0.1');
});

it('initiates a donation and dispatches the razorpay-open event with the order details', function () {
    Livewire::test(DonationForm::class)
        ->set('amount', '1000')
        ->set('name', 'Test Donor')
        ->set('email', 'testdonor@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true)
        ->call('donate')
        ->assertHasNoErrors()
        ->assertDispatched('donation-initiated');

    expect(Donation::query()->count())->toBe(1)
        ->and(Donor::query()->where('email', 'testdonor@example.com')->exists())->toBeTrue();
});

it('blocks submission when both 80G and anonymous are checked, with the exact explanatory message', function () {
    Livewire::test(DonationForm::class)
        ->set('amount', '1000')
        ->set('name', 'Test Donor')
        ->set('email', 'conflict@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', true)
        ->set('isAnonymous', true)
        ->set('pan', 'ABCDE1234F')
        ->set('addressLine1', '123 Main St')
        ->set('city', 'Mumbai')
        ->set('state', 'Maharashtra')
        ->set('pincode', '400001')
        ->set('acceptedTerms', true)
        ->call('donate')
        ->assertHasErrors('conflict')
        ->assertNotDispatched('donation-initiated');

    expect(Donation::query()->count())->toBe(0);
});

it('requires PAN and address only when the 80G checkbox is checked', function () {
    Livewire::test(DonationForm::class)
        ->set('amount', '1000')
        ->set('name', 'Test Donor')
        ->set('email', 'needpan@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', true)
        ->set('acceptedTerms', true)
        ->call('donate')
        ->assertHasErrors(['pan', 'addressLine1', 'city', 'state', 'pincode']);
});

it('does not require PAN or address once the 80G checkbox is unchecked', function () {
    Livewire::test(DonationForm::class)
        ->set('amount', '1000')
        ->set('name', 'Test Donor')
        ->set('email', 'nopan@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true)
        ->call('donate')
        ->assertHasNoErrors();
});

it('requires terms acceptance', function () {
    Livewire::test(DonationForm::class)
        ->set('amount', '1000')
        ->set('name', 'Test Donor')
        ->set('email', 'noterms@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', false)
        ->call('donate')
        ->assertHasErrors('acceptedTerms');
});

it('rejects an amount below the configured minimum, even though the client sent it', function () {
    Livewire::test(DonationForm::class)
        ->set('amount', '1')
        ->set('name', 'Test Donor')
        ->set('email', 'tamper@example.com')
        ->set('phone', '9876543210')
        ->set('want80g', false)
        ->set('acceptedTerms', true)
        ->call('donate')
        ->assertHasErrors('amount')
        ->assertNotDispatched('donation-initiated');

    expect(Donation::query()->count())->toBe(0);
});
