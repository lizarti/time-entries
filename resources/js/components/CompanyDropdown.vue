<template>
    <AppCombobox
        :model-value="selectValue"
        :options="options"
        placeholder="All companies"
        search-placeholder="Search companies..."
        @update:model-value="onSelect"
    />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import AppCombobox from '@/components/AppCombobox.vue';
import { useCompanies } from '@/composables/useCompanies';
import { useSelectedCompany } from '@/composables/useSelectedCompany';
import type { Company } from '@/types/api';

const { companies } = useCompanies();
const { selectedCompany, setSelectedCompany } = useSelectedCompany();

const options = computed(() => [
    { value: 'all', label: 'All companies' },
    ...companies.value.map((c: Company) => ({ value: String(c.id), label: c.name })),
]);

const selectValue = computed(() =>
    selectedCompany.value ? String(selectedCompany.value.id) : 'all',
);

function onSelect(value: string): void {
    if (value === 'all') {
        setSelectedCompany(null);
        return;
    }
    const company = companies.value.find((c: Company) => String(c.id) === value) ?? null;
    setSelectedCompany(company);
}
</script>
