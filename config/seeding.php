<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seed dataset size
    |--------------------------------------------------------------------------
    |
    | Number of events EventSeeder generates. Defaults to the full 1.25M. Use
    | MediumEventSeeder for a practical local demo seed.
    |
    */
    'rows' => (int) env('SEED_ROWS', 1_250_000),

    'medium_rows' => (int) env('MEDIUM_SEED_ROWS', 25_000),

    'event_image_urls' => [
        'concert' => [
            'events/concert-1.jpg',
            'events/concert-2.jpg',
            'events/concert-3.jpg',
        ],
        'conference' => [
            'events/conference-1.jpg',
            'events/conference-2.jpg',
            'events/conference-3.jpg',
        ],
        'meetup' => [
            'events/meetup-1.jpg',
            'events/meetup-2.jpg',
            'events/meetup-3.jpg',
        ],
        'workshop' => [
            'events/workshop-1.jpg',
            'events/workshop-2.jpg',
            'events/workshop-3.jpg',
        ],
        'festival' => [
            'events/festival-1.jpg',
            'events/festival-2.jpg',
            'events/festival-3.jpg',
        ],
        'sports' => [
            'events/sports-1.jpg',
            'events/sports-2.jpg',
            'events/sports-3.jpg',
        ],
        'networking' => [
            'events/networking-1.jpg',
            'events/networking-2.jpg',
            'events/networking-3.jpg',
        ],
        'exhibition' => [
            'events/exhibition-1.jpg',
            'events/exhibition-2.jpg',
            'events/exhibition-3.jpg',
        ],
    ],
];
