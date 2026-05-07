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
            <AppCombobox
                v-else
                :model-value="row.company_id ? String(row.company_id) : ''"
                :options="companyOptions"
                placeholder="Company"
                search-placeholder="Search companies..."
                @update:model-value="onCompanyChange"
            />
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
            <AppCombobox
                :model-value="row.employee_id ? String(row.employee_id) : ''"
                :options="availableEmployeeOptions"
                :disabled="!effectiveCompanyId"
                placeholder="Employee"
                search-placeholder="Search employees..."
                @update:model-value="(v) => emit('update:modelValue', { ...row, employee_id: Number(v) })"
            />
        </TableCell>

        <!-- Project -->
        <TableCell>
            <AppCombobox
                :model-value="row.project_id ? String(row.project_id) : ''"
                :options="availableProjectOptions"
                :disabled="!effectiveCompanyId"
                placeholder="Project"
                search-placeholder="Search projects..."
                @update:model-value="(v) => emit('update:modelValue', { ...row, project_id: Number(v) })"
            />
        </TableCell>

        <!-- Task -->
        <TableCell>
            <AppCombobox
                :model-value="row.task_id ? String(row.task_id) : ''"
                :options="availableTaskOptions"
                :disabled="!effectiveCompanyId"
                placeholder="Task"
                search-placeholder="Search tasks..."
                @update:model-value="(v) => emit('update:modelValue', { ...row, task_id: Number(v) })"
            />
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
import { computed } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import AppCombobox from '@/components/AppCombobox.vue';
import { useCompanies } from '@/composables/useCompanies';
import { useEmployees } from '@/composables/useEmployees';
import { useProjects } from '@/composables/useProjects';
import { useTasks } from '@/composables/useTasks';
import type { BulkInsertEntry, Company, Employee, Project, Task } from '@/types/api';

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
const effectiveCompanyId = computed<number | null>(() =>
    props.lockedCompanyId ?? (row.value.company_id || null),
);

// ─── Available options ────────────────────────────────────────────────────────
const { employees } = useEmployees(effectiveCompanyId);
const { projects } = useProjects(effectiveCompanyId);
const { tasks }    = useTasks(effectiveCompanyId);

const availableEmployeeOptions = computed(() =>
    employees.value.map((e: Employee) => ({ value: String(e.id), label: e.name })),
);
const availableProjectOptions = computed(() =>
    projects.value.map((p: Project) => ({ value: String(p.id), label: p.name })),
);
const availableTaskOptions = computed(() =>
    tasks.value.map((t: Task) => ({ value: String(t.id), label: t.name })),
);

// ─── Company options (for unlocked row) ───────────────────────────────────────
const { companies: allCompanies } = useCompanies();

const companyOptions = computed(() =>
    allCompanies.value.map((c: Company) => ({ value: String(c.id), label: c.name })),
);

// ─── Company change resets dependents ─────────────────────────────────────────
function onCompanyChange(value: string): void {
    emit('update:modelValue', {
        ...row.value,
        company_id:  Number(value),
        employee_id: 0,
        project_id:  0,
        task_id:     0,
    });
}

// ─── Locked company display name ──────────────────────────────────────────────
const lockedCompanyName = computed<string>(() => {
    if (props.lockedCompanyId === null) return '';
    return allCompanies.value.find((c: Company) => c.id === props.lockedCompanyId)?.name
        ?? String(props.lockedCompanyId);
});

// ─── Error helper ─────────────────────────────────────────────────────────────
function fieldError(field: string): string | undefined {
    return props.errors[field];
}
</script>
