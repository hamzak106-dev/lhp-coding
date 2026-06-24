<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonImmutable;

/**
 * Shapes events for the API. Critically, the list/map shape never includes the
 * full ~1.5 KB JSON payload (with its padding `notes`) — only the handful of
 * fields the cards/markers need — which keeps responses small at 1.25M-row scale.
 */
class EventTransformer
{
    public function __construct(private readonly LocationResolver $locations) {}

    /**
     * Lean shape for card grid + map markers.
     *
     * @return array<string, mixed>
     */
    public function forList(Event $event): array
    {
        $location = $this->locations->resolve($event->latitude, $event->longitude);
        $image = $event->relationLoaded('primaryImage') ? $event->primaryImage : $event->images->first();

        return [
            'id' => $event->id,
            'title' => $event->title(),
            'category' => $event->type,
            'status' => $event->status,
            'start_iso' => $this->iso($event->created_time),
            'timezone' => $location['timezone'],
            'location_label' => $location['label'],
            'city' => $location['city'],
            'lat' => $event->latitude,
            'lng' => $event->longitude,
            'price' => $event->price(),
            'currency' => $event->currency(),
            'venue' => $event->venueName(),
            'image_url' => $image?->url,
        ];
    }

    /**
     * Full shape for the detail page.
     *
     * @return array<string, mixed>
     */
    public function forDetail(Event $event): array
    {
        $location = $this->locations->resolve($event->latitude, $event->longitude);

        return [
            'id' => $event->id,
            'title' => $event->title(),
            'description' => $event->description(),
            'category' => $event->type,
            'status' => $event->status,
            'start_iso' => $this->iso($event->created_time),
            'end_iso' => $event->endsAt()?->toIso8601String(),
            'timezone' => $location['timezone'],
            'location_label' => $location['label'],
            'city' => $location['city'],
            'country' => $location['country'],
            'lat' => $event->latitude,
            'lng' => $event->longitude,
            'price' => $event->price(),
            'currency' => $event->currency(),
            'venue' => $event->venueName(),
            'images' => $event->images->map(fn ($i) => ['url' => $i->url, 'is_primary' => $i->is_primary])->all(),
            'attendees_count' => $event->attendees_count ?? $event->attendees()->count(),
        ];
    }

    private function iso(?int $timestamp): ?string
    {
        return $timestamp !== null
            ? CarbonImmutable::createFromTimestampUTC($timestamp)->toIso8601String()
            : null;
    }
}
