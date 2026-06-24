# Local Runbook

This is the quick operational guide for running the event visuals app locally.

## Fresh database

Use the medium seed for local review. It creates events with city-anchored
locations and two local image rows per event.

```bash
php artisan migrate:fresh
php artisan storage:link --force
php artisan db:seed --class=MediumEventSeeder
```

To use a smaller local dataset:

```bash
php artisan migrate:fresh
php artisan storage:link --force
MEDIUM_SEED_ROWS=5000 php artisan db:seed --class=MediumEventSeeder
```

To seed the full dataset:

```bash
php artisan migrate:fresh --seed
php artisan storage:link --force
```

## Backfill Images Only

If events already exist but images are missing or broken, rebuild the storage
symlink and backfill image rows:

```bash
php artisan storage:link --force
php artisan db:seed --class=EventImageSeeder
```

Check one image directly in the browser. Event payloads use the Laravel-served
`/event-images/...` route, so S3 is not required and Apache does not need to
serve the storage symlink directly.

```text
http://lhp-coding.local/event-images/events/concert-1.jpg
```

## Local Domain

For the Apache vhost used during this task, `.env` should contain:

```env
APP_URL=http://lhp-coding.local
```

After changing `.env`, clear config:

```bash
php artisan config:clear
```

## Vite

For development, start Vite after clearing any stale hot file:

```bash
rm -f public/hot
npm run dev
```

Then open:

```text
http://lhp-coding.local/events-visual-1
http://lhp-coding.local/events-visual-2
```

For production-style assets:

```bash
npm run build
```

## Quality Checks

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --debug
npm run types:check
npm run build
```

The Pest feature tests use SQLite in memory. If `pdo_sqlite` is not installed,
they will fail with `could not find driver` even when the app itself works with
MySQL.
