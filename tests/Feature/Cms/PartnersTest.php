<?php

declare(strict_types=1);

use App\Models\Partner;

it('shows only published partners on the partners page', function () {
    Partner::factory()->create(['name' => 'Visible Partner', 'is_published' => true]);
    Partner::factory()->create(['name' => 'Hidden Partner', 'is_published' => false]);

    $response = $this->get(route('partners.index'));

    $response->assertOk()->assertSeeText('Visible Partner')->assertDontSeeText('Hidden Partner');
});

it('groups partners by category only when more than one category exists', function () {
    Partner::factory()->create(['name' => 'Only Partner', 'category' => 'Corporate Partner']);

    $response = $this->get(route('partners.index'));

    $response->assertOk()->assertDontSeeText('Corporate Partner');
});

it('shows an empty state when there are no partners', function () {
    $this->get(route('partners.index'))->assertOk()->assertSeeText('No partners listed yet');
});
