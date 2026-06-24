<?php

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes a primary image and orders images by position', function () {
    $event = Event::factory()->create();
    EventImage::factory()->for($event)->secondary(1)->create();
    EventImage::factory()->for($event)->create(); // primary, position 0

    expect($event->primaryImage->position)->toBe(0)
        ->and($event->primaryImage->is_primary)->toBeTrue()
        ->and($event->images->pluck('position')->all())->toBe([0, 1]);
});

it('builds a public URL for an image path', function () {
    $image = EventImage::factory()->make(['path' => 'events/concert-1.jpg']);

    expect($image->url)->toContain('/storage/events/concert-1.jpg');
});
