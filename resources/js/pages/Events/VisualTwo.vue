<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import EventFilters from '@/components/EventFilters.vue';
import type { EventFilterValues } from '@/components/EventFilters.vue';
import { Badge } from '@/components/ui/badge';
import { formatEventTime, formatPrice } from '@/composables/useEventDateTime';
import type { CityOption, EventListItem } from '@/types/events';

const props = defineProps<{
    categories: string[];
    cities: CityOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events · Map', href: '/events-visual-2' }],
    },
});

// Fix Leaflet's default marker asset paths under Vite.
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const today = new Date().toISOString().slice(0, 10);
const filters = ref<EventFilterValues>({
    from: today,
    to: '',
    city: '',
    category: '',
});

const events = ref<EventListItem[]>([]);
const loading = ref(false);
const capped = ref(false);

const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let cluster: L.MarkerClusterGroup | null = null;
const markersById = new Map<string, L.Marker>();
let debounce: ReturnType<typeof setTimeout> | null = null;

function popupHtml(event: EventListItem): string {
    const time = formatEventTime(event.start_iso, event.timezone);
    const img = event.image_url
        ? `<img src="${event.image_url}" alt="" style="width:100%;height:96px;object-fit:cover;border-radius:8px;margin-bottom:6px" />`
        : '';

    return `
        <div style="width:200px">
            ${img}
            <strong style="display:block;font-size:13px;line-height:1.3">${event.title}</strong>
            <div style="font-size:11px;color:#666;margin:4px 0">${time.eventLabel}</div>
            <div style="font-size:11px;color:#666">${event.location_label} · ${formatPrice(event.price, event.currency)}</div>
            <a href="/events/${event.id}" style="display:inline-block;margin-top:6px;font-size:12px;color:#6d28d9;font-weight:600">View details →</a>
        </div>`;
}

async function fetchMarkers() {
    if (!map || !cluster) {
        return;
    }

    loading.value = true;

    const b = map.getBounds();
    const params = new URLSearchParams({
        north: String(b.getNorth()),
        south: String(b.getSouth()),
        east: String(b.getEast()),
        west: String(b.getWest()),
    });

    if (filters.value.from) {
        params.set('from', filters.value.from);
    }

    if (filters.value.to) {
        params.set('to', filters.value.to);
    }

    if (filters.value.category) {
        params.set('category', filters.value.category);
    }

    try {
        const res = await fetch(`/events/map?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await res.json();
        events.value = payload.data;
        capped.value = payload.capped;

        cluster.clearLayers();
        markersById.clear();

        for (const event of payload.data as EventListItem[]) {
            if (event.lat == null || event.lng == null) {
                continue;
            }

            const marker = L.marker([event.lat, event.lng]).bindPopup(
                popupHtml(event),
            );
            markersById.set(event.id, marker);
            cluster.addLayer(marker);
        }
    } finally {
        loading.value = false;
    }
}

function scheduleFetch() {
    if (debounce) {
        clearTimeout(debounce);
    }

    debounce = setTimeout(fetchMarkers, 300);
}

function focusEvent(event: EventListItem) {
    if (!map || event.lat == null || event.lng == null) {
        return;
    }

    map.setView([event.lat, event.lng], Math.max(map.getZoom(), 11));
    markersById.get(event.id)?.openPopup();
}

// When a city is picked, fly there; the viewport change refetches markers.
watch(
    () => filters.value.city,
    (city) => {
        const match = props.cities.find((c) => c.value === city);

        if (map && match) {
            map.setView([match.lat, match.lng], 11);
        }
    },
);

watch(
    () => [filters.value.from, filters.value.to, filters.value.category],
    scheduleFetch,
);

onMounted(() => {
    if (!mapEl.value) {
        return;
    }

    map = L.map(mapEl.value, { worldCopyJump: true }).setView([39, -30], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    cluster = L.markerClusterGroup({ chunkedLoading: true });
    map.addLayer(cluster);

    map.on('moveend', scheduleFetch);
    fetchMarkers();
});

onBeforeUnmount(() => {
    if (debounce) {
        clearTimeout(debounce);
    }

    map?.remove();
    map = null;
});

function openDetail(id: string) {
    router.visit(`/events/${id}`);
}
</script>

<template>
    <Head title="Events · Map" />

    <div class="flex flex-col gap-4 p-4">
        <header class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Events near you
            </h1>
            <p class="text-sm text-muted-foreground">
                Pan and zoom the map to explore events by area.
                <span v-if="capped" class="text-amber-600"
                    >Showing the first 750 in view — zoom in for more.</span
                >
            </p>
        </header>

        <EventFilters
            v-model="filters"
            :categories="categories"
            :cities="cities"
            hide-city
        />

        <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
            <div
                ref="mapEl"
                class="z-0 h-[42rem] w-full overflow-hidden rounded-xl border shadow-sm"
            ></div>

            <aside
                class="flex h-[42rem] flex-col overflow-hidden rounded-xl border"
            >
                <div
                    class="flex items-center justify-between border-b bg-muted/40 px-4 py-2.5"
                >
                    <span class="text-sm font-medium">In view</span>
                    <Badge variant="secondary">{{ events.length }}</Badge>
                </div>
                <ul class="flex-1 divide-y overflow-y-auto">
                    <li
                        v-for="event in events"
                        :key="event.id"
                        class="flex cursor-pointer gap-3 p-3 transition-colors hover:bg-accent"
                        @mouseenter="focusEvent(event)"
                        @click="openDetail(event.id)"
                    >
                        <img
                            v-if="event.image_url"
                            :src="event.image_url"
                            alt=""
                            loading="lazy"
                            class="size-14 shrink-0 rounded-md object-cover"
                        />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ event.title }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{
                                    formatEventTime(
                                        event.start_iso,
                                        event.timezone,
                                    ).eventLabel
                                }}
                            </p>
                            <p
                                class="flex items-center gap-1 truncate text-xs text-muted-foreground"
                            >
                                <MapPin class="size-3 shrink-0" />
                                {{ event.location_label }}
                            </p>
                        </div>
                    </li>
                    <li
                        v-if="!loading && events.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        No events in this area. Try zooming out or changing
                        filters.
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</template>
