<?php

declare(strict_types=1);

use App\Enums\PostCategory;
use App\Filament\Admin\Resources\CampaignCategories\Pages\CreateCampaignCategory;
use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Posts\Pages\CreatePost;
use App\Models\CampaignCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/*
 * Each of these admin forms fills the slug in from the title/name when that field loses focus.
 * That used to store a Stringable object as the slug. It crashed campaigns (CreateCampaign takes a
 * strict string; see CampaignResourceTest), while these three happened to survive it because their
 * save paths do not type-hint the slug. They now store a real string too, so they cannot start
 * failing if that ever changes — and these tests cover the auto-fill path itself, which the older
 * tests skipped by typing the slug by hand.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('saves a blog post whose slug was auto-filled from the title', function () {
    Livewire::actingAs($this->admin)
        ->test(CreatePost::class)
        ->set('data.title', 'A Day at the Food Drive')
        ->fillForm(['category' => PostCategory::ImpactStories->value, 'body' => '<p>Story.</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Post::query()->latest('id')->first()->slug)->toBe('a-day-at-the-food-drive');
});

it('saves a CMS page whose slug was auto-filled from the title', function () {
    Livewire::actingAs($this->admin)
        ->test(CreatePage::class)
        ->set('data.title', 'Our Impact Report')
        ->fillForm(['body' => '<p>Numbers.</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::query()->where('slug', 'our-impact-report')->exists())->toBeTrue();
});

it('saves a campaign category whose slug was auto-filled from the name', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateCampaignCategory::class)
        ->set('data.name', 'Elder Care')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(CampaignCategory::query()->where('slug', 'elder-care')->exists())->toBeTrue();
});
