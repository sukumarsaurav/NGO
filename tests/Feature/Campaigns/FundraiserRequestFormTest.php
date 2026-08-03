<?php

declare(strict_types=1);

use App\Mail\NewFundraiserRequestMail;
use App\Models\CampaignCategory;
use App\Models\FundraiserRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Mail::fake();
    User::factory()->create()->assignRole('admin');
});

it('shows the start-a-fundraiser form', function () {
    $this->get(route('fundraiser.show'))->assertOk()->assertSeeText('Start a Fundraiser');
});

it('submits a fundraiser request from the public form', function () {
    $category = CampaignCategory::factory()->create();

    $response = $this->post(route('fundraiser.store'), [
        'name' => 'Public Requester',
        'email' => 'publicrequester@example.com',
        'phone' => '9876543210',
        'organisation_name' => 'Local Trust',
        'cause_category_id' => $category->id,
        'title' => 'Community Kitchen',
        'description' => 'We run a community kitchen and need funds.',
        'goal_amount' => '5000',
    ]);

    $response->assertRedirect();
    expect(FundraiserRequest::query()->where('email', 'publicrequester@example.com')->exists())->toBeTrue();
    Mail::assertQueued(NewFundraiserRequestMail::class);
});

it('validates required fields on the fundraiser form', function () {
    $this->post(route('fundraiser.store'), [])->assertSessionHasErrors(['name', 'email', 'phone', 'title', 'description', 'goal_amount']);
});

it('rate limits fundraiser request submissions to 3 per hour per IP', function () {
    $payload = fn (int $i) => [
        'name' => 'Spammer',
        'email' => "spammer{$i}@example.com",
        'phone' => '9876543210',
        'title' => 'Spam Title',
        'description' => 'Spam description text here.',
        'goal_amount' => '1000',
    ];

    for ($i = 1; $i <= 3; $i++) {
        $this->post(route('fundraiser.store'), $payload($i))->assertRedirect();
    }

    $this->post(route('fundraiser.store'), $payload(4))->assertStatus(429);
});
