<?php

namespace App\Models;

use Database\Factories\EventImageFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property-read string $url
 * @property string $path
 */
class EventImage extends Model
{
    /** @use HasFactory<EventImageFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'position' => 'integer',
    ];

    protected $appends = ['url'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Public URL for the locally-stored image file.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(function (): string {
            $path = ltrim($this->path, '/');

            if (Str::startsWith($path, ['http://', 'https://'])) {
                return $this->path;
            }

            if (Str::startsWith($path, ['storage/http://', 'storage/https://'])) {
                return Str::after($path, 'storage/');
            }

            return sprintf('/storage/%s', $path);
        });
    }
}
