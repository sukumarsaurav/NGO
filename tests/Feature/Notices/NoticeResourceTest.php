<?php

declare(strict_types=1);

use App\Enums\NoticeAudience;
use App\Enums\NoticeStatus;
use App\Filament\Admin\Resources\Notices\Pages\CreateNotice;
use App\Filament\Admin\Resources\Notices\Pages\ListNotices;
use App\Models\Department;
use App\Models\Member;
use App\Models\Notice;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists notices in the admin', function () {
    Livewire::actingAs($this->admin)->test(ListNotices::class)->assertSuccessful();
});

it('creates an all_members notice and stamps the creating admin', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateNotice::class)
        ->fillForm([
            'title' => 'Field trip update',
            'body' => '<p>Details inside.</p>',
            'audience' => NoticeAudience::AllMembers->value,
            'priority' => 'normal',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $notice = Notice::query()->where('title', 'Field trip update')->firstOrFail();

    expect($notice->audience)->toBe(NoticeAudience::AllMembers)
        ->and($notice->created_by_user_id)->toBe($this->admin->id);
});

it('folds the department multiselect into audience_filter on save', function () {
    $department = Department::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CreateNotice::class)
        ->fillForm(['audience' => NoticeAudience::Department->value])
        ->fillForm([
            'title' => 'Field trip update',
            'body' => '<p>Details inside.</p>',
            'department_ids' => [$department->id],
            'priority' => 'normal',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $notice = Notice::query()->where('title', 'Field trip update')->firstOrFail();

    expect($notice->audience_filter['department_ids'])->toBe([$department->id]);
});

it('publishing a draft notice from the table materialises recipients and flips status', function () {
    Queue::fake();

    Member::factory()->active()->create(['department_id' => null]);
    $notice = Notice::factory()->create(['audience' => NoticeAudience::AllMembers->value]);

    Livewire::actingAs($this->admin)
        ->test(ListNotices::class)
        ->callTableAction('publish', $notice);

    expect($notice->fresh()->status)->toBe(NoticeStatus::Sending)
        ->and($notice->fresh()->recipient_count)->toBe(1);
});
