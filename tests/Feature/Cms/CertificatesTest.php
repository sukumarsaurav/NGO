<?php

declare(strict_types=1);

use App\Models\Certificate;

it('shows only published certificates on the certificates page', function () {
    Certificate::factory()->create(['title' => 'Visible Certificate', 'is_published' => true]);
    Certificate::factory()->create(['title' => 'Hidden Certificate', 'is_published' => false]);

    $response = $this->get(route('certificates.index'));

    $response->assertOk()->assertSeeText('Visible Certificate')->assertDontSeeText('Hidden Certificate');
});

it('shows an empty state when there are no certificates', function () {
    $this->get(route('certificates.index'))->assertOk()->assertSeeText('No certificates published yet');
});
