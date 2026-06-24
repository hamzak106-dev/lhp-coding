<script setup lang="ts">
import { MapPin, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import type { CityOption } from '@/types/events';

const props = defineProps<{
    modelValue: string;
    cities: CityOption[];
    placeholder?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [string] }>();

const query = ref(
    props.cities.find((c) => c.value === props.modelValue)?.label ?? '',
);
const open = ref(false);
let blurTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.modelValue,
    (val) => {
        if (!val) {
            query.value = '';
        } else {
            const match = props.cities.find((c) => c.value === val);
            if (match) query.value = match.label;
        }
    },
);

const suggestions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.cities.slice(0, 8);
    return props.cities.filter((c) => c.label.toLowerCase().includes(q)).slice(0, 8);
});

function onInput(e: Event) {
    query.value = (e.target as HTMLInputElement).value;
    open.value = true;
    if (query.value === '') emit('update:modelValue', '');
}

function onFocus() {
    open.value = true;
}

function select(city: CityOption) {
    query.value = city.label;
    open.value = false;
    emit('update:modelValue', city.value);
}

function clear() {
    query.value = '';
    open.value = false;
    emit('update:modelValue', '');
}

function onBlur() {
    blurTimer = setTimeout(() => {
        open.value = false;
        // If input doesn't match any city, clear the filter value
        const match = props.cities.find(
            (c) => c.label.toLowerCase() === query.value.trim().toLowerCase(),
        );
        if (!match) {
            query.value = '';
            emit('update:modelValue', '');
        }
    }, 150);
}

function onOptionMousedown(city: CityOption) {
    if (blurTimer) clearTimeout(blurTimer);
    select(city);
}
</script>

<template>
    <div class="relative">
        <div class="relative flex items-center">
            <MapPin
                class="pointer-events-none absolute left-2.5 size-3.5 text-muted-foreground"
            />
            <input
                :value="query"
                type="text"
                autocomplete="off"
                :placeholder="placeholder ?? 'Search city…'"
                class="h-9 w-48 rounded-md border border-input bg-background pl-7 pr-7 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
            />
            <button
                v-if="query"
                type="button"
                tabindex="-1"
                class="absolute right-2 text-muted-foreground hover:text-foreground"
                @mousedown.prevent="clear"
            >
                <X class="size-3.5" />
            </button>
        </div>

        <ul
            v-if="open && suggestions.length"
            class="absolute z-50 mt-1 w-56 overflow-hidden rounded-md border bg-popover shadow-md"
        >
            <li
                v-for="city in suggestions"
                :key="city.value"
                class="cursor-pointer px-3 py-2 text-sm leading-snug hover:bg-accent"
                @mousedown="onOptionMousedown(city)"
            >
                {{ city.label }}
            </li>
        </ul>
    </div>
</template>
