<template>
    <div class="flex flex-wrap items-end gap-3">

        <!-- Search -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">Search</label>
            <div class="relative">
                <SearchIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                <Input
                    v-model="searchInput"
                    placeholder="Employee, project, task..."
                    class="pl-8 w-56"
                />
            </div>
        </div>

        <!-- Employee -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">Employee</label>
            <AppCombobox
                :model-value="modelEmployee"
                :options="employeeOptions"
                placeholder="All employees"
                search-placeholder="Search employees..."
                class="w-44"
                @update:model-value="v => emit('update:modelValue', { employee_id: v ? Number(v) : undefined })"
            />
        </div>

        <!-- Project -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">Project</label>
            <AppCombobox
                :model-value="modelProject"
                :options="projectOptions"
                placeholder="All projects"
                search-placeholder="Search projects..."
                class="w-44"
                @update:model-value="v => emit('update:modelValue', { project_id: v ? Number(v) : undefined })"
            />
        </div>

        <!-- Task -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">Task</label>
            <AppCombobox
                :model-value="modelTask"
                :options="taskOptions"
                placeholder="All tasks"
                search-placeholder="Search tasks..."
                class="w-40"
                @update:model-value="v => emit('update:modelValue', { task_id: v ? Number(v) : undefined })"
            />
        </div>

        <!-- Date from -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">From</label>
            <div class="flex items-center gap-1">
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-36 justify-start font-normal"
                            :class="!modelValue.date_from && 'text-muted-foreground'"
                        >
                            <CalendarIcon data-icon="inline-start" />
                            {{ modelValue.date_from
                                ? df.format(parseDate(modelValue.date_from).toDate(getLocalTimeZone()))
                                : 'Start date' }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                        <Calendar
                            :model-value="modelValue.date_from ? parseDate(modelValue.date_from) : undefined"
                            layout="month-and-year"
                            @update:model-value="v => v && emit('update:modelValue', { date_from: v.toString() })"
                        />
                    </PopoverContent>
                </Popover>
                <Button
                    v-if="modelValue.date_from"
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 shrink-0"
                    @click="emit('update:modelValue', { date_from: undefined })"
                >
                    <XIcon class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Date to -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">To</label>
            <div class="flex items-center gap-1">
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-36 justify-start font-normal"
                            :class="!modelValue.date_to && 'text-muted-foreground'"
                        >
                            <CalendarIcon data-icon="inline-start" />
                            {{ modelValue.date_to
                                ? df.format(parseDate(modelValue.date_to).toDate(getLocalTimeZone()))
                                : 'End date' }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0" align="start">
                        <Calendar
                            :model-value="modelValue.date_to ? parseDate(modelValue.date_to) : undefined"
                            layout="month-and-year"
                            @update:model-value="v => v && emit('update:modelValue', { date_to: v.toString() })"
                        />
                    </PopoverContent>
                </Popover>
                <Button
                    v-if="modelValue.date_to"
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 shrink-0"
                    @click="emit('update:modelValue', { date_to: undefined })"
                >
                    <XIcon class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Clear all -->
        <div v-if="hasActiveFilters" class="flex flex-col gap-1.5">
            <label class="text-xs opacity-0 select-none">Clear</label>
            <Button variant="ghost" @click="onClear">
                <XIcon data-icon="inline-start" />
                Clear
            </Button>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { CalendarIcon, SearchIcon, XIcon } from 'lucide-vue-next';
import { parseDate, getLocalTimeZone, DateFormatter } from '@internationalized/date';
import { useDebounceFn } from '@vueuse/core';
import AppCombobox from '@/components/AppCombobox.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useEmployees } from '@/composables/useEmployees';
import { useProjects } from '@/composables/useProjects';
import { useTasks } from '@/composables/useTasks';
import type { Employee, Project, Task, TimeEntryFilters } from '@/types/api';

const props = defineProps<{
    modelValue: TimeEntryFilters;
    companyId: number | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [filters: Partial<TimeEntryFilters>];
}>();

const df = new DateFormatter('en-US', { dateStyle: 'medium' });

// ─── Company-scoped options ────────────────────────────────────────────────────

const companyRef = computed(() => props.companyId);
const { employees } = useEmployees(companyRef);
const { projects }  = useProjects(companyRef);
const { tasks }     = useTasks(companyRef);

// Include an "All ..." sentinel as first option so the user can deselect.
const employeeOptions = computed(() => [
    { value: '', label: 'All employees' },
    ...employees.value.map((e: Employee) => ({ value: String(e.id), label: e.name })),
]);
const projectOptions = computed(() => [
    { value: '', label: 'All projects' },
    ...projects.value.map((p: Project) => ({ value: String(p.id), label: p.name })),
]);
const taskOptions = computed(() => [
    { value: '', label: 'All tasks' },
    ...tasks.value.map((t: Task) => ({ value: String(t.id), label: t.name })),
]);

// Controlled values for the comboboxes ('' = no filter applied → shows placeholder/All).
const modelEmployee = computed(() => props.modelValue.employee_id ? String(props.modelValue.employee_id) : '');
const modelProject  = computed(() => props.modelValue.project_id  ? String(props.modelValue.project_id)  : '');
const modelTask     = computed(() => props.modelValue.task_id      ? String(props.modelValue.task_id)     : '');

// ─── Debounced search ─────────────────────────────────────────────────────────

const searchInput = ref(props.modelValue.search ?? '');

// Sync local input if parent resets search externally (e.g. Clear all).
watch(
    () => props.modelValue.search,
    (v) => { if (searchInput.value !== (v ?? '')) searchInput.value = v ?? ''; },
);

const emitSearch = useDebounceFn(
    (value: string) => emit('update:modelValue', { search: value || undefined }),
    300,
);
watch(searchInput, emitSearch);

// ─── Active filters check ─────────────────────────────────────────────────────

const hasActiveFilters = computed(() =>
    !!(props.modelValue.search     ||
       props.modelValue.employee_id ||
       props.modelValue.project_id  ||
       props.modelValue.task_id     ||
       props.modelValue.date_from   ||
       props.modelValue.date_to),
);

// ─── Clear all ────────────────────────────────────────────────────────────────

function onClear(): void {
    searchInput.value = '';
    emit('update:modelValue', {
        search:      undefined,
        employee_id: undefined,
        project_id:  undefined,
        task_id:     undefined,
        date_from:   undefined,
        date_to:     undefined,
    });
}
</script>
