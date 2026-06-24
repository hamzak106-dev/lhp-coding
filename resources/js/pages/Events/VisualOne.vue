<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarSearch } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import EventCard from '@/components/EventCard.vue';
import EventFilters from '@/components/EventFilters.vue';
import type { EventFilterValues } from '@/components/EventFilters.vue';
import { Skeleton } from '@/components/ui/skeleton';
import type { CityOption, EventListItem } from '@/types/events';

defineProps<{
    categories: string[];
    cities: CityOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Events · Card Grid', href: '/events-visual-1' },
        ],
    },
});

const today = new Date().toISOString().slice(0, 10);
const filters = ref<EventFilterValues>({
    from: today,
    to: '',
    city: '',
    category: '',
});

const rows = ref<EventListItem[]>([]);
const page = ref(0);
const lastPage = ref<number | null>(null);
const total = ref<number | null>(null);
const loading = ref(false);
const hasLoadedOnce = ref(false);
const loadedMs = ref(0);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;
let debounce: ReturnType<typeof setTimeout> | null = null;

const hasMore = computed(
    () => lastPage.value === null || page.value < lastPage.value,
);

async function loadMore() {
    if (loading.value || !hasMore.value) {
        return;
    }

    loading.value = true;

    const params = new URLSearchParams({ page: String(page.value + 1) });

    if (filters.value.from) {
        params.set('from', filters.value.from);
    }

    if (filters.value.to) {
        params.set('to', filters.value.to);
    }

    if (filters.value.city) {
        params.set('city', filters.value.city);
    }

    if (filters.value.category) {
        params.set('category', filters.value.category);
    }

    try {
        const response = await fetch(`/events/grid?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        rows.value.push(...payload.data);
        page.value = payload.current_page;
        lastPage.value = payload.last_page;
        total.value = payload.total;
        loadedMs.value += payload.stats.ms;
        hasLoadedOnce.value = true;
    } finally {
        loading.value = false;
    }
}

function reload() {
    rows.value = [];
    page.value = 0;
    lastPage.value = null;
    total.value = null;
    hasLoadedOnce.value = false;
    loadedMs.value = 0;
    loadMore();
}

watch(
    filters,
    () => {
        if (debounce) {
            clearTimeout(debounce);
        }

        debounce = setTimeout(reload, 300);
    },
    { deep: true },
);

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '600px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    loadMore();
});

onBeforeUnmount(() => {
    observer?.disconnect();

    if (debounce) {
        clearTimeout(debounce);
    }
});
</script>

<template>
    <Head title="Events · Card Grid" />

    <div class="flex flex-col gap-5 p-4">
        <header class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Discover events
            </h1>
            <p class="text-sm text-muted-foreground">
                {{
                    total !== null
                        ? `${total.toLocaleString()} upcoming events`
                        : 'Loading events…'
                }}
                <span v-if="hasLoadedOnce" class="text-muted-foreground/60"
                    >· {{ (loadedMs / 1000).toFixed(1) }}s</span
                >
            </p>
        </header>

        <EventFilters
            v-model="filters"
            :categories="categories"
            :cities="cities"
        />

        <div
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="(event, i) in rows"
                :key="event.id"
                class="animate-in duration-500 fill-mode-both fade-in slide-in-from-bottom-3"
                :style="{ animationDelay: `${(i % 24) * 30}ms` }"
            >
                <EventCard :event="event" />
            </div>

            <!-- Skeletons while loading -->
            <template v-if="loading">
                <div
                    v-for="n in 8"
                    :key="`s${n}`"
                    class="flex flex-col gap-3 rounded-xl border p-0"
                >
                    <Skeleton class="aspect-[4/3] w-full rounded-t-xl" />
                    <div class="flex flex-col gap-2 p-4">
                        <Skeleton class="h-4 w-3/4" />
                        <Skeleton class="h-3 w-1/2" />
                        <Skeleton class="h-3 w-2/3" />
                    </div>
                </div>
            </template>
        </div>

        <div
            v-if="!loading && hasLoadedOnce && rows.length === 0"
            class="flex flex-col items-center gap-2 rounded-xl border border-dashed py-16 text-center text-muted-foreground"
        >
            <CalendarSearch class="size-8" />
            <p>No events match your filters.</p>
        </div>

        <div ref="sentinel" class="h-px"></div>
    </div>
</template>
