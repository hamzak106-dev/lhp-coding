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
            'path' => fake()->randomElement($this->imagePool('concert')),
            'position' => 0,
            'is_primary' => true,
        ];
    }

    public function secondary(int $position = 1): static
    {
        return $this->state(fn () => [
            'path' => fake()->randomElement($this->imagePool('concert')),
            'position' => $position,
            'is_primary' => false,
        ]);
    }

    public function category(string $category, int $position = 0, bool $primary = true): static
    {
        return $this->state(fn () => [
            'path' => fake()->randomElement($this->imagePool($category)),
            'position' => $position,
            'is_primary' => $primary,
        ]);
    }

    /**
     * @return non-empty-array<int, string>
     */
    private function imagePool(string $category): array
    {
        $pools = config('seeding.event_image_urls', []);
        $pool = $pools[$category] ?? $pools['concert'] ?? ['events/concert-1.jpg'];

        return array_values($pool);
    }
}
