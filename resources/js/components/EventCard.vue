<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { formatEventTime, formatPrice } from '@/composables/useEventDateTime';
import { eventImageUrl } from '@/lib/eventImages';
import type { EventListItem } from '@/types/events';

const props = defineProps<{ event: EventListItem }>();

const time = computed(() =>
    formatEventTime(props.event.start_iso, props.event.timezone),
);
const price = computed(() =>
    formatPrice(props.event.price, props.event.currency),
);
const imageUrl = computed(() => eventImageUrl(props.event.image_url));
</script>

<template>
    <Link
        :href="`/events/${event.id}`"
        class="group flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <div class="relative aspect-[4/3] overflow-hidden bg-muted">
            <img
                v-if="imageUrl"
                :src="imageUrl"
                :alt="event.title"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <!-- Date chip -->
            <div
                class="absolute top-3 left-3 flex flex-col items-center rounded-lg bg-background/90 px-2.5 py-1 text-center shadow-sm backdrop-blur"
            >
                <span class="text-sm leading-none font-bold">{{
                    time.day
                }}</span>
                <span
                    class="text-[10px] tracking-wide text-muted-foreground uppercase"
                    >{{ time.month }}</span
                >
            </div>
            <Badge class="absolute top-3 right-3 capitalize">{{
                event.category
            }}</Badge>
            <div
                class="absolute right-3 bottom-3 rounded-md bg-background/90 px-2 py-0.5 text-xs font-semibold shadow-sm backdrop-blur"
            >
                {{ price }}
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-2 p-4">
            <h3
                class="line-clamp-2 leading-snug font-semibold group-hover:text-primary"
            >
                {{ event.title }}
            </h3>
            <div
                class="mt-auto flex flex-col gap-1 text-sm text-muted-foreground"
            >
                <span class="flex items-center gap-1.5">
                    <CalendarDays class="size-3.5 shrink-0" />
                    <span class="truncate">{{ time.eventLabel }}</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <MapPin class="size-3.5 shrink-0" />
                    <span class="truncate">{{ event.location_label }}</span>
                </span>
            </div>
        </div>
    </Link>
</template>
