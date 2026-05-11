<template>
    <div class="flex flex-col gap-5">

        <!-- Summary -->
        <HistorySummary
            v-if="loading || (meta?.total ?? 0) > 0"
            :by-employee="summary?.by_employee ?? []"
            :by-project="summary?.by_project ?? []"
            :by-company="summary?.by_company ?? []"
            :loading="loading"
        />

        <hr v-if="loading || (meta?.total ?? 0) > 0" class="border-border" />

        <h4 class="text-2xl font-semibold">Time entries</h4>

        <!-- Table card -->
        <div class="rounded-lg border bg-card">
            <div class="flex flex-col gap-5 p-6">

                <!-- Error -->
                <div
                    v-if="error"
                    class="rounded-md border border-destructive bg-destructive/10 px-4 py-3 text-sm text-destructive"
                >
                    {{ error }}
                </div>

                <!-- Filter bar -->
                <HistoryFilterBar
                    :model-value="filterState"
                    :company-id="selectedCompany?.id ?? null"
                    @update:model-value="setFilters"
                />

                <!-- Table -->
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                v-for="col in columns"
                                :key="col.key"
                                :class="[
                                    col.sortable && 'cursor-pointer select-none hover:text-foreground',
                                    col.key === 'hours' && 'text-right',
                                ]"
                                @click="col.sortable ? onSortClick(col.key) : undefined"
                            >
                                <div :class="['flex items-center gap-1', col.key === 'hours' && 'justify-end']">
                                    {{ col.label }}
                                    <template v-if="col.sortable">
                                        <ArrowUpIcon
                                            v-if="params.sort_by === col.key && params.sort_dir === 'asc'"
                                            class="h-3.5 w-3.5"
                                        />
                                        <ArrowDownIcon
                                            v-else-if="params.sort_by === col.key"
                                            class="h-3.5 w-3.5"
                                        />
                                        <ChevronsUpDownIcon
                                            v-else
                                            class="h-3.5 w-3.5 opacity-30"
                                        />
                                    </template>
                                </div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <!-- Loading skeletons -->
                    <TableBody v-if="loading">
                        <TableRow v-for="n in params.per_page" :key="n">
                            <TableCell v-for="col in columns" :key="col.key">
                                <Skeleton class="h-4 w-24" />
                            </TableCell>
                        </TableRow>
                    </TableBody>

                    <!-- Empty state -->
                    <TableBody v-else-if="entries.length === 0">
                        <TableRow>
                            <TableCell :colspan="columns.length" class="py-12 text-center text-muted-foreground">
                                No time entries found.
                            </TableCell>
                        </TableRow>
                    </TableBody>

                    <!-- Data -->
                    <TableBody v-else>
                        <template v-for="entry in entries" :key="entry.id">

                            <!-- Edit mode -->
                            <HistoryEntryRow
                                v-if="editingId === entry.id"
                                :entry="entry"
                                :errors="rowErrors"
                                :saving="savingId === entry.id"
                                @save="(payload) => onSave(entry, payload)"
                                @cancel="onCancel"
                            />

                            <!-- Read mode -->
                            <TableRow v-else>
                                <TableCell>{{ entry.company.name }}</TableCell>
                                <TableCell>{{ entry.date }}</TableCell>
                                <TableCell>{{ entry.employee.name }}</TableCell>
                                <TableCell>{{ entry.project.name }}</TableCell>
                                <TableCell>{{ entry.task.name }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ entry.hours }}</TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :disabled="editingId !== null"
                                        @click="onEdit(entry.id)"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>

                        </template>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div
                    v-if="meta && meta.total > 0"
                    class="flex items-center justify-between text-sm"
                >
                    <!-- Entry count -->
                    <span class="text-muted-foreground">
                        Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }} entries
                    </span>

                    <!-- Controls -->
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="params.page === 1"
                            @click="setPage(params.page - 1)"
                        >
                            <ChevronLeftIcon class="h-4 w-4" />
                        </Button>

                        <span class="text-muted-foreground">
                            {{ meta.current_page }} / {{ meta.last_page }}
                        </span>

                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="params.page === meta.last_page"
                            @click="setPage(params.page + 1)"
                        >
                            <ChevronRightIcon class="h-4 w-4" />
                        </Button>

                        <Select
                            :model-value="String(params.per_page)"
                            @update:model-value="v => setPerPage(Number(v))"
                        >
                            <SelectTrigger class="w-[80px] h-8">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="25">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>

                        <span class="text-muted-foreground">per page</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { ArrowDownIcon, ArrowUpIcon, ChevronLeftIcon, ChevronRightIcon, ChevronsUpDownIcon, PencilIcon } from 'lucide-vue-next';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useSelectedCompany } from '@/composables/useSelectedCompany';
import { useTimeEntries } from '@/composables/useTimeEntries';
import HistoryFilterBar from '@/components/HistoryFilterBar.vue';
import HistoryEntryRow from '@/components/HistoryEntryRow.vue';
import HistorySummary from '@/components/summary/HistorySummary.vue';
import type { TimeEntry, TimeEntryFilters, UpdateTimeEntryPayload } from '@/types/api';

const { selectedCompany } = useSelectedCompany();
const { entries, loading, error, meta, summary, params, setFilters, setSort, setPage, setPerPage, update } = useTimeEntries();

// ─── Column config ────────────────────────────────────────────────────────────

const columns = [
    { key: 'company',  label: 'Company',  sortable: true  },
    { key: 'date',     label: 'Date',     sortable: true  },
    { key: 'employee', label: 'Employee', sortable: true  },
    { key: 'project',  label: 'Project',  sortable: true  },
    { key: 'task',     label: 'Task',     sortable: true  },
    { key: 'hours',    label: 'Hours',    sortable: true  },
    { key: 'actions',  label: '',         sortable: false },
] as const;

// ─── Edit state ──────────────────────────────────────────────────────────────

const editingId  = ref<number | null>(null);
const savingId   = ref<number | null>(null);
const rowErrors  = ref<Record<string, string>>({});

function onEdit(id: number): void {
    editingId.value = id;
    rowErrors.value = {};
}

function onCancel(): void {
    editingId.value = null;
    rowErrors.value = {};
}

async function onSave(entry: TimeEntry, payload: UpdateTimeEntryPayload): Promise<void> {
    savingId.value  = entry.id;
    rowErrors.value = {};
    try {
        await update(entry.id, payload);
        editingId.value = null;
    } catch (e: unknown) {
        if (isValidationError(e)) {
            rowErrors.value = parseValidationErrors(e.body.errors);
        }
        // General errors bubble up to the top-level `error` ref via the composable.
    } finally {
        savingId.value = null;
    }
}

function isValidationError(e: unknown): e is { status: number; body: { errors: Record<string, string[]> } } {
    return typeof e === 'object' && e !== null && 'status' in e &&
           (e as { status: number }).status === 422 && 'body' in e;
}

function parseValidationErrors(errors: Record<string, string[]>): Record<string, string> {
    return Object.fromEntries(
        Object.entries(errors).map(([k, msgs]) => [k, msgs[0]]),
    );
}

// ─── Sort click ───────────────────────────────────────────────────────────────

function onSortClick(key: string): void {
    if (params.value.sort_by === key) {
        setSort(key, params.value.sort_dir === 'asc' ? 'desc' : 'asc');
    } else {
        setSort(key, 'desc');
    }
}

// ─── Filter bar state (non-company filters only) ──────────────────────────────

const filterState = computed<TimeEntryFilters>(() => ({
    search:      params.value.search,
    employee_id: params.value.employee_id,
    project_id:  params.value.project_id,
    task_id:     params.value.task_id,
    date_from:   params.value.date_from,
    date_to:     params.value.date_to,
}));

// ─── Company change → reset filters and reload ────────────────────────────────

onMounted(() => setFilters({ company_id: selectedCompany.value?.id }));
watch(selectedCompany, (company) => setFilters({ company_id: company?.id }));
</script>
