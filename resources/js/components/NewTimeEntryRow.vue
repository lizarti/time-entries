<template>
    <TableRow>
        <!-- Company -->
        <TableCell>
            <div class="flex flex-col gap-1">
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
                    :class="cn({ 'border-destructive': fieldError('company_id') })"
                    placeholder="Company"
                    search-placeholder="Search companies..."
                    @update:model-value="onCompanyChange"
                />
                <p v-if="fieldError('company_id')" class="text-xs text-destructive">
                    {{ fieldError('company_id') }}
                </p>
            </div>
        </TableCell>

        <!-- Date -->
        <TableCell>
            <div class="flex flex-col gap-1">
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            :class="cn(
                                'w-38 justify-start text-left font-normal',
                                !row.date && 'text-muted-foreground',
                                fieldError('date') && 'border-destructive',
                            )"
                        >
                            <CalendarIcon data-icon="inline-start" />
                            {{ row.date ? df.format(parseDate(row.date).toDate(getLocalTimeZone())) : 'Pick a date' }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                        <Calendar
                            :model-value="row.date ? parseDate(row.date) : undefined"
                            layout="month-and-year"
                            initial-focus
                            @update:model-value="onDateChange"
                        />
                    </PopoverContent>
                </Popover>
                <p v-if="fieldError('date')" class="text-xs text-destructive">
                    {{ fieldError('date') }}
                </p>
            </div>
        </TableCell>

        <!-- Employee -->
        <TableCell>
            <div class="flex flex-col gap-1">
                <AppCombobox
                    :model-value="row.employee_id ? String(row.employee_id) : ''"
                    :options="availableEmployeeOptions"
                    :disabled="!effectiveCompanyId"
                    :class="cn({ 'border-destructive': fieldError('employee_id') })"
                    placeholder="Employee"
                    search-placeholder="Search employees..."
                    @update:model-value="onEmployeeChange"
                />
                <p v-if="fieldError('employee_id')" class="text-xs text-destructive">
                    {{ fieldError('employee_id') }}
                </p>
            </div>
        </TableCell>

        <!-- Project -->
        <TableCell>
            <div class="flex flex-col gap-1">
                <AppCombobox
                    :model-value="row.project_id ? String(row.project_id) : ''"
                    :options="availableProjectOptions"
                    :disabled="!effectiveCompanyId"
                    :class="cn({ 'border-destructive': fieldError('project_id') })"
                    placeholder="Project"
                    search-placeholder="Search projects..."
                    @update:model-value="onProjectChange"
                />
                <p v-if="fieldError('project_id')" class="text-xs text-destructive">
                    {{ fieldError('project_id') }}
                </p>
            </div>
        </TableCell>

        <!-- Task -->
        <TableCell>
            <div class="flex flex-col gap-1">
                <AppCombobox
                    :model-value="row.task_id ? String(row.task_id) : ''"
                    :options="availableTaskOptions"
                    :disabled="!effectiveCompanyId"
                    :class="cn({ 'border-destructive': fieldError('task_id') })"
                    placeholder="Task"
                    search-placeholder="Search tasks..."
                    @update:model-value="onTaskChange"
                />
                <p v-if="fieldError('task_id')" class="text-xs text-destructive">
                    {{ fieldError('task_id') }}
                </p>
            </div>
        </TableCell>

        <!-- Hours -->
        <TableCell>
            <div class="flex flex-col gap-1">
                <Input
                    type="number"
                    min="0.01"
                    step="0.5"
                    placeholder="0.00"
                    :value="row.hours || ''"
                    class="w-24"
                    :class="{ 'border-destructive': fieldError('hours') }"
                    @input="onHoursChange"
                />
                <p v-if="fieldError('hours')" class="text-xs text-destructive">
                    {{ fieldError('hours') }}
                </p>
            </div>
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
import { CalendarIcon, XIcon } from 'lucide-vue-next';
import { parseDate, getLocalTimeZone, DateFormatter } from '@internationalized/date';
import type { DateValue } from '@internationalized/date';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
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
    'clear-error': [field: string];
}>();

const row = computed(() => props.modelValue);

const df = new DateFormatter('en-US', { dateStyle: 'medium' });

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

// ─── Field change handlers ────────────────────────────────────────────────────
function onCompanyChange(value: string): void {
    emit('clear-error', 'company_id');
    emit('clear-error', 'employee_id');
    emit('clear-error', 'project_id');
    emit('clear-error', 'task_id');
    emit('update:modelValue', {
        ...row.value,
        company_id:  Number(value),
        employee_id: 0,
        project_id:  0,
        task_id:     0,
    });
}

function onDateChange(v: DateValue | undefined): void {
    if (!v) return;
    emit('clear-error', 'date');
    emit('update:modelValue', { ...row.value, date: v.toString() });
}

function onEmployeeChange(v: string): void {
    emit('clear-error', 'employee_id');
    emit('update:modelValue', { ...row.value, employee_id: Number(v) });
}

function onProjectChange(v: string): void {
    emit('clear-error', 'project_id');
    emit('update:modelValue', { ...row.value, project_id: Number(v) });
}

function onTaskChange(v: string): void {
    emit('clear-error', 'task_id');
    emit('update:modelValue', { ...row.value, task_id: Number(v) });
}

function onHoursChange(event: Event): void {
    emit('clear-error', 'hours');
    emit('update:modelValue', {
        ...row.value,
        hours: parseFloat((event.target as HTMLInputElement).value) || 0,
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
