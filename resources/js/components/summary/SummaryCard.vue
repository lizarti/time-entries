<template>
    <div class="rounded-lg border bg-card p-3 text-card-foreground flex flex-col">

        <!-- Title -->
        <p class="mb-2.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ title }}
        </p>

        <!-- Loading state -->
        <div v-if="loading" class="space-y-2">
            <Skeleton v-for="n in 3" :key="n" class="h-3.5 w-full" />
        </div>

        <!-- Data rows -->
        <template v-else>
            <div class="max-h-52 space-y-px overflow-y-auto grow">
                <div
                    v-for="row in rows"
                    :key="row.label"
                    class="flex items-baseline justify-between gap-2 rounded px-1 py-0.5 text-sm hover:bg-muted/50"
                >
                    <span class="truncate">{{ row.label }}</span>
                    <span class="shrink-0 tabular-nums text-muted-foreground">
                        {{ formatHours(row.hours) }}
                    </span>
                </div>

                <p v-if="rows.length === 0" class="px-1 py-0.5 text-xs text-muted-foreground">
                    No data
                </p>
            </div>

            <!-- Total footer -->
            <div
                v-if="rows.length > 0"
                class="mt-2 flex items-baseline justify-between border-t pt-2 text-sm font-semibold"
            >
                <span>Total</span>
                <span class="tabular-nums">{{ formatHours(total) }}</span>
            </div>
        </template>

    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import type { SummaryRow } from '@/types/api';

const props = defineProps<{
    title:   string;
    rows:    SummaryRow[];
    loading: boolean;
}>();

const total = computed(() =>
    props.rows.reduce((sum, r) => sum + r.hours, 0),
);

// Strips trailing decimal zeros: 8.00 → "8 h", 8.50 → "8.5 h", 8.25 → "8.25 h"
function formatHours(hours: number): string {
    return `${parseFloat(hours.toFixed(2))} h`;
}
</script>
