<template>
    <TableRow>
        <!-- Company -->
        <TableCell>
            <span
                v-if="lockedCompanyId !== null"
                class="text-sm text-muted-foreground px-1"
            >
                {{ lockedCompanyName }}
            </span>
            <Select
                v-else
                :model-value="row.company_id ? String(row.company_id) : ''"
                @update:model-value="onCompanyChange"
            >
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="Company" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="c in allCompanies"
                        :key="c.id"
                        :value="String(c.id)"
                    >
                        {{ c.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </TableCell>

        <!-- Date -->
        <TableCell>
            <Input
                type="date"
                :value="row.date"
                class="w-38"
                :class="{ 'border-destructive': fieldError('date') }"
                @input="emit('update:modelValue', { ...row, date: ($event.target as HTMLInputElement).value })"
            />
        </TableCell>

        <!-- Employee -->
        <TableCell>
            <Select
                :model-value="row.employee_id ? String(row.employee_id) : ''"
                :disabled="!effectiveCompanyId"
                @update:model-value="(v) => emit('update:modelValue', { ...row, employee_id: Number(v) })"
            >
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="Employee" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="e in availableEmployeeOptions"
                        :key="e.id"
                        :value="String(e.id)"
                    >
                        {{ e.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </TableCell>

        <!-- Project -->
        <TableCell>
            <Select
                :model-value="row.project_id ? String(row.project_id) : ''"
                :disabled="!effectiveCompanyId"
                @update:model-value="(v) => emit('update:modelValue', { ...row, project_id: Number(v) })"
            >
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="Project" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="p in availableProjectOptions"
                        :key="p.id"
                        :value="String(p.id)"
                    >
                        {{ p.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </TableCell>

        <!-- Task -->
        <TableCell>
            <Select
                :model-value="row.task_id ? String(row.task_id) : ''"
                :disabled="!effectiveCompanyId"
                @update:model-value="(v) => emit('update:modelValue', { ...row, task_id: Number(v) })"
            >
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="Task" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="t in availableTaskOptions"
                        :key="t.id"
                        :value="String(t.id)"
                    >
                        {{ t.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </TableCell>

        <!-- Hours -->
        <TableCell>
            <Input
                type="number"
                min="0.01"
                step="0.5"
                placeholder="0.00"
                :value="row.hours || ''"
                class="w-24"
                :class="{ 'border-destructive': fieldError('hours') }"
                @input="emit('update:modelValue', { ...row, hours: parseFloat(($event.target as HTMLInputElement).value) || 0 })"
            />
        </TableCell>

        <!-- Remove -->
        <TableCell>
            <Button variant="ghost" size="icon" @click="emit('remove')">
                <XIcon class="size-4" />
            </Button>
        </TableCell>
    </TableRow>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { TableCell, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useCompanies } from '@/composables/useCompanies';
import { useEmployees } from '@/composables/useEmployees';
import { useProjects } from '@/composables/useProjects';
import { useTasks } from '@/composables/useTasks';
import type { BulkInsertEntry, Company } from '@/types/api';

const props = defineProps<{
    modelValue: BulkInsertEntry;
    lockedCompanyId: number | null;
    errors: Record<string, string>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: BulkInsertEntry];
    'remove': [];
}>();

const row = computed(() => props.modelValue);

// ─── Effective company ID ──────────────────────────────────────────────────────
// When locked (global company selected), always use that.
// Otherwise, use the row's own company_id selection.
const effectiveCompanyId = computed<number | null>(() =>
    props.lockedCompanyId ?? (row.value.company_id || null),
);

// ─── Available options (reactive to effectiveCompanyId) ───────────────────────
const { availableEmployeeOptions, availableProjectOptions, availableTaskOptions } =
    (() => {
        const { employees: availableEmployeeOptions } = useEmployees(effectiveCompanyId);
        const { projects: availableProjectOptions } = useProjects(effectiveCompanyId);
        const { tasks: availableTaskOptions } = useTasks(effectiveCompanyId);
        return { availableEmployeeOptions, availableProjectOptions, availableTaskOptions };
    })();

// ─── Company change resets dependents ─────────────────────────────────────────
function onCompanyChange(value: string): void {
    emit('update:modelValue', {
        ...row.value,
        company_id: Number(value),
        employee_id: 0,
        project_id: 0,
        task_id: 0,
    });
}

// ─── Locked company display name ──────────────────────────────────────────────
const { companies: allCompanies } = useCompanies();

const lockedCompanyName = computed<string>(() => {
    if (props.lockedCompanyId === null) return '';
    const match = allCompanies.value.find((c: Company) => c.id === props.lockedCompanyId);
    return match?.name ?? String(props.lockedCompanyId);
});

// ─── Error helper ─────────────────────────────────────────────────────────────
function fieldError(field: string): string | undefined {
    return props.errors[field];
}
</script>
