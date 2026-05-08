<template>
    <TableRow>

        <!-- Company (locked) -->
        <TableCell>
            <span class="px-1 text-sm">{{ entry.company.name }}</span>
        </TableCell>

        <!-- Date -->
        <TableCell>
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        :class="cn(
                            'w-38 justify-start text-left font-normal',
                            !draft.date && 'text-muted-foreground',
                            fieldError('date') && 'border-destructive',
                        )"
                    >
                        <CalendarIcon data-icon="inline-start" />
                        {{ draft.date ? df.format(parseDate(draft.date).toDate(getLocalTimeZone())) : 'Pick a date' }}
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-auto p-0" align="start">
                    <Calendar
                        :model-value="draft.date ? parseDate(draft.date) : undefined"
                        layout="month-and-year"
                        initial-focus
                        @update:model-value="(v) => v && (draft.date = v.toString())"
                    />
                </PopoverContent>
            </Popover>
            <p v-if="fieldError('date')" class="mt-1 text-xs text-destructive">{{ fieldError('date') }}</p>
        </TableCell>

        <!-- Employee -->
        <TableCell>
            <AppCombobox
                :model-value="draft.employee_id ? String(draft.employee_id) : ''"
                :options="employeeOptions"
                placeholder="Employee"
                search-placeholder="Search employees..."
                @update:model-value="(v) => (draft.employee_id = Number(v))"
            />
            <p v-if="fieldError('employee_id')" class="mt-1 text-xs text-destructive">{{ fieldError('employee_id') }}</p>
        </TableCell>

        <!-- Project -->
        <TableCell>
            <AppCombobox
                :model-value="draft.project_id ? String(draft.project_id) : ''"
                :options="projectOptions"
                placeholder="Project"
                search-placeholder="Search projects..."
                @update:model-value="(v) => (draft.project_id = Number(v))"
            />
            <p v-if="fieldError('project_id')" class="mt-1 text-xs text-destructive">{{ fieldError('project_id') }}</p>
        </TableCell>

        <!-- Task -->
        <TableCell>
            <AppCombobox
                :model-value="draft.task_id ? String(draft.task_id) : ''"
                :options="taskOptions"
                placeholder="Task"
                search-placeholder="Search tasks..."
                @update:model-value="(v) => (draft.task_id = Number(v))"
            />
            <p v-if="fieldError('task_id')" class="mt-1 text-xs text-destructive">{{ fieldError('task_id') }}</p>
        </TableCell>

        <!-- Hours -->
        <TableCell class="text-right">
            <Input
                type="number"
                min="0.01"
                step="0.5"
                placeholder="0.00"
                :model-value="draft.hours || ''"
                class="w-24 text-right tabular-nums"
                :class="{ 'border-destructive': fieldError('hours') }"
                @input="draft.hours = parseFloat(($event.target as HTMLInputElement).value) || 0"
            />
            <p v-if="fieldError('hours')" class="mt-1 text-xs text-destructive">{{ fieldError('hours') }}</p>
        </TableCell>

        <!-- Actions -->
        <TableCell class="text-right">
            <div class="flex items-center justify-end gap-1">
                <Button size="sm" :disabled="saving" @click="onSave">
                    <Loader2Icon v-if="saving" class="size-3 animate-spin" />
                    <span>Save</span>
                </Button>
                <Button variant="ghost" size="sm" :disabled="saving" @click="emit('cancel')">
                    Cancel
                </Button>
            </div>
        </TableCell>

    </TableRow>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue';
import { CalendarIcon, Loader2Icon } from 'lucide-vue-next';
import { parseDate, getLocalTimeZone, DateFormatter } from '@internationalized/date';
import { TableCell, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import AppCombobox from '@/components/AppCombobox.vue';
import { useEmployees } from '@/composables/useEmployees';
import { useProjects } from '@/composables/useProjects';
import { useTasks } from '@/composables/useTasks';
import type { Employee, Project, Task, TimeEntry, UpdateTimeEntryPayload } from '@/types/api';

const props = defineProps<{
    entry: TimeEntry;
    errors: Record<string, string>;
    saving: boolean;
}>();

const emit = defineEmits<{
    save: [payload: UpdateTimeEntryPayload];
    cancel: [];
}>();

const df = new DateFormatter('en-US', { dateStyle: 'medium' });

// ─── Draft state (mutable copy of the entry's current values) ─────────────────
const draft = reactive<UpdateTimeEntryPayload>({
    employee_id: props.entry.employee.id,
    project_id:  props.entry.project.id,
    task_id:     props.entry.task.id,
    date:        props.entry.date,
    hours:       props.entry.hours,
});

// ─── Load options for the entry's company (locked) ────────────────────────────
const companyId = computed(() => props.entry.company.id);

const { employees } = useEmployees(companyId);
const { projects }  = useProjects(companyId);
const { tasks }     = useTasks(companyId);

const employeeOptions = computed(() =>
    employees.value.map((e: Employee) => ({ value: String(e.id), label: e.name })),
);
const projectOptions = computed(() =>
    projects.value.map((p: Project) => ({ value: String(p.id), label: p.name })),
);
const taskOptions = computed(() =>
    tasks.value.map((t: Task) => ({ value: String(t.id), label: t.name })),
);

// ─── Helpers ──────────────────────────────────────────────────────────────────
function fieldError(field: string): string | undefined {
    return props.errors[field];
}

function onSave(): void {
    emit('save', { ...draft });
}
</script>
