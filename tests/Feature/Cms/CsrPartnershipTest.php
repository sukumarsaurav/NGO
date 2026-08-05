<?php

declare(strict_types=1);

use App\Mail\NewCsrInquiryMail;
use App\Models\CsrInquiry;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('shows the csr partnership form', function () {
    $this->get(route('csr-partnership.show'))->assertOk()->assertSeeText('CSR Partnership');
});

it('submits the csr partnership form and emails the org address', function () {
    Mail::fake();

    $response = $this->post(route('csr-partnership.store'), [
        'organisation_name' => 'Acme Corp',
        'contact_name' => 'Jane CSR',
        'email' => 'jane@acme.example.com',
        'phone' => '9876543210',
        'message' => 'We would like to explore a CSR partnership.',
    ]);

    $response->assertRedirect();
    expect(CsrInquiry::query()->where('email', 'jane@acme.example.com')->exists())->toBeTrue();
    Mail::assertQueued(NewCsrInquiryMail::class);
});

it('validates required fields on the csr partnership form', function () {
    $this->post(route('csr-partnership.store'), [])
        ->assertSessionHasErrors(['organisation_name', 'contact_name', 'email', 'phone', 'message']);
});

it('silently drops a csr partnership submission with the honeypot field filled', function () {
    Mail::fake();

    $response = $this->post(route('csr-partnership.store'), [
        'organisation_name' => 'Spam Corp',
        'contact_name' => 'A Bot',
        'email' => 'bot@example.com',
        'phone' => '0000000000',
        'message' => 'Buy my product',
        'website' => 'https://spam.example.com',
    ]);

    $response->assertRedirect();
    expect(CsrInquiry::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Mail::assertNothingQueued();
});

it('rate limits csr partnership submissions to 3 per hour per IP', function () {
    $payload = fn (int $i) => [
        'organisation_name' => 'Spammer Inc',
        'contact_name' => 'Spammer',
        'email' => "spam{$i}@example.com",
        'phone' => '0000000000',
        'message' => 'Spam message text.',
    ];

    for ($i = 1; $i <= 3; $i++) {
        $this->post(route('csr-partnership.store'), $payload($i))->assertRedirect();
    }

    $this->post(route('csr-partnership.store'), $payload(4))->assertStatus(429);
});
