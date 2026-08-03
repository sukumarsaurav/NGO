<?php

declare(strict_types=1);

use App\Mail\NewsletterConfirmMail;
use App\Models\Subscriber;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('queues a confirmation email when a new address subscribes', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'confirmme@example.com']);

    Mail::assertQueued(NewsletterConfirmMail::class);
});

it('does not re-queue a confirmation email for an already-confirmed subscriber', function () {
    Mail::fake();

    Subscriber::query()->create([
        'email' => 'already@example.com',
        'token' => str()->random(40),
        'confirmed_at' => now(),
    ]);

    $this->post(route('newsletter.subscribe'), ['email' => 'already@example.com']);

    Mail::assertNothingQueued();
});

it('confirms a subscriber via their token link', function () {
    $subscriber = Subscriber::query()->create([
        'email' => 'toconfirm@example.com',
        'token' => str()->random(40),
    ]);

    $this->get(route('newsletter.confirm', ['token' => $subscriber->token]))->assertRedirect();

    expect($subscriber->fresh()->confirmed_at)->not->toBeNull();
});

it('unsubscribes via a token link', function () {
    $subscriber = Subscriber::query()->create([
        'email' => 'toleave@example.com',
        'token' => str()->random(40),
        'confirmed_at' => now(),
    ]);

    $this->get(route('newsletter.unsubscribe', ['token' => $subscriber->token]))->assertRedirect();

    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('404s an unknown confirm token', function () {
    $this->get(route('newsletter.confirm', ['token' => 'nonexistent']))->assertNotFound();
});
