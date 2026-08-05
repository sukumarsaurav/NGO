<?php

declare(strict_types=1);

use App\Models\GalleryPhoto;

it('shows only published photos on the gallery page', function () {
    GalleryPhoto::factory()->create(['title' => 'Visible Photo', 'is_published' => true]);
    GalleryPhoto::factory()->create(['title' => 'Hidden Photo', 'is_published' => false]);

    $response = $this->get(route('gallery.index'));

    $response->assertOk()->assertSeeText('Visible Photo')->assertDontSeeText('Hidden Photo');
});

it('shows an empty state when there are no photos', function () {
    $this->get(route('gallery.index'))->assertOk()->assertSeeText('No photos yet');
});
