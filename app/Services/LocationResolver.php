<?php

namespace App\Services;

/**
 * Offline reverse-geocoder.
 *
 * Events store only latitude/longitude. The seeder places each event within
 * ±0.5° of a known anchor city, so the nearest anchor is an accurate, fully
 * offline source of a human-readable location and the correct IANA timezone.
 * Lookups are O(number of cities) with per-coordinate memoization.
 */
class LocationResolver
{
    /** @var list<array{0: float, 1: float, 2: string, 3: string, 4: string}> */
    private array $cities;

    /** @var array<string, array{label: string, city: string, country: string, timezone: string}> */
    private array $cache = [];

    public function __construct()
    {
        /** @var list<array{0: float, 1: float, 2: string, 3: string, 4: string}> $cities */
        $cities = config('cities', []);
        $this->cities = $cities;
    }

    /**
     * Resolve coordinates to the nearest city.
     *
     * @return array{label: string, city: string, country: string, timezone: string}
     */
    public function resolve(?float $lat, ?float $lng): array
    {
        if ($lat === null || $lng === null) {
            return ['label' => 'Location unavailable', 'city' => 'Unknown', 'country' => '', 'timezone' => 'UTC'];
        }

        $key = round($lat, 4).':'.round($lng, 4);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $best = null;
        $bestDistance = INF;

        foreach ($this->cities as $city) {
            // Squared Euclidean distance is enough to rank nearest anchors.
            $distance = ($lat - $city[0]) ** 2 + ($lng - $city[1]) ** 2;
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $city;
            }
        }

        $resolved = $best === null
            ? ['label' => 'Location unavailable', 'city' => 'Unknown', 'country' => '', 'timezone' => 'UTC']
            : [
                'label' => "Near {$best[2]}, {$best[3]}",
                'city' => $best[2],
                'country' => $best[3],
                'timezone' => $best[4],
            ];

        return $this->cache[$key] = $resolved;
    }

    /**
     * Filter options for the UI: one entry per anchor city, sorted by label.
     *
     * @return list<array{value: string, label: string, lat: float, lng: float}>
     */
    public function options(): array
    {
        $options = array_map(fn (array $c) => [
            'value' => $c[2],
            'label' => "{$c[2]}, {$c[3]}",
            'lat' => $c[0],
            'lng' => $c[1],
        ], $this->cities);

        usort($options, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $options;
    }

    /**
     * Bounding box around a named city's anchor. The seed jitter is ±0.5°, so a
     * slightly wider box reliably captures that city's events. Indexed lat/lng
     * range queries use this for the "filter by location" requirement.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}|null
     */
    public function boundingBox(string $city, float $padding = 0.6): ?array
    {
        foreach ($this->cities as $c) {
            if ($c[2] === $city) {
                return [
                    'min_lat' => $c[0] - $padding,
                    'max_lat' => $c[0] + $padding,
                    'min_lng' => $c[1] - $padding,
                    'max_lng' => $c[1] + $padding,
                ];
            }
        }

        return null;
    }
}
