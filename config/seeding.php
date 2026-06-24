<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seed dataset size
    |--------------------------------------------------------------------------
    |
    | Number of events EventSeeder generates. Defaults to the full 1.25M; lower
    | it while iterating, e.g. SEED_ROWS=25000 php artisan db:seed.
    |
    */
    'rows' => (int) env('SEED_ROWS', 1_250_000),
];
