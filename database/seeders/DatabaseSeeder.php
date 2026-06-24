<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Defaults to 1,250,000 events (≈2.5 GB; ~3s first listing load on a
        // laptop). Use MediumEventSeeder for a faster local demo dataset.
        $this->call(EventSeeder::class);
    }
}
