<?php

declare(strict_types=1);

use App\Actions\Campaigns\ApproveFundraiserRequest;
use App\Actions\Campaigns\RejectFundraiserRequest;
use App\Actions\Campaigns\SubmitFundraiserRequest;
use App\Mail\FundraiserRequestApprovedMail;
use App\Mail\FundraiserRequestRejectedMail;
use App\Mail\NewFundraiserRequestMail;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\FundraiserRequest;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    Mail::fake();
});

it('emails every admin when a public fundraiser request is submitted', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $unrelated = User::factory()->create();

    app(SubmitFundraiserRequest::class)->handle([
        'name' => 'Jane Requester',
        'email' => 'jane@example.com',
        'phone' => '9876543210',
        'title' => 'Help Build a Shelter',
        'description' => 'We need funds to build a shelter.',
        'goal_amount' => 500000,
    ]);

    Mail::assertQueued(NewFundraiserRequestMail::class, fn ($mail) => $mail->hasTo($admin->email));
    Mail::assertNotQueued(NewFundraiserRequestMail::class, fn ($mail) => $mail->hasTo($unrelated->email));
});

it('creates the request with status new', function () {
    app(SubmitFundraiserRequest::class)->handle([
        'name' => 'Jane Requester',
        'email' => 'jane2@example.com',
        'phone' => '9876543210',
        'title' => 'Help Build a Shelter',
        'description' => 'We need funds.',
        'goal_amount' => 500000,
    ]);

    expect(FundraiserRequest::query()->where('email', 'jane2@example.com')->first()->status->value)->toBe('new');
});

it('approving a request creates a draft campaign pre-filled from it and emails the requester', function () {
    $category = CampaignCategory::factory()->create();
    $admin = User::factory()->create();

    $request = FundraiserRequest::factory()->create([
        'title' => 'Winter Relief Drive',
        'description' => 'Detailed story about winter relief.',
        'goal_amount' => 750000,
        'cause_category_id' => $category->id,
        'organisation_name' => 'Helping Hands',
    ]);

    $updated = app(ApproveFundraiserRequest::class)->handle($request, $admin->id);

    expect($updated->status->value)->toBe('approved')
        ->and($updated->reviewed_by_user_id)->toBe($admin->id)
        ->and($updated->campaign_id)->not->toBeNull();

    $campaign = Campaign::find($updated->campaign_id);
    expect($campaign->status->value)->toBe('draft')
        ->and($campaign->title)->toBe('Winter Relief Drive')
        ->and($campaign->goal_amount)->toBe(750000)
        ->and($campaign->category_id)->toBe($category->id)
        ->and($campaign->beneficiary_name)->toBe('Helping Hands');

    Mail::assertQueued(FundraiserRequestApprovedMail::class, fn ($mail) => $mail->hasTo($request->email));
});

it('refuses to approve a request without a cause category', function () {
    $request = FundraiserRequest::factory()->create(['cause_category_id' => null]);

    app(ApproveFundraiserRequest::class)->handle($request);
})->throws(InvalidArgumentException::class, 'cause category');

it('refuses to approve an already-approved request', function () {
    $category = CampaignCategory::factory()->create();
    $request = FundraiserRequest::factory()->create(['cause_category_id' => $category->id]);

    app(ApproveFundraiserRequest::class)->handle($request);
    app(ApproveFundraiserRequest::class)->handle($request->fresh());
})->throws(InvalidArgumentException::class);

it('rejecting a request sends a courteous email with the reason', function () {
    $request = FundraiserRequest::factory()->create();
    $admin = User::factory()->create();

    $updated = app(RejectFundraiserRequest::class)->handle($request, 'Goal amount is unrealistic for the described cause.', $admin->id);

    expect($updated->status->value)->toBe('rejected')
        ->and($updated->review_notes)->toBe('Goal amount is unrealistic for the described cause.');

    Mail::assertQueued(FundraiserRequestRejectedMail::class, function ($mail) use ($request) {
        return $mail->hasTo($request->email) && $mail->request->review_notes === 'Goal amount is unrealistic for the described cause.';
    });
});

it('refuses to reject an already-rejected request', function () {
    $request = FundraiserRequest::factory()->create();

    app(RejectFundraiserRequest::class)->handle($request, 'first reason');
    app(RejectFundraiserRequest::class)->handle($request->fresh(), 'second reason');
})->throws(InvalidArgumentException::class);
