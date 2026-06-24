<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventImageSeeder extends Seeder
{
    private const CHUNK = 1000;

    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    public function run(): void
    {
        $this->command->info('Backfilling event images...');

        $now = now()->toDateTimeString();
        $done = 0;

        DB::table('events')
            ->select(['id', 'type'])
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($events) use ($now, &$done) {
                $ids = $events->pluck('id')->all();

                $existing = DB::table('event_images')
                    ->select(['event_id', 'position'])
                    ->whereIn('event_id', $ids)
                    ->get()
                    ->groupBy('event_id')
                    ->map(fn ($rows) => $rows->pluck('position')->map(fn ($position) => (int) $position)->all());

                $rows = [];

                foreach ($events as $event) {
                    $positions = $existing->get($event->id, []);
                    $type = in_array($event->type, self::TYPES, true) ? $event->type : 'concert';
                    [$primaryImage, $secondaryImage] = $this->imagePairFor($type);

                    if (! in_array(0, $positions, true)) {
                        $rows[] = [
                            'event_id' => $event->id,
                            'path' => $primaryImage,
                            'position' => 0,
                            'is_primary' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (! in_array(1, $positions, true)) {
                        $rows[] = [
                            'event_id' => $event->id,
                            'path' => $secondaryImage,
                            'position' => 1,
                            'is_primary' => false,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('event_images')->insert($rows);
                }

                $done += count($events);
                $this->command->getOutput()->writeln("  checked {$done} events");
            });

        $this->command->info('Event image backfill complete.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function imagePairFor(string $type): array
    {
        $pools = config('seeding.event_image_urls', []);
        $pool = array_values($pools[$type] ?? $pools['concert'] ?? ['events/concert-1.jpg', 'events/concert-2.jpg']);
        $primaryIndex = mt_rand(0, count($pool) - 1);
        $secondaryIndex = $primaryIndex;

        if (count($pool) > 1) {
            while ($secondaryIndex === $primaryIndex) {
                $secondaryIndex = mt_rand(0, count($pool) - 1);
            }
        }

        return [$pool[$primaryIndex], $pool[$secondaryIndex]];
    }
}
