<?php

declare(strict_types=1);

use App\Mail\NewInternshipApplicationMail;
use App\Models\InternshipApplication;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('shows the internship form', function () {
    $this->get(route('internship.show'))->assertOk()->assertSeeText('Internship Program');
});

it('submits the internship form and emails the org address', function () {
    Mail::fake();

    $response = $this->post(route('internship.store'), [
        'name' => 'Applicant One',
        'email' => 'applicant@example.com',
        'phone' => '9876543210',
        'track' => 'Communications',
    ]);

    $response->assertRedirect();
    expect(InternshipApplication::query()->where('email', 'applicant@example.com')->exists())->toBeTrue();
    Mail::assertQueued(NewInternshipApplicationMail::class);
});

it('stores an uploaded resume on the private disk', function () {
    Storage::fake('local');
    Mail::fake();

    $this->post(route('internship.store'), [
        'name' => 'Resume Applicant',
        'email' => 'resume-applicant@example.com',
        'phone' => '9876543210',
        'resume' => UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf'),
    ])->assertRedirect();

    $application = InternshipApplication::query()->where('email', 'resume-applicant@example.com')->firstOrFail();

    expect($application->resume_path)->not->toBeNull();
    Storage::disk('local')->assertExists($application->resume_path);
});

it('validates required fields on the internship form', function () {
    $this->post(route('internship.store'), [])->assertSessionHasErrors(['name', 'email', 'phone']);
});

it('silently drops an internship submission with the honeypot field filled', function () {
    Mail::fake();

    $response = $this->post(route('internship.store'), [
        'name' => 'A Bot',
        'email' => 'bot@example.com',
        'phone' => '0000000000',
        'website' => 'https://spam.example.com',
    ]);

    $response->assertRedirect();
    expect(InternshipApplication::query()->where('email', 'bot@example.com')->exists())->toBeFalse();
    Mail::assertNothingQueued();
});

it('rate limits internship submissions to 3 per hour per IP', function () {
    $payload = fn (int $i) => ['name' => 'Spammer', 'email' => "spam{$i}@example.com", 'phone' => '0000000000'];

    for ($i = 1; $i <= 3; $i++) {
        $this->post(route('internship.store'), $payload($i))->assertRedirect();
    }

    $this->post(route('internship.store'), $payload(4))->assertStatus(429);
});
