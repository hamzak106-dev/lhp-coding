<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition']);
        $lat = fake()->latitude();
        $lng = fake()->longitude();
        $startsAt = fake()->numberBetween(strtotime('-1 year'), strtotime('+1 year'));
        $name = ucwords(rtrim(fake()->sentence(3), '.'));

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'status' => fake()->randomElement(['draft', 'published', 'cancelled', 'sold_out']),
            'created_time' => $startsAt,
            'latitude' => $lat,
            'longitude' => $lng,
            'payload' => [
                'name' => $name,
                'category' => $type,
                'description' => "Join us for {$name} — a {$type} you won't want to miss.",
                'venue' => ['name' => fake()->company(), 'capacity' => fake()->numberBetween(20, 50000)],
                'location' => ['lat' => $lat, 'lng' => $lng],
                'schedule' => ['starts_at' => $startsAt, 'ends_at' => $startsAt + 7200],
                'pricing' => ['currency' => 'USD', 'min_price' => fake()->randomFloat(2, 0, 250)],
            ],
        ];
    }

    /** Attach a primary + secondary image, mirroring the seeded shape. */
    public function withImages(): static
    {
        return $this->afterCreating(function (Event $event) {
            EventImage::factory()->for($event)->category($event->type, 0, true)->create();
            EventImage::factory()->for($event)->category($event->type, 1, false)->create();
        });
    }

    /** Start the event a given number of hours from now (handy for reminder tests). */
    public function startingInHours(int $hours): static
    {
        return $this->state(fn () => [
            'created_time' => now()->addHours($hours)->getTimestamp(),
        ]);
    }
}
