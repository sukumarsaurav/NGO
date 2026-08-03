<?php

declare(strict_types=1);

use App\Actions\Campaigns\PostCampaignUpdate;
use App\Actions\Donations\InitiateDonation;
use App\Actions\Donations\RecordSuccessfulDonation;
use App\Mail\CampaignUpdateMail;
use App\Models\Campaign;
use App\Services\Payment\DTOs\PaymentResult;
use App\Support\Money;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    Mail::fake();
});

function donorGaveTo(Campaign $campaign, string $email)
{
    $initiation = app(InitiateDonation::class)->handle(
        donorName: 'Update Donor',
        donorEmail: $email,
        donorPhone: null,
        amount: Money::fromRupees(500),
        campaignId: $campaign->id,
    );

    return app(RecordSuccessfulDonation::class)->handle($initiation->order->orderId, new PaymentResult(
        paymentId: 'pay_'.uniqid(),
        orderId: $initiation->order->orderId,
        status: 'captured',
        amount: Money::fromRupees(500),
    ));
}

it('emails every donor once when an update with notify_donors is published immediately', function () {
    $campaign = Campaign::factory()->active()->create();
    donorGaveTo($campaign, 'donor1@example.com');
    donorGaveTo($campaign, 'donor2@example.com');

    app(PostCampaignUpdate::class)->handle($campaign, [
        'title' => 'Big News',
        'body' => 'We made great progress this month.',
        'notify_donors' => true,
    ]);

    Mail::assertQueued(CampaignUpdateMail::class, fn ($mail) => $mail->hasTo('donor1@example.com'));
    Mail::assertQueued(CampaignUpdateMail::class, fn ($mail) => $mail->hasTo('donor2@example.com'));
    Mail::assertQueued(CampaignUpdateMail::class, 2);
});

it('sends only one email to a donor who gave to the campaign multiple times', function () {
    $campaign = Campaign::factory()->active()->create();
    donorGaveTo($campaign, 'repeat-donor@example.com');
    donorGaveTo($campaign, 'repeat-donor@example.com');

    app(PostCampaignUpdate::class)->handle($campaign, [
        'title' => 'Update',
        'body' => 'Body text.',
        'notify_donors' => true,
    ]);

    Mail::assertQueued(CampaignUpdateMail::class, 1);
});

it('does not email donors when notify_donors is false', function () {
    $campaign = Campaign::factory()->active()->create();
    donorGaveTo($campaign, 'silent@example.com');

    app(PostCampaignUpdate::class)->handle($campaign, [
        'title' => 'Quiet Update',
        'body' => 'No notification needed.',
        'notify_donors' => false,
    ]);

    Mail::assertNotQueued(CampaignUpdateMail::class);
});

it('does not email donors for an update scheduled in the future', function () {
    $campaign = Campaign::factory()->active()->create();
    donorGaveTo($campaign, 'future@example.com');

    app(PostCampaignUpdate::class)->handle($campaign, [
        'title' => 'Scheduled Update',
        'body' => 'Coming soon.',
        'notify_donors' => true,
        'published_at' => now()->addDay(),
    ]);

    Mail::assertNotQueued(CampaignUpdateMail::class);
});
