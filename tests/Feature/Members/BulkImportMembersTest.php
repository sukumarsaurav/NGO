<?php

declare(strict_types=1);

use App\Actions\Members\BulkImportMembers;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->import = app(BulkImportMembers::class);
});

function validRow(array $overrides = []): array
{
    static $n = 0;
    $n++;

    return array_merge([
        'name' => "Test Member {$n}",
        'email' => "member{$n}@example.com",
        'phone' => '9876543210',
        'department' => null,
        'designation' => null,
        'date_of_birth' => '15/06/1990',
        'gender' => 'male',
        'blood_group' => 'O+',
        'address_line1' => null,
        'address_line2' => null,
        'city' => null,
        'state' => null,
        'pincode' => null,
        'emergency_contact_name' => null,
        'emergency_contact_phone' => null,
        'id_proof_type' => null,
        'id_proof_number' => null,
        'joined_on' => '2026-01-15',
    ], $overrides);
}

it('dry-runs without writing anything to the database', function () {
    $rows = [validRow(), validRow(), validRow()];

    $result = $this->import->handle($rows, dryRun: true);

    expect($result->validCount())->toBe(3)
        ->and($result->errors)->toBe([])
        ->and(Member::count())->toBe(0)
        ->and(User::count())->toBe(0);
});

it('reports errors for missing required fields without writing anything', function () {
    $rows = [
        validRow(),
        validRow(['name' => '']),
        validRow(['email' => '']),
    ];

    $result = $this->import->handle($rows, dryRun: true);

    expect($result->validCount())->toBe(1)
        ->and($result->errorRowCount())->toBe(2)
        ->and(Member::count())->toBe(0);
});

it('imports 197 rows and reports exactly 3 errors from a 200-row batch — the acceptance criterion', function () {
    $rows = [];

    for ($i = 1; $i <= 200; $i++) {
        $rows[] = match (true) {
            $i === 50 => validRow(['name' => '']),
            $i === 100 => validRow(['email' => 'not-an-email']),
            $i === 150 => validRow(['email' => '']),
            default => validRow(),
        };
    }

    $dryRun = $this->import->handle($rows, dryRun: true);
    expect($dryRun->validCount())->toBe(197)
        ->and($dryRun->errorRowCount())->toBe(3)
        ->and(Member::count())->toBe(0);

    $result = $this->import->handle($rows, dryRun: false);
    expect($result->validCount())->toBe(197)
        ->and($result->errorRowCount())->toBe(3)
        ->and(Member::count())->toBe(197)
        ->and(User::count())->toBe(197);
});

it('rejects an email that already exists in the database, never overwriting silently', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $result = $this->import->handle([validRow(['email' => 'existing@example.com'])], dryRun: true);

    expect($result->errorRowCount())->toBe(1)
        ->and($result->errors[0]->message)->toContain('already exists');
});

it('rejects a duplicate email within the same file, not just against the database', function () {
    $rows = [
        validRow(['email' => 'dupe@example.com']),
        validRow(['email' => 'dupe@example.com']),
    ];

    $result = $this->import->handle($rows, dryRun: true);

    expect($result->validCount())->toBe(1)
        ->and($result->errorRowCount())->toBe(1)
        ->and($result->errors[0]->row)->toBe(2);
});

it('resolves department and designation by name, case-insensitively', function () {
    $department = Department::factory()->create(['name' => 'Fundraising']);
    $designation = Designation::factory()->create(['title' => 'Volunteer Coordinator']);

    $result = $this->import->handle([
        validRow(['department' => 'fundraising', 'designation' => 'VOLUNTEER COORDINATOR']),
    ], dryRun: false);

    expect($result->created[0]->department_id)->toBe($department->id)
        ->and($result->created[0]->designation_id)->toBe($designation->id);
});

it('suggests a near-miss department name without silently guessing it', function () {
    Department::factory()->create(['name' => 'Fundraising']);

    $result = $this->import->handle([validRow(['department' => 'Fundraisng'])], dryRun: true);

    expect($result->errorRowCount())->toBe(1)
        ->and($result->errors[0]->message)->toContain("Did you mean 'Fundraising'?")
        ->and(Member::count())->toBe(0);
});

it('does not suggest a department name that is too dissimilar to be helpful', function () {
    Department::factory()->create(['name' => 'Fundraising']);

    $result = $this->import->handle([validRow(['department' => 'Zebra'])], dryRun: true);

    expect($result->errors[0]->message)->not->toContain('Did you mean');
});

it('parses both dd/mm/yyyy and yyyy-mm-dd date formats', function () {
    $result = $this->import->handle([
        validRow(['email' => 'a@example.com', 'date_of_birth' => '25/12/1985']),
        validRow(['email' => 'b@example.com', 'date_of_birth' => '1985-12-25']),
    ], dryRun: false);

    expect($result->created[0]->date_of_birth->toDateString())->toBe('1985-12-25')
        ->and($result->created[1]->date_of_birth->toDateString())->toBe('1985-12-25');
});

it('rejects an unparseable date', function () {
    $result = $this->import->handle([validRow(['date_of_birth' => 'not-a-date'])], dryRun: true);

    expect($result->errorRowCount())->toBe(1)
        ->and($result->errors[0]->field)->toBe('date_of_birth');
});

it('assigns the member role and generates a real member code for each imported row', function () {
    $result = $this->import->handle([validRow(), validRow()], dryRun: false);

    foreach ($result->created as $member) {
        expect($member->user->hasRole('member'))->toBeTrue()
            ->and($member->member_code)->toMatch('/^VGWGF-\d{4}-\d{5}$/');
    }

    expect($result->created[0]->member_code)->not->toBe($result->created[1]->member_code);
});
