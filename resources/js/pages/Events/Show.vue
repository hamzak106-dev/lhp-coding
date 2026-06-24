<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Clock,
    MapPin,
    Ticket,
    Users,
} from '@lucide/vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatEventTime, formatPrice } from '@/composables/useEventDateTime';

interface EventDetail {
    id: string;
    title: string;
    description: string | null;
    category: string;
    status: string;
    start_iso: string | null;
    end_iso: string | null;
    timezone: string;
    location_label: string;
    city: string;
    country: string;
    lat: number | null;
    lng: number | null;
    price: number;
    currency: string;
    venue: string | null;
    images: { url: string; is_primary: boolean }[];
    attendees_count: number;
}

const props = defineProps<{ event: EventDetail }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events', href: '/events-visual-1' }],
    },
});

const time = computed(() =>
    formatEventTime(props.event.start_iso, props.event.timezone),
);
const activeImage = ref(props.event.images[0]?.url ?? null);

const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const form = useForm({ name: '', email: '', status: 'attending' });

function register() {
    form.post(`/events/${props.event.id}/attendees`, {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email'),
    });
}

onMounted(() => {
    if (mapEl.value && props.event.lat != null && props.event.lng != null) {
        map = L.map(mapEl.value, {
            zoomControl: false,
            scrollWheelZoom: false,
            dragging: false,
        }).setView([props.event.lat, props.event.lng], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
        }).addTo(map);
        L.marker([props.event.lat, props.event.lng]).addTo(map);
    }
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});
</script>

<template>
    <Head :title="event.title" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <Link
            href="/events-visual-1"
            class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to events
        </Link>

        <!-- Gallery -->
        <div class="flex flex-col gap-3">
            <div
                class="aspect-[16/9] w-full overflow-hidden rounded-2xl border bg-muted"
            >
                <img
                    v-if="activeImage"
                    :src="activeImage"
                    :alt="event.title"
                    class="h-full w-full object-cover"
                />
            </div>
            <div v-if="event.images.length > 1" class="flex gap-3">
                <button
                    v-for="(img, i) in event.images"
                    :key="i"
                    type="button"
                    class="aspect-[4/3] w-28 overflow-hidden rounded-lg border-2 transition-all"
                    :class="
                        activeImage === img.url
                            ? 'border-primary'
                            : 'border-transparent opacity-70 hover:opacity-100'
                    "
                    @click="activeImage = img.url"
                >
                    <img
                        :src="img.url"
                        alt=""
                        class="h-full w-full object-cover"
                    />
                </button>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <!-- Main content -->
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge class="capitalize">{{ event.category }}</Badge>
                        <Badge variant="outline" class="capitalize">{{
                            event.status.replace('_', ' ')
                        }}</Badge>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ event.title }}
                    </h1>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-start gap-3 rounded-xl border p-4">
                        <CalendarDays class="mt-0.5 size-5 text-primary" />
                        <div>
                            <p class="text-sm font-medium">
                                {{ time.eventLabel }}
                            </p>
                            <p
                                v-if="time.localLabel"
                                class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Clock class="size-3" /> Your time:
                                {{ time.localLabel }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border p-4">
                        <MapPin class="mt-0.5 size-5 text-primary" />
                        <div>
                            <p class="text-sm font-medium">
                                {{ event.venue ?? 'Venue to be announced' }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ event.location_label }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="event.description" class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">About this event</h2>
                    <p class="leading-relaxed text-muted-foreground">
                        {{ event.description }}
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold">Location</h2>
                    <div
                        ref="mapEl"
                        class="z-0 h-64 w-full overflow-hidden rounded-xl border"
                    ></div>
                </div>
            </div>

            <!-- Registration sidebar -->
            <aside class="lg:sticky lg:top-4 lg:h-fit">
                <div
                    class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-baseline justify-between">
                        <span class="text-2xl font-bold">{{
                            formatPrice(event.price, event.currency)
                        }}</span>
                        <span
                            class="flex items-center gap-1 text-sm text-muted-foreground"
                        >
                            <Users class="size-4" />
                            {{ event.attendees_count.toLocaleString() }} going
                        </span>
                    </div>

                    <form
                        class="flex flex-col gap-3"
                        @submit.prevent="register"
                    >
                        <div class="flex flex-col gap-1.5">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                autocomplete="name"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full"
                        >
                            <Spinner v-if="form.processing" />
                            <Ticket v-else class="size-4" />
                            Register interest
                        </Button>
                        <p class="text-center text-xs text-muted-foreground">
                            We'll email you a confirmation and reminders before
                            the event.
                        </p>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</template>
