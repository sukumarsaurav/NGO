<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Admin\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Mail\EmailTemplatePreviewMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists every seeded email template', function () {
    Livewire::actingAs($this->admin)->test(ListEmailTemplates::class)->assertSuccessful();

    expect(EmailTemplate::query()->count())->toBeGreaterThan(15);
});

it('edits a template subject and body', function () {
    $template = EmailTemplate::query()->where('key', 'donation.thank_you')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(EditEmailTemplate::class, ['record' => $template->getKey()])
        ->fillForm([
            'subject' => 'Updated subject {{ receipt_number }}',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($template->fresh()->subject)->toBe('Updated subject {{ receipt_number }}');
});

it('test-sends a rendered preview to the given address', function () {
    Mail::fake();

    $template = EmailTemplate::query()->where('key', 'donation.thank_you')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ListEmailTemplates::class)
        ->callTableAction('testSend', $template, data: ['to' => 'reviewer@example.com']);

    Mail::assertSent(EmailTemplatePreviewMail::class, fn ($mail) => $mail->hasTo('reviewer@example.com'));
});
