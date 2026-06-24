<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publishedEvent(array $attributes): Event
{
    return Event::factory()->create(array_merge(['status' => 'published'], $attributes));
}

it('returns only published events on the grid feed', function () {
    publishedEvent(['created_time' => now()->addDays(5)->timestamp]);
    Event::factory()->create(['status' => 'draft', 'created_time' => now()->addDays(5)->timestamp]);

    $response = $this->getJson('/events/grid?from='.now()->toDateString());

    $response->assertOk();
    expect($response->json('total'))->toBe(1);
});

it('filters the grid by category and date range', function () {
    $concert = publishedEvent(['type' => 'concert', 'created_time' => now()->addDays(5)->timestamp]);
    publishedEvent(['type' => 'workshop', 'created_time' => now()->addDays(5)->timestamp]);
    publishedEvent(['type' => 'concert', 'created_time' => now()->subDays(30)->timestamp]); // past

    $response = $this->getJson('/events/grid?from='.now()->toDateString().'&category=concert');

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toHaveCount(1)->and($ids->first())->toBe($concert->id);
});

it('filters the grid by city using a bounding box', function () {
    $ny = publishedEvent(['latitude' => 40.7128, 'longitude' => -74.0060, 'created_time' => now()->addDays(2)->timestamp]);
    publishedEvent(['latitude' => 35.6762, 'longitude' => 139.6503, 'created_time' => now()->addDays(2)->timestamp]); // Tokyo

    $response = $this->getJson('/events/grid?from='.now()->toDateString().'&city=New York');

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toHaveCount(1)->and($ids->first())->toBe($ny->id);
});

it('returns only events inside the map bounding box', function () {
    $ny = publishedEvent(['latitude' => 40.7128, 'longitude' => -74.0060, 'created_time' => now()->addDays(2)->timestamp]);
    publishedEvent(['latitude' => 35.6762, 'longitude' => 139.6503, 'created_time' => now()->addDays(2)->timestamp]); // Tokyo

    $response = $this->getJson('/events/map?north=41&south=40&east=-73&west=-75&from='.now()->toDateString());

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toHaveCount(1)->and($ids->first())->toBe($ny->id);
});

it('requires a bounding box for the map feed', function () {
    $this->getJson('/events/map')->assertStatus(422);
});
