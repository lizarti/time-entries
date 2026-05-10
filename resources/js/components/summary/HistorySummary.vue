<template>
    <div>

        <!-- Toggle header -->
        <button
            type="button"
            class="flex w-full items-center justify-between py-1 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            @click="expanded = !expanded"
        >
            <span>Summary</span>
            <ChevronDownIcon
                class="h-4 w-4 transition-transform duration-200"
                :class="{ 'rotate-180': expanded }"
            />
        </button>

        <!-- Cards -->
        <div v-show="expanded" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
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
import { computed, ref } from 'vue';
import { ChevronDownIcon } from 'lucide-vue-next';
import SummaryCard from '@/components/summary/SummaryCard.vue';
import type { SummaryRow } from '@/types/api';

const props = defineProps<{
    byEmployee: SummaryRow[];
    byProject:  SummaryRow[];
    byCompany:  SummaryRow[];
    loading:    boolean;
}>();

const expanded = ref(true);

const cards = computed(() => [
    { title: 'By Employee', rows: props.byEmployee },
    { title: 'By Project',  rows: props.byProject  },
    { title: 'By Company',  rows: props.byCompany  },
]);
</script>
