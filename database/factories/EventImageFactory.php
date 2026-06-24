<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventImage>
 */
class EventImageFactory extends Factory
{
    protected $model = EventImage::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'path' => 'events/concert-1.jpg',
            'position' => 0,
            'is_primary' => true,
        ];
    }

    public function secondary(int $position = 1): static
    {
        return $this->state(fn () => [
            'path' => 'events/concert-2.jpg',
            'position' => $position,
            'is_primary' => false,
        ]);
    }
}
