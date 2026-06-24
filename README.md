# Event Visuals

A Laravel 13 + Inertia + Vue 3 + Tailwind v4 app for browsing a large, fully-seeded
events dataset (~1.25M rows) two distinct ways — an image-forward **card grid** and an
interactive **map** — with local images, human-readable addresses, timezone-aware times,
date/location filtering, and an attendee registration + email reminder system.

> Built as a solution to `CODING_TEST.md`. See **[DECISIONS.md](DECISIONS.md)** for the
> reasoning behind the design, and **[EXPLAIN.md](EXPLAIN.md)** for query/index evidence.

## Features

- **Two browsing experiences** — `/events-visual-1` (card grid, infinite scroll,
  animations) and `/events-visual-2` (Leaflet map, clustered markers, synced side list).
- **Images, end to end** — `event_images` table + relationship, 2 images per event,
  served locally from `storage/app/public/events` (no hotlinking).
- **Addresses from lat/lng** — offline nearest-city resolver → "Near {City}, {Country}".
- **Timezones** — event-local time shown alongside the viewer's local time.
- **Filtering** — by date range, city, and category (grid); by viewport + date + category (map).
- **Attendees & email** — register interest, get a queued confirmation email, and
  reminder emails **3 days** and **24 hours** before the event.
- **Built for scale** — composite indexes, lean API payloads, capped/clustered map.

## Requirements

- PHP 8.3+ (with `pdo_sqlite` for the test suite, `pdo_mysql` for the app DB)
- Node 20+, MySQL 8+

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
# point DB_* at your MySQL instance (defaults: db lhp_coding, root/password)

php artisan migrate
php artisan storage:link          # serve the local event images

# Seed the dataset. Full is ~1.25M events (a few minutes, ~2 GB):
php artisan db:seed
# …or scale down while iterating:
SEED_ROWS=25000 php artisan db:seed
```

## Running

```bash
composer run dev    # serve + queue worker + vite + log tail, all at once
```

or individually:

```bash
php artisan serve
php artisan queue:work          # processes confirmation/reminder emails
npm run dev
```

Then open <http://localhost:8000/events-visual-1> and `/events-visual-2`.

Mail uses the `log` driver by default, so confirmation/reminder emails land in
`storage/logs/laravel.log` — handy for the demo.

## Reminder emails

Reminders are queued by a scheduled command:

```bash
php artisan events:send-reminders     # run once (idempotent)
php artisan schedule:work             # run the hourly scheduler locally
```

In production, point cron at `php artisan schedule:run` every minute as usual.

## Quality checks

```bash
composer test          # Pint + PHPStan (level 7) + Pest
npm run lint:check && npm run format:check && npm run types:check
```

The test suite is configured for SQLite in-memory (standard). If your environment
lacks `pdo_sqlite`, you can run against a throwaway MySQL DB without changing config:

```bash
mysql -uroot -ppassword -e "CREATE DATABASE IF NOT EXISTS lhp_coding_test;"
DB_CONNECTION=mysql DB_DATABASE=lhp_coding_test php artisan test
```

## Key code

| Area | Path |
| --- | --- |
| Models | `app/Models/{Event,EventImage,Attendee}.php` |
| Offline geocoding | `app/Services/LocationResolver.php`, `config/cities.php` |
| API shaping | `app/Services/EventTransformer.php` |
| Controllers | `app/Http/Controllers/{EventController,AttendeeController}.php` |
| Email | `app/Mail/*`, `app/Console/Commands/SendEventReminders.php` |
| Indexes / schema | `database/migrations/2026_06_24_*` |
| Pages | `resources/js/pages/Events/{VisualOne,VisualTwo,Show}.vue` |
| Shared UI | `resources/js/components/{EventCard,EventFilters}.vue`, `composables/useEventDateTime.ts` |
