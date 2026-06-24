<script setup lang="ts">
import { ChevronDown, RotateCcw } from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import CityAutocomplete from '@/components/CityAutocomplete.vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import type { CityOption } from '@/types/events';

export interface EventFilterValues {
    from: string;
    to: string;
    city: string;
    category: string;
}

const props = defineProps<{
    categories: string[];
    cities: CityOption[];
    modelValue: EventFilterValues;
    /** Hide the city filter (the map filters location by viewport instead). */
    hideCity?: boolean;
}>();

const emit = defineEmits<{ 'update:modelValue': [EventFilterValues] }>();

const filters = reactive<EventFilterValues>({ ...props.modelValue });

watch(filters, (value) => emit('update:modelValue', { ...value }), {
    deep: true,
});

function reset() {
    filters.from = '';
    filters.to = '';
    filters.city = '';
    filters.category = '';
}

const fieldClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

const categoryOpen = ref(false);

function selectCategory(value: string) {
    filters.category = value;
    categoryOpen.value = false;
}

const categoryLabel = (cat: string) =>
    cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : 'All categories';
</script>

<template>
    <div class="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-3">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground" for="from"
                >From</label
            >
            <DatePicker id="from" v-model="filters.from" placeholder="Start date" />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground" for="to"
                >To</label
            >
            <DatePicker id="to" v-model="filters.to" placeholder="End date" />
        </div>

        <div v-if="!hideCity" class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">City</label>
            <CityAutocomplete
                v-model="filters.city"
                :cities="cities"
                placeholder="All cities"
            />
        </div>

        <div class="relative flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Category</label>
            <button
                type="button"
                class="flex h-9 w-40 items-center justify-between gap-2 rounded-md border border-input bg-background px-3 text-sm shadow-sm hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                @click="categoryOpen = !categoryOpen"
                @blur="categoryOpen = false"
            >
                <span class="capitalize">{{ categoryLabel(filters.category) }}</span>
                <ChevronDown
                    class="size-3.5 shrink-0 text-muted-foreground transition-transform"
                    :class="{ 'rotate-180': categoryOpen }"
                />
            </button>

            <ul
                v-if="categoryOpen"
                class="absolute top-full z-50 mt-1 w-40 overflow-hidden rounded-md border bg-popover shadow-md"
            >
                <li
                    class="cursor-pointer px-3 py-2 text-sm hover:bg-accent"
                    :class="{ 'font-medium': !filters.category }"
                    @mousedown.prevent="selectCategory('')"
                >
                    All categories
                </li>
                <li
                    v-for="cat in categories"
                    :key="cat"
                    class="cursor-pointer px-3 py-2 text-sm capitalize hover:bg-accent"
                    :class="{ 'font-medium': filters.category === cat }"
                    @mousedown.prevent="selectCategory(cat)"
                >
                    {{ cat }}
                </li>
            </ul>
        </div>

        <Button variant="outline" size="sm" class="ml-auto cursor-pointer" @click="reset">
            <RotateCcw class="size-3.5" />
            Reset
        </Button>
    </div>
</template>
