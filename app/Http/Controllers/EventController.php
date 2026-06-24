<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventTransformer;
use App\Services\LocationResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** Event categories (stored in the `type` column). */
    public const CATEGORIES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    public const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    public function __construct(
        private readonly EventTransformer $transformer,
        private readonly LocationResolver $locations,
    ) {}

    /** Original raw listing (kept; date filter + ordering now index-backed). */
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
            ],
            'statuses' => self::STATUSES,
        ]);
    }

    /** Visual 1 — card grid. */
    public function visualGrid(): Response
    {
        return Inertia::render('Events/VisualOne', $this->filterMetadata());
    }

    /** Visual 2 — interactive map. */
    public function visualMap(): Response
    {
        return Inertia::render('Events/VisualTwo', $this->filterMetadata());
    }

    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadListing($request);

        return response()->json([
            'data' => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => $stats,
        ]);
    }

    /**
     * JSON feed for the card grid: date range + city + category filters,
     * paginated, with only the primary image eager-loaded and a lean payload.
     */
    public function grid(Request $request): JsonResponse
    {
        $start = microtime(true);

        $query = Event::query()
            ->with('primaryImage')
            ->where('status', 'published');

        $this->applyFilters($query, $request);

        $events = $query->orderBy('created_time')
            ->paginate(24)
            ->withQueryString();

        $data = array_map(
            fn (Event $e) => $this->transformer->forList($e),
            $events->items(),
        );

        return response()->json([
            'data' => $data,
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => [
                'ms' => (int) round((microtime(true) - $start) * 1000),
                'bytes' => strlen((string) json_encode($data)),
            ],
        ]);
    }

    /**
     * JSON feed for the map: a viewport bounding box (+ date/category filters),
     * capped result set, served from the (latitude, longitude) index.
     */
    public function map(Request $request): JsonResponse
    {
        // Validate by hand and return JSON 422 directly: this is an XHR feed, and
        // the app's exception handler only auto-renders JSON for `api/*` paths.
        $validator = Validator::make($request->all(), [
            'north' => ['required', 'numeric'],
            'south' => ['required', 'numeric'],
            'east' => ['required', 'numeric'],
            'west' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'A bounding box is required.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $start = microtime(true);

        $query = Event::query()
            ->with('primaryImage')
            ->where('status', 'published')
            ->whereBetween('latitude', [$validated['south'], $validated['north']])
            ->whereBetween('longitude', [$validated['west'], $validated['east']]);

        $this->applyFilters($query, $request, applyCity: false);

        $events = $query->orderBy('created_time')->limit(750)->get();

        $data = $events->map(fn (Event $e) => $this->transformer->forList($e))->all();

        return response()->json([
            'data' => $data,
            'count' => count($data),
            'capped' => count($data) === 750,
            'stats' => [
                'ms' => (int) round((microtime(true) - $start) * 1000),
                'bytes' => strlen((string) json_encode($data)),
            ],
        ]);
    }

    public function show(Event $event): Response
    {
        $event->load('images')->loadCount('attendees');

        return Inertia::render('Events/Show', [
            'event' => $this->transformer->forDetail($event),
        ]);
    }

    /**
     * Shared date/city/category filtering for the grid and map feeds.
     *
     * @param  Builder<Event>  $query
     */
    private function applyFilters(Builder $query, Request $request, bool $applyCity = true): void
    {
        if ($request->filled('from')) {
            $query->where('created_time', '>=', strtotime((string) $request->input('from')));
        }

        if ($request->filled('to')) {
            // Include the whole "to" day.
            $query->where('created_time', '<=', strtotime((string) $request->input('to')) + 86399);
        }

        if ($request->filled('category') && in_array($request->input('category'), self::CATEGORIES, true)) {
            $query->where('type', $request->input('category'));
        }

        if ($applyCity && $request->filled('city')) {
            $box = $this->locations->boundingBox((string) $request->input('city'));
            if ($box !== null) {
                $query->whereBetween('latitude', [$box['min_lat'], $box['max_lat']])
                    ->whereBetween('longitude', [$box['min_lng'], $box['max_lng']]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function filterMetadata(): array
    {
        return [
            'categories' => self::CATEGORIES,
            'cities' => $this->locations->options(),
        ];
    }

    /**
     * @return array{0: LengthAwarePaginator<int, Event>, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $events = Event::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when(
                $request->filled('from'),
                fn ($q) => $q->where('created_time', '>=', strtotime((string) $request->input('from'))),
            )
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($events->items())),
        ];

        return [$events, $stats];
    }
}
