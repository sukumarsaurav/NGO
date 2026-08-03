<?php

declare(strict_types=1);

use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Actions\Receipts\GenerateDonationReceipt;
use App\Models\Receipt;
use App\Models\User;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Queue::fake();
    Storage::fake('local');
});

it('links a donor to a logged-in user by matching email and shows their donation history', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Portal Donor',
        donorEmail: 'portaldonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(750),
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_portal_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(750),
    ));

    $user = User::factory()->create(['email' => 'portaldonor@example.com']);

    $response = $this->actingAs($user)->get(route('portal.donations.index'));

    $response->assertOk()->assertSeeText('750.00');

    expect($initiation->donation->fresh()->donor->fresh()->user_id)->toBe($user->id);
});

it('lets a donor download their own receipt', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Receipt Portal Donor',
        donorEmail: 'receiptportal@example.com',
        donorPhone: null,
        amount: Money::fromRupees(750),
    );
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_portal_2',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(750),
    ));
    $receipt = app(GenerateDonationReceipt::class)->handle($donation);
    Storage::disk('local')->put($receipt->file_path = "receipts/{$receipt->uuid}.pdf", '%PDF-1.7 fake');
    $receipt->save();

    $user = User::factory()->create(['email' => 'receiptportal@example.com']);

    $response = $this->actingAs($user)->get(route('portal.donations.receipt', $receipt));

    $response->assertOk();
    expect($receipt->fresh()->download_count)->toBe(1);
});

it('retroactively issues an 80G receipt once a donor submits PAN and address via the portal', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'PAN Donor',
        donorEmail: 'pandonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_pan_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));
    app(GenerateDonationReceipt::class)->handle($donation);

    expect(Receipt::query()->where('donation_id', $donation->id)->where('series', '80g')->exists())->toBeFalse();

    $user = User::factory()->create(['email' => 'pandonor@example.com']);

    $response = $this->actingAs($user)->post(route('portal.donations.pan'), [
        'pan' => 'ABCDE1234F',
        'address_line1' => '221B Baker Street',
        'address_line2' => null,
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);

    $response->assertRedirect();
    expect($donation->donor->fresh()->pan)->toBe('ABCDE1234F')
        ->and(Receipt::query()->where('donation_id', $donation->id)->where('series', '80g')->exists())->toBeTrue();
});

it('rejects an invalid PAN format from the portal PAN form', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Bad PAN Donor',
        donorEmail: 'badpandonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(1000),
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_pan_2',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(1000),
    ));

    $user = User::factory()->create(['email' => 'badpandonor@example.com']);

    $response = $this->actingAs($user)->post(route('portal.donations.pan'), [
        'pan' => 'NOT-A-PAN',
        'address_line1' => '221B Baker Street',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
    ]);

    $response->assertSessionHasErrors('pan');
});

it('downloads an annual consolidated statement for a donor\'s financial year', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Statement Donor',
        donorEmail: 'statementdonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_statement_1',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));
    app(GenerateDonationReceipt::class)->handle($donation);

    $user = User::factory()->create(['email' => 'statementdonor@example.com']);
    $financialYear = $donation->fresh()->financial_year;

    $response = $this->actingAs($user)->get(route('portal.donations.annual-statement', $financialYear));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('downloads an empty annual statement for a financial year with no receipts', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Empty Year Donor',
        donorEmail: 'emptyyeardonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(500),
    );
    app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_statement_2',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));

    $user = User::factory()->create(['email' => 'emptyyeardonor@example.com']);

    $response = $this->actingAs($user)->get(route('portal.donations.annual-statement', '2019-20'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('forbids downloading another donor\'s receipt', function () {
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Owner Donor',
        donorEmail: 'ownerdonor@example.com',
        donorPhone: null,
        amount: Money::fromRupees(750),
    );
    $donation = app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_portal_3',
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(750),
    ));
    $receipt = app(GenerateDonationReceipt::class)->handle($donation);
    Storage::disk('local')->put($receipt->file_path = "receipts/{$receipt->uuid}.pdf", '%PDF-1.7 fake');
    $receipt->save();

    $intruder = User::factory()->create(['email' => 'intruder@example.com']);

    $response = $this->actingAs($intruder)->get(route('portal.donations.receipt', $receipt));

    $response->assertForbidden();
});
