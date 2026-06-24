<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class MediumEventSeeder extends Seeder
{
    public function run(): void
    {
        $rows = (int) config('seeding.medium_rows', 25_000);

        $this->command->info("Seeding a medium dataset with {$rows} events...");

        Config::set('seeding.rows', $rows);

        $this->call(EventSeeder::class);
    }
}
