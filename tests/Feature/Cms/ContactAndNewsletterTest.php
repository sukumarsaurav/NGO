<?php

declare(strict_types=1);

use App\Mail\NewContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Subscriber;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('shows the contact form', function () {
    $this->get(route('contact.show'))->assertOk()->assertSeeText('Contact Us');
});

it('submits the contact form and emails the org address', function () {
    Mail::fake();

    $response = $this->post(route('contact.store'), [
        'name' => 'Contact Tester',
        'email' => 'contacttester@example.com',
        'message' => 'I have a question about your work.',
    ]);

    $response->assertRedirect();
    expect(ContactMessage::query()->where('email', 'contacttester@example.com')->exists())->toBeTrue();
    Mail::assertQueued(NewContactMessageMail::class);
});

it('validates required fields on the contact form', function () {
    $this->post(route('contact.store'), [])->assertSessionHasErrors(['name', 'email', 'message']);
});

it('silently drops a submission with the honeypot field filled', function () {
    Mail::fake();

    $response = $this->post(route('contact.store'), [
        'name' => 'A Bot',
        'email' => 'bot@example.com',
        'message' => 'Buy my product',
        'website' => 'https://spam.example.com',
    ]);

    $response->assertRedirect();
    expect(ContactMessage::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Mail::assertNothingQueued();
});

it('rate limits contact submissions to 3 per hour per IP', function () {
    $payload = fn (int $i) => ['name' => 'Spammer', 'email' => "spam{$i}@example.com", 'message' => 'Spam message text.'];

    for ($i = 1; $i <= 3; $i++) {
        $this->post(route('contact.store'), $payload($i))->assertRedirect();
    }

    $this->post(route('contact.store'), $payload(4))->assertStatus(429);
});

it('subscribes an email to the newsletter', function () {
    $response = $this->post(route('newsletter.subscribe'), ['email' => 'subscriber@example.com']);

    $response->assertRedirect();
    expect(Subscriber::query()->where('email', 'subscriber@example.com')->exists())->toBeTrue();
});

it('re-subscribing an unsubscribed email clears unsubscribed_at', function () {
    $subscriber = Subscriber::query()->create([
        'email' => 'returning@example.com',
        'token' => str()->random(40),
        'unsubscribed_at' => now(),
    ]);

    $this->post(route('newsletter.subscribe'), ['email' => 'returning@example.com']);

    expect($subscriber->fresh()->unsubscribed_at)->toBeNull();
});

it('validates the newsletter email', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');
});
