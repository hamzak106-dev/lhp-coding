# Query plans & index evidence

`EXPLAIN` output for the hot queries, captured against the seeded MySQL dataset.
The goal of the added indexes (`database/migrations/2026_06_24_000001_add_indexes_to_events_table.php`)
is to satisfy each query's **filter + `ORDER BY created_time` + `LIMIT`** from a
single index — i.e. **no `filesort`, no full table scan**.

> Reproduce: `mysql -uroot -ppassword lhp_coding -e "EXPLAIN <query>\G"`.
> `rows` is the optimizer's pre-`LIMIT` estimate; the `LIMIT` short-circuits the
> actual scan once enough matching rows are found in index order.

## 1. Listing — the original filesort, now fixed

```sql
SELECT id FROM events WHERE status = 'published' ORDER BY created_time DESC LIMIT 50;
```
```
type:  ref
key:   events_status_created_time_index
Extra: Backward index scan; Using index
```
Before the migration this filtered on the lone `status` index and **filesorted**
~700k rows by `created_time` on every page load. Now it's a covering, backward
index scan — ordering comes for free from `(status, created_time)`.

## 2. Card grid — status + category + date range, ordered

```sql
SELECT id FROM events
WHERE status='published' AND type='concert' AND created_time >= :from
ORDER BY created_time LIMIT 24;
```
```
type:  range
key:   events_status_created_time_index
Extra: Using index condition; Using where
```
The optimizer walks `(status, created_time)` in order (no filesort), applies the
`created_time` range via index condition, and filters `type` — terminating at the
`LIMIT`. `(type, created_time)` is also available and is preferred for
category-first queries that don't pin `status`.

## 3. Map — bounding box + date

```sql
SELECT id FROM events
WHERE status='published'
  AND latitude BETWEEN 40.1 AND 41.3 AND longitude BETWEEN -74.7 AND -73.4
  AND created_time >= :from
ORDER BY created_time LIMIT 750;
```
```
type:  range
possible_keys: …events_lat_lng_index
key:   events_status_created_time_index
Extra: Using index condition; Using where
```
With a date filter present, the optimizer prefers `(status, created_time)` so the
`ORDER BY … LIMIT 750` is served in index order (no filesort), filtering the bbox
as it goes. `events_lat_lng_index` stays available and is chosen for
bbox-dominant queries without a tight date constraint. Either way the endpoint
**caps at 750 rows** and the client clusters them, so the map never materialises
a million markers.

## 4. Date-range only

```sql
SELECT id FROM events WHERE created_time >= :from ORDER BY created_time LIMIT 24;
```
```
type:  range
key:   events_created_time_index
Extra: Using where; Using index
```
Covering index scan in `created_time` order — no filesort.

## Why these indexes (and not more)

- `(status, created_time)` and `(type, created_time)` mirror the two filter-then-sort
  access patterns (status-first listing/grid; category-first browsing).
- `created_time` alone covers date-range queries with no other predicate.
- `(latitude, longitude)` backs geographic bounding-box lookups.

I deliberately stopped there. Each index is paid for on write — and the seed
inserts ~1.25M events **plus ~2.5M `event_images`** — so adding wider composites
(e.g. `(status, type, created_time)`) wasn't worth the write cost given the
`LIMIT`-bounded reads already avoid filesort.
