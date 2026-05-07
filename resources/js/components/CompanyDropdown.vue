<template>
    <Select :model-value="selectValue" @update:model-value="onSelect">
        <SelectTrigger class="w-56">
            <SelectValue placeholder="All companies" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem value="all">All companies</SelectItem>
            <SelectItem
                v-for="company in companies"
                :key="company.id"
                :value="String(company.id)"
            >
                {{ company.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCompanies } from '@/composables/useCompanies';
import { useSelectedCompany } from '@/composables/useSelectedCompany';
import type { Company } from '@/types/api';

const { companies } = useCompanies();
const { selectedCompany, setSelectedCompany } = useSelectedCompany();

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
