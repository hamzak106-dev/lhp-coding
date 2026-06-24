# Design decisions & notes

A short tour of the choices behind this implementation and *why* they were made.
The brief was deliberately open-ended ("your call"), so this captures the trade-offs.

## The dataset shapes everything

The seed is ~1.25M events with their human-facing fields (`name`, `description`,
`venue`, `schedule`, `pricing`) packed into a JSON `payload` column, plus a unix
`created_time` (the event start), `type`, `status`, and `latitude`/`longitude`.
I built **for the data as it is** rather than reshaping it: no destructive
migration of `payload` into columns, no per-row backfill across a million rows.
That constraint drove most of what follows.

## Performance & indexes (the headline)

Out of the box only `status` was indexed, yet every listing does
`ORDER BY created_time` — a filesort over 1.25M rows. I added composite indexes
that let MySQL satisfy filter **and** sort from one index:

| Index | Serves |
| --- | --- |
| `(status, created_time)` | the original listing + grid (status filter, time order) |
| `(type, created_time)` | category filter + time order (card grid) |
| `created_time` | date-range scans with no status/type filter |
| `(latitude, longitude)` | bounding-box queries for the map |

See `EXPLAIN.md` for `EXPLAIN` output proving index usage (no `filesort`, no full
scan) against the full dataset.

Other scale choices:

- **Lean API payloads.** `EventTransformer::forList()` returns only the dozen
  fields a card/marker needs — never the ~1.5 KB `payload` (with its padding
  `notes`). List responses stay small; the existing `bytes`/`ms` counters stay honest.
- **The map can't return a million markers.** `/events/map` takes the viewport
  bounding box + filters, hits the `(latitude, longitude)` index, and **caps at
  750** rows; the client clusters them (Leaflet.markercluster). Zooming in narrows
  the box and reveals more — the UI flags when a view is capped.
- **Reminders are driven from the small `attendees` table**, joined to events on
  the indexed `created_time`, and chunked — never a scan of all events.

## Images — dedicated table, reused files

`event_images` is a real relationship (`event_id` FK, `position`, `is_primary`,
composite index `(event_id, position)`), seeded with **2 rows per event**. The
physical files are a small **committed pool** of category-themed placeholders in
`storage/app/public/events/` (served locally via `storage:link` — no hotlinking).
~2.5M DB rows reference ~16 files, so the relationship is genuine and end-to-end
while disk stays tiny. The card grid eager-loads only `primaryImage`.

> Trade-off considered: a deterministic accessor (UUID → image) avoids the rows
> entirely, but a real table better demonstrates relationships + indexing, which
> the role explicitly cares about.

## Addresses & timezones — offline, no external calls

Events only carry lat/lng. Reverse-geocoding 1.25M rows via an external API is
neither reliable nor allowed-in-spirit (the brief bans hotlinked image URLs and
wants it to work against the data as-is). The seeder jitters every event within
±0.5° of one of ~75 anchor cities, so I bundled those cities with names + IANA
timezones (`config/cities.php`) and resolve each event to its **nearest anchor**
(`LocationResolver`, O(75) + memoized). That yields both a human-readable
"Near {City}, {Country}" label and the correct timezone — fully offline and
deterministic.

**Timezones:** `created_time` is a unix UTC instant. Events are global, so the UI
shows the time **in the event's own timezone** (primary) and, when the viewer is
elsewhere, **also in their local timezone** (via the browser's `Intl`), so nobody
has to convert. Emails render the event-local time with its abbreviation (e.g. JST).

## Attendees & email

- `POST /events/{event}/attendees` is idempotent per `(event_id, email)` (unique
  constraint + `firstOrCreate`), queues a confirmation email, and flashes a toast.
- Two queued Markdown mailables: confirmation and reminder.
- `events:send-reminders` is **threshold-based**, not exact-time: it sends the 24h
  reminder for events starting within `(now, now+24h]` and the 3-day reminder for
  `(now+24h, now+72h]`, stamping `reminder_24h_sent_at` / `reminder_3d_sent_at` so
  it **never double-sends**. Scheduled **hourly** (`bootstrap/app.php`) with
  `withoutOverlapping()`, so a missed run self-heals on the next tick rather than
  silently dropping a reminder.

## The two visual pages

Two deliberately different mental models, sharing one `EventCard` /
`useEventDateTime` / `EventFilters` core:

1. **Card Grid (`/events-visual-1`)** — image-forward, responsive, infinite scroll
   (reusing the starter's `IntersectionObserver` pattern), staggered reveal +
   hover-lift animations, skeleton loaders. Filters: date range, city, category.
2. **Map (`/events-visual-2`)** — Leaflet + clustered markers over OpenStreetMap
   tiles, a side list synced to the viewport, marker popups linking to detail.
   Filters by viewport (location) + date range + category. The map directly
   exercises the lat/lng data and the "filter by location" requirement.

## Fixes to the provided scaffold

While working through it I also corrected pre-existing issues so the whole tree is
green: a real bug in `Events/Index.vue` (`@click="aplyFilters"` → undefined
function), the `from` date filter that the listing controller ignored, and a set
of PHPStan (level 7) findings in the seeder/models. The `event.payload` shape on
the detail page was intentionally replaced with a presentation-ready shape, and
its test updated to match.

## Working with AI (how this was built)

- **Spec first.** I read `CODING_TEST.md` + the codebase, then wrote a concrete
  plan (data layer → services → controllers → email → frontend → tests → docs) and
  executed it in that dependency order, verifying each layer before building on it.
- **Model choice.** Opus for the architecture/plan and the cross-cutting reasoning
  (index design, timezone/geocoding strategy, reminder semantics); it's worth the
  cost where a wrong call is expensive to unwind. Routine, well-scoped edits
  (boilerplate components, factories) are exactly where a faster model (Sonnet) fits
  — judgment about *which* model for *which* task is part of the workflow.
- **Verification over vibes.** Every layer was checked against the real DB/HTTP
  (tinker, `curl` the feeds, render the Inertia pages) and locked in with Pest
  tests, `pint`, `phpstan` (level 7), `eslint`, `prettier`, and `vue-tsc`.
- **Caveat handled locally:** this box lacks `pdo_sqlite`, so the suite (configured
  for sqlite in-memory) was verified against a throwaway MySQL test DB via env
  overrides — without touching the committed config, which stays standard.

## Intentionally out of scope

MongoDB and MCP servers are mentioned in the role but not the task; per "quality
over quantity" I kept the build focused on what `CODING_TEST.md` asked for.
