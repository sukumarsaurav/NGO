<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Members\Pages\ImportMembers;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

function csvFile(string $content): UploadedFile
{
    // Livewire's file-upload testing expects Laravel's fake-file testing
    // helper (it reads a public ->name property) — a manually-constructed
    // UploadedFile doesn't have one and breaks Livewire's upload lifecycle.
    return UploadedFile::fake()->createWithContent('members.csv', $content);
}

it('previews a real CSV upload with a dry run and reports errors without writing to the database', function () {
    $csv = "name,email,phone,joined_on\n"
        ."Anita Sharma,anita@example.com,9876543210,15/01/2026\n"
        .",missingname@example.com,9876543211,2026-01-16\n"
        ."Rohit Gupta,not-an-email,9876543212,2026-01-17\n";

    Livewire::actingAs($this->admin)
        ->test(ImportMembers::class)
        ->set('data.csv', csvFile($csv))
        ->call('parseAndPreview')
        ->assertSet('summary.total', 3)
        ->assertSet('summary.valid', 1)
        ->assertSet('summary.errorRows', 2);

    expect(Member::count())->toBe(0);
});

it('matches CSV headers case-insensitively with spaces or underscores', function () {
    $csv = "Name,Email,Date of Birth\nSuresh Iyer,suresh@example.com,10/05/1992\n";

    $component = Livewire::actingAs($this->admin)
        ->test(ImportMembers::class)
        ->set('data.csv', csvFile($csv))
        ->call('parseAndPreview');

    $component->assertSet('summary.valid', 1);
});

it('actually imports valid rows when confirmed, and skips invalid ones', function () {
    $csv = "name,email,joined_on\n"
        ."Deepa Nair,deepa@example.com,2026-01-01\n"
        .",badrow@example.com,2026-01-02\n"
        ."Vikram Joshi,vikram@example.com,2026-01-03\n";

    $component = Livewire::actingAs($this->admin)
        ->test(ImportMembers::class)
        ->set('data.csv', csvFile($csv))
        ->call('parseAndPreview');

    expect(Member::count())->toBe(0);

    $component->call('confirmImport')
        ->assertSet('imported', true)
        ->assertSet('summary.valid', 2);

    expect(Member::count())->toBe(2)
        ->and(User::where('email', 'deepa@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'vikram@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'badrow@example.com')->exists())->toBeFalse();
});

it('rejects a manager from the import page', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get('/admin/members/import')
        ->assertForbidden();
});
