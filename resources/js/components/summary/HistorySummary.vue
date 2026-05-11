<template>
    <div>

        <h4 class="text-2xl font-semibold">Summary</h4>

        <!-- Cards -->
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <SummaryCard
                v-for="card in cards"
                :key="card.title"
                :title="card.title"
                :rows="card.rows"
                :loading="loading"
            />
        </div>

    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SummaryCard from '@/components/summary/SummaryCard.vue';
import type { SummaryRow } from '@/types/api';

const props = defineProps<{
    byEmployee: SummaryRow[];
    byProject:  SummaryRow[];
    byCompany:  SummaryRow[];
    loading:    boolean;
}>();

const cards = computed(() => [
    { title: 'By Employee', rows: props.byEmployee },
    { title: 'By Project',  rows: props.byProject  },
    { title: 'By Company',  rows: props.byCompany  },
]);
</script>
