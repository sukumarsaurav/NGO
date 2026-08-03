<?php

declare(strict_types=1);

use App\Actions\Documents\IssueAppointmentLetter;
use App\Actions\Documents\IssueCertificate;
use App\Actions\Documents\IssueIdCard;
use App\Actions\Documents\RevokeDocument;
use App\Jobs\GeneratePdfDocument;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentTemplate;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DocumentTemplateSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(DocumentTemplateSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->admin = User::factory()->create();
});

it('issues an ID card, queues the PDF job, and freezes a snapshot of the member', function () {
    Queue::fake();

    $department = Department::factory()->create(['name' => 'Fundraising']);
    $member = Member::factory()->create(['department_id' => $department->id]);

    $document = app(IssueIdCard::class)->handle($member, $this->admin->id);

    expect($document->status->value)->toBe('queued')
        ->and($document->type->value)->toBe('id_card')
        ->and($document->document_number)->toStartWith('IC-')
        ->and($document->snapshot_data['department'])->toBe('Fundraising');

    Queue::assertPushed(GeneratePdfDocument::class, fn ($job) => $job->issuedDocumentId === $document->id);

    // Changing the member's department after issuing must not rewrite history.
    $department2 = Department::factory()->create(['name' => 'Operations']);
    $member->update(['department_id' => $department2->id]);

    expect($document->fresh()->snapshot_data['department'])->toBe('Fundraising');
});

it('encodes the documents own uuid in its qr payload, not a different one', function () {
    Queue::fake();

    $member = Member::factory()->create();
    $document = app(IssueIdCard::class)->handle($member, $this->admin->id);

    expect($document->qr_payload)->toContain($document->uuid);
});

it('refuses to issue an appointment letter for a member with no designation', function () {
    $member = Member::factory()->create(['designation_id' => null]);

    app(IssueAppointmentLetter::class)->handle($member, $this->admin->id);
})->throws(InvalidArgumentException::class);

it('issues an appointment letter using the designation letter template when set', function () {
    Queue::fake();

    $template = DocumentTemplate::factory()->create(['type' => 'appointment_letter']);
    $designation = Designation::factory()->create(['title' => 'Secretary', 'letter_template_id' => $template->id]);
    $member = Member::factory()->create(['designation_id' => $designation->id]);

    $document = app(IssueAppointmentLetter::class)->handle($member, $this->admin->id);

    expect($document->template_id)->toBe($template->id)
        ->and($document->title)->toContain('Secretary');
});

it('issues a certificate with a custom title', function () {
    Queue::fake();

    $member = Member::factory()->create();

    $document = app(IssueCertificate::class)->handle($member, 'Volunteer of the Year 2026', $this->admin->id);

    expect($document->title)->toBe('Volunteer of the Year 2026')
        ->and($document->type->value)->toBe('certificate');
});

it('revokes an issued document with a reason', function () {
    Queue::fake();

    $member = Member::factory()->create();
    $document = app(IssueCertificate::class)->handle($member, 'Test cert', $this->admin->id);
    $document->update(['status' => 'issued']);

    $revoked = app(RevokeDocument::class)->handle($document, 'Printed with a typo');

    expect($revoked->status->value)->toBe('revoked')
        ->and($revoked->revoked_reason)->toBe('Printed with a typo')
        ->and($revoked->revoked_at)->not->toBeNull();
});

it('refuses to revoke a document that is not currently issued', function () {
    $member = Member::factory()->create();
    $document = app(IssueCertificate::class)->handle($member, 'Test cert', $this->admin->id);
    // still 'queued', never flipped to 'issued'

    app(RevokeDocument::class)->handle($document, 'irrelevant');
})->throws(InvalidArgumentException::class);

it('links a superseded document to its replacement', function () {
    Queue::fake();

    $member = Member::factory()->create();
    $old = app(IssueCertificate::class)->handle($member, 'Old cert', $this->admin->id);
    $new = app(IssueCertificate::class)->handle($member, 'Reissued cert', $this->admin->id);

    $result = app(RevokeDocument::class)->supersede($old, $new);

    expect($result->status->value)->toBe('superseded')
        ->and($result->superseded_by_id)->toBe($new->id);
});
