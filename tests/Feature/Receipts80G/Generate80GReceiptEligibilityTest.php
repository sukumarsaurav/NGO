<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\Generate80GReceipt;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Settings\SettingsRepository;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

function succeededDonationWithDonorDetails(array $donorOverrides = [], array $donationOverrides = [])
{
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Eighty G Donor',
        donorEmail: 'eightyg-'.uniqid().'@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );

    $initiation->donation->donor->update(array_merge([
        'pan' => 'ABCDE1234F',
        'address_line1' => '221B Baker Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ], $donorOverrides));

    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));

    if ($donationOverrides) {
        $donation->update($donationOverrides);
        $donation = $donation->fresh();
    }

    return $donation;
}

it('issues an 80G receipt when the donation and donor are fully eligible', function () {
    $donation = succeededDonationWithDonorDetails();

    $receipt = app(Generate80GReceipt::class)->handle($donation);

    expect($receipt->series->value)->toBe('80g')
        ->and($receipt->receipt_number)->toContain('80G')
        ->and($receipt->snapshot_data['donor_pan'])->toBe('ABCDE1234F');
});

it('refuses when the donation has not succeeded', function () {
    $donation = succeededDonationWithDonorDetails();
    $donation->update(['status' => 'refunded']);

    app(Generate80GReceipt::class)->handle($donation->fresh());
})->throws(InvalidArgumentException::class, 'Donation not completed.');

it('refuses when the donation itself is not eligible for 80G (e.g. cash over ₹2,000)', function () {
    $donation = succeededDonationWithDonorDetails(donationOverrides: ['eligible_for_80g' => false]);

    app(Generate80GReceipt::class)->handle($donation);
})->throws(InvalidArgumentException::class, 'not eligible for 80G');

it('refuses when the donor has no PAN', function () {
    $donation = succeededDonationWithDonorDetails(['pan' => null]);

    app(Generate80GReceipt::class)->handle($donation);
})->throws(InvalidArgumentException::class, 'PAN is required');

it('refuses when the donor address is incomplete', function () {
    $donation = succeededDonationWithDonorDetails(['city' => null]);

    app(Generate80GReceipt::class)->handle($donation);
})->throws(InvalidArgumentException::class, 'Donor address is required for an 80G receipt.');

it('refuses when the NGO 80G registration has expired', function () {
    app(SettingsRepository::class)->set('org.80g_valid_to', now()->subDay()->toDateString());

    $donation = succeededDonationWithDonorDetails();

    app(Generate80GReceipt::class)->handle($donation);
})->throws(InvalidArgumentException::class, "NGO's 80G registration has expired");

it('refuses when the donation date falls outside the 80G validity window', function () {
    app(SettingsRepository::class)->set('org.80g_valid_from', now()->addDays(5)->toDateString());

    $donation = succeededDonationWithDonorDetails();

    app(Generate80GReceipt::class)->handle($donation);
})->throws(InvalidArgumentException::class, 'validity period');

it('returns the existing receipt instead of throwing when one already exists', function () {
    $donation = succeededDonationWithDonorDetails();

    $first = app(Generate80GReceipt::class)->handle($donation);
    $second = app(Generate80GReceipt::class)->handle($donation->fresh());

    expect($second->id)->toBe($first->id);
});

it('exposes the ineligibility reason for the UI instead of throwing', function () {
    $donation = succeededDonationWithDonorDetails(['pan' => null]);

    $reason = app(Generate80GReceipt::class)->ineligibilityReason($donation);

    expect($reason)->toContain('PAN is required');
});

it('returns null from ineligibilityReason when eligible', function () {
    $donation = succeededDonationWithDonorDetails();

    expect(app(Generate80GReceipt::class)->ineligibilityReason($donation))->toBeNull();
});
