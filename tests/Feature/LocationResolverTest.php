<?php

use App\Services\LocationResolver;

it('resolves coordinates to the nearest city and timezone', function () {
    $resolver = new LocationResolver;

    // Jittered around the New York anchor.
    $ny = $resolver->resolve(40.75, -74.01);
    expect($ny['city'])->toBe('New York')
        ->and($ny['timezone'])->toBe('America/New_York')
        ->and($ny['label'])->toBe('Near New York, USA');

    $tokyo = $resolver->resolve(35.70, 139.70);
    expect($tokyo['city'])->toBe('Tokyo')
        ->and($tokyo['timezone'])->toBe('Asia/Tokyo');
});

it('handles missing coordinates gracefully', function () {
    $resolver = new LocationResolver;

    $result = $resolver->resolve(null, null);
    expect($result['timezone'])->toBe('UTC')
        ->and($result['city'])->toBe('Unknown');
});

it('returns a bounding box for a known city', function () {
    $box = (new LocationResolver)->boundingBox('New York');

    expect($box)->not->toBeNull()
        ->and($box['min_lat'])->toBeLessThan(40.7128)
        ->and($box['max_lat'])->toBeGreaterThan(40.7128);
});
