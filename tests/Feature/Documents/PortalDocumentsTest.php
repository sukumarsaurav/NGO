<?php

declare(strict_types=1);

use App\Actions\Documents\IssueCertificate;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DocumentTemplateSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DocumentTemplateSeeder::class);
    Queue::fake();
    Storage::fake('local');
});

it('lets a member download their own issued document', function () {
    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id]);

    $document = app(IssueCertificate::class)->handle($member, 'Volunteer Award', $user->id);
    Storage::disk('local')->put("documents/{$document->uuid}.pdf", '%PDF-1.7 fake');
    $document->update(['status' => 'issued', 'file_path' => "documents/{$document->uuid}.pdf"]);

    $response = $this->actingAs($user)->get(route('portal.documents.download', $document));

    $response->assertOk();
    expect($document->fresh()->download_count)->toBe(1);
});

it('forbids downloading another member\'s document', function () {
    $owner = User::factory()->create();
    $ownerMember = Member::factory()->create(['user_id' => $owner->id]);
    $document = app(IssueCertificate::class)->handle($ownerMember, 'Volunteer Award', $owner->id);
    Storage::disk('local')->put("documents/{$document->uuid}.pdf", '%PDF-1.7 fake');
    $document->update(['status' => 'issued', 'file_path' => "documents/{$document->uuid}.pdf"]);

    $intruder = User::factory()->create();
    Member::factory()->create(['user_id' => $intruder->id]);

    $response = $this->actingAs($intruder)->get(route('portal.documents.download', $document));

    $response->assertForbidden();
});
