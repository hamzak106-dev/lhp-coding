<?php

namespace App\Models;

use App\Services\LocationResolver;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EventImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('position');
    }

    /** @return HasOne<EventImage, $this> */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(EventImage::class)->where('is_primary', true);
    }

    /** @return HasMany<Attendee, $this> */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    /*
     * Human-friendly accessors. Title/description/venue/price live inside the
     * JSON `payload`; these are used by the detail page (the list/map shapes are
     * built by EventTransformer to keep their byte size small at scale).
     */

    public function title(): string
    {
        return $this->payload['name'] ?? 'Untitled event';
    }

    public function description(): ?string
    {
        return $this->payload['description'] ?? null;
    }

    /**
     * Event start. `created_time` is a unix timestamp (UTC) and is the source
     * of truth; we fall back to the payload schedule if it is ever missing.
     */
    public function startsAt(): ?CarbonImmutable
    {
        $ts = $this->created_time ?? ($this->payload['schedule']['starts_at'] ?? null);

        return $ts !== null ? CarbonImmutable::createFromTimestampUTC((int) $ts) : null;
    }

    public function endsAt(): ?CarbonImmutable
    {
        $ts = $this->payload['schedule']['ends_at'] ?? null;

        return $ts !== null ? CarbonImmutable::createFromTimestampUTC((int) $ts) : null;
    }

    public function price(): float
    {
        return (float) ($this->payload['pricing']['min_price'] ?? 0);
    }

    public function currency(): string
    {
        return $this->payload['pricing']['currency'] ?? 'USD';
    }

    public function venueName(): ?string
    {
        return $this->payload['venue']['name'] ?? null;
    }

    /** IANA timezone resolved offline from the event's coordinates. */
    public function timezone(): string
    {
        return app(LocationResolver::class)->resolve($this->latitude, $this->longitude)['timezone'];
    }

    /** Human-readable location, e.g. "Near Berlin, Germany". */
    public function locationLabel(): string
    {
        return app(LocationResolver::class)->resolve($this->latitude, $this->longitude)['label'];
    }

    /** Start time formatted in the event's own timezone (for emails/detail). */
    public function formattedStart(): string
    {
        $start = $this->startsAt();

        return $start === null
            ? 'Date to be announced'
            : $start->setTimezone($this->timezone())->format('l, M j, Y \a\t g:i A T');
    }
}
