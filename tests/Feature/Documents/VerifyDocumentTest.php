<?php

declare(strict_types=1);

use App\Actions\Documents\IssueCertificate;
use App\Actions\Documents\RevokeDocument;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DocumentTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DocumentTemplateSeeder::class);
    Queue::fake();
});

it('shows a valid document with member details when scanned', function () {
    $admin = User::factory()->create();
    $member = Member::factory()->create();
    $document = app(IssueCertificate::class)->handle($member, 'Volunteer Award', $admin->id);
    $document->update(['status' => 'issued']);

    $response = $this->get(route('verify.show', $document->uuid));

    $response->assertOk()
        ->assertSeeText('Valid document')
        ->assertSeeText($document->document_number);

    expect($document->fresh()->verified_count)->toBe(1);
});

it('shows REVOKED with the reason when the document has been revoked', function () {
    $admin = User::factory()->create();
    $member = Member::factory()->create();
    $document = app(IssueCertificate::class)->handle($member, 'Volunteer Award', $admin->id);
    $document->update(['status' => 'issued']);

    app(RevokeDocument::class)->handle($document, 'Issued in error');

    $response = $this->get(route('verify.show', $document->uuid));

    $response->assertOk()
        ->assertSeeText('REVOKED')
        ->assertSeeText('Issued in error');
});

it('shows not found for an unknown uuid', function () {
    $response = $this->get(route('verify.show', (string) Str::uuid()));

    $response->assertOk()->assertSeeText('Not found');
});
