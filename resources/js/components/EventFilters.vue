<script setup lang="ts">
import { RotateCcw } from '@lucide/vue';
import { reactive, watch } from 'vue';
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
            <label class="text-xs font-medium text-muted-foreground" for="city"
                >City</label
            >
            <select id="city" v-model="filters.city" :class="fieldClass">
                <option value="">All cities</option>
                <option v-for="c in cities" :key="c.value" :value="c.value">
                    {{ c.label }}
                </option>
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label
                class="text-xs font-medium text-muted-foreground"
                for="category"
                >Category</label
            >
            <select
                id="category"
                v-model="filters.category"
                :class="[fieldClass, 'capitalize']"
            >
                <option value="">All categories</option>
                <option
                    v-for="cat in categories"
                    :key="cat"
                    :value="cat"
                    class="capitalize"
                >
                    {{ cat }}
                </option>
            </select>
        </div>

        <Button variant="outline" size="sm" class="ml-auto" @click="reset">
            <RotateCcw class="size-3.5" />
            Reset
        </Button>
    </div>
</template>
