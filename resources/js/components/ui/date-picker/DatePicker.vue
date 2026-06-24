<script setup lang="ts">
import { CalendarDate, getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { ChevronLeft, ChevronRight, CalendarIcon, X } from '@lucide/vue';
import {
    CalendarCell,
    CalendarCellTrigger,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHead,
    CalendarGridRow,
    CalendarHeadCell,
    CalendarHeader,
    CalendarHeading,
    CalendarNext,
    CalendarPrev,
    CalendarRoot,
} from 'reka-ui';
import { onClickOutside } from '@vueuse/core';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        placeholder?: string;
        id?: string;
        class?: string;
    }>(),
    { placeholder: 'Pick a date' },
);

const emit = defineEmits<{ 'update:modelValue': [string] }>();

const open = ref(false);
const container = ref<HTMLElement | null>(null);

onClickOutside(container, () => {
    open.value = false;
});

const calValue = computed<CalendarDate | undefined>(() => {
    if (!props.modelValue) return undefined;
    try {
        return parseDate(props.modelValue) as CalendarDate;
    } catch {
        return undefined;
    }
});

const defaultPlaceholder = today(getLocalTimeZone()) as CalendarDate;

const displayValue = computed(() => {
    if (!props.modelValue) return null;
    try {
        const d = parseDate(props.modelValue);
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(new Date(d.year, d.month - 1, d.day));
    } catch {
        return null;
    }
});

function onSelect(val: CalendarDate | undefined) {
    emit('update:modelValue', val ? val.toString() : '');
    open.value = false;
}

function clear(e: Event) {
    e.stopPropagation();
    emit('update:modelValue', '');
}
</script>

<template>
    <div ref="container" class="relative">
        <button
            :id="id"
            type="button"
            :class="
                cn(
                    'flex h-9 min-w-[160px] items-center gap-2 rounded-md border border-input bg-background px-3 text-sm shadow-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                    props.class,
                )
            "
            @click="open = !open"
        >
            <CalendarIcon class="size-3.5 shrink-0 text-muted-foreground" />
            <span :class="displayValue ? 'text-foreground' : 'text-muted-foreground'">
                {{ displayValue ?? placeholder }}
            </span>
            <button
                v-if="displayValue"
                class="-mr-1 ml-auto rounded p-0.5 text-muted-foreground hover:text-foreground"
                type="button"
                @click="clear"
            >
                <X class="size-3" />
            </button>
        </button>

        <Transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="open"
                class="absolute left-0 top-full z-50 mt-1.5 rounded-xl border bg-popover p-3 shadow-lg"
            >
                <CalendarRoot
                    :model-value="calValue"
                    :default-placeholder="defaultPlaceholder"
                    weekday-format="short"
                    fixed-weeks
                    @update:model-value="onSelect"
                >
                    <template #default="{ grid, weekDays }">
                        <CalendarHeader class="mb-2 flex items-center justify-between px-1">
                            <CalendarPrev
                                class="flex size-7 items-center justify-center rounded-md hover:bg-accent disabled:opacity-40"
                            >
                                <ChevronLeft class="size-4" />
                            </CalendarPrev>
                            <CalendarHeading class="text-sm font-semibold" />
                            <CalendarNext
                                class="flex size-7 items-center justify-center rounded-md hover:bg-accent disabled:opacity-40"
                            >
                                <ChevronRight class="size-4" />
                            </CalendarNext>
                        </CalendarHeader>

                        <div v-for="month in grid" :key="month.value.month">
                            <CalendarGrid class="w-full border-collapse">
                                <CalendarGridHead>
                                    <CalendarGridRow class="mb-1 flex">
                                        <CalendarHeadCell
                                            v-for="day in weekDays"
                                            :key="day"
                                            class="w-9 text-center text-xs font-medium text-muted-foreground"
                                        >
                                            {{ day }}
                                        </CalendarHeadCell>
                                    </CalendarGridRow>
                                </CalendarGridHead>
                                <CalendarGridBody>
                                    <CalendarGridRow
                                        v-for="(week, wi) in month.rows"
                                        :key="wi"
                                        class="flex"
                                    >
                                        <CalendarCell
                                            v-for="day in week"
                                            :key="day.toString()"
                                            :date="day"
                                            class="p-0"
                                        >
                                            <CalendarCellTrigger
                                                :day="day"
                                                :month="month.value"
                                                class="flex size-9 items-center justify-center rounded-md text-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring data-[outside-month]:text-muted-foreground/40 data-[selected]:bg-primary data-[selected]:text-primary-foreground data-[today]:font-semibold data-[today]:text-primary data-[selected]:data-[today]:text-primary-foreground"
                                            />
                                        </CalendarCell>
                                    </CalendarGridRow>
                                </CalendarGridBody>
                            </CalendarGrid>
                        </div>
                    </template>
                </CalendarRoot>
            </div>
        </Transition>
    </div>
</template>
