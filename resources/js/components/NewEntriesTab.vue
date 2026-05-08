<template>
    <div class="flex flex-col gap-4 pt-4">

        <!-- Error banner -->
        <div
            v-if="submitError"
            class="rounded-md border border-destructive bg-destructive/10 px-4 py-3 text-sm text-destructive"
        >
            {{ submitError }}
        </div>

        <!-- Success banner -->
        <div
            v-if="submitSuccess"
            class="rounded-md border border-green-600 bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            Entries submitted successfully.
        </div>

        <!-- Table -->
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Company</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Employee</TableHead>
                    <TableHead>Project</TableHead>
                    <TableHead>Task</TableHead>
                    <TableHead>Hours</TableHead>
                    <TableHead class="w-10" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <NewTimeEntryRow
                    v-for="(row, index) in rows"
                    :key="index"
                    :model-value="row"
                    :locked-company-id="selectedCompany?.id ?? null"
                    :errors="rowErrors[index] ?? {}"
                    @update:model-value="(updated) => onUpdateRow(index, updated)"
                    @remove="removeRow(index)"
                    @clear-error="(field) => clearRowError(index, field)"
                />
            </TableBody>
        </Table>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <Button variant="outline" @click="addRow">
                <PlusIcon data-icon="inline-start" />
                Add row
            </Button>

            <Button :disabled="loading || rows.length === 0" @click="submit">
                <Loader2Icon v-if="loading" class="animate-spin" data-icon="inline-start" />
                Submit entries
            </Button>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { PlusIcon, Loader2Icon } from 'lucide-vue-next';
import {
    Table, TableBody, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { useSelectedCompany } from '@/composables/useSelectedCompany';
import { useTimeEntries } from '@/composables/useTimeEntries';
import NewTimeEntryRow from '@/components/NewTimeEntryRow.vue';
import type { BulkInsertEntry } from '@/types/api';

const { selectedCompany } = useSelectedCompany();
const { loading, bulkInsert } = useTimeEntries();

// ─── Row state ────────────────────────────────────────────────────────────────
function blankRow(): BulkInsertEntry {
    return {
        company_id:  selectedCompany.value?.id ?? 0,
        employee_id: 0,
        project_id:  0,
        task_id:     0,
        date:        '',
        hours:       0,
    };
}

const rows = ref<BulkInsertEntry[]>([blankRow()]);

function addRow(): void {
    rows.value.push(blankRow());
}

function removeRow(index: number): void {
    rows.value.splice(index, 1);
}

// ─── Submission ───────────────────────────────────────────────────────────────
const submitError = ref<string | null>(null);
const submitSuccess = ref(false);
const rowErrors = ref<Record<number, Record<string, string>>>({});

async function submit(): Promise<void> {
    submitSuccess.value = false;

    if (!validateRows()) return;

    submitError.value = null;
    rowErrors.value = {};

    try {
        await bulkInsert({ entries: rows.value });
        rows.value = [blankRow()];
        submitSuccess.value = true;
    } catch (e: unknown) {
        if (isValidationError(e)) {
            parseValidationErrors(e.body.errors);
        } else {
            submitError.value = e instanceof Error ? e.message : 'Submission failed.';
        }
    }
}

// ─── Frontend validation ────────────────────────────────────────────────────
function validateRows(): boolean {
    const errors: Record<number, Record<string, string>> = {};

    // Required-field checks
    rows.value.forEach((row, index) => {
        const errs: Record<string, string> = {};

        if (!row.company_id)  errs.company_id  = 'Company is required';
        if (!row.date)        errs.date        = 'Date is required';
        if (!row.employee_id) errs.employee_id = 'Employee is required';
        if (!row.project_id)  errs.project_id  = 'Project is required';
        if (!row.task_id)     errs.task_id     = 'Task is required';
        if (!row.hours || row.hours <= 0) errs.hours = 'Hours must be greater than 0';

        if (Object.keys(errs).length) errors[index] = errs;
    });

    // Cross-row conflict: same employee + same date → more than one distinct project
    const groups = new Map<string, { projectId: number; index: number }[]>();

    rows.value.forEach((row, index) => {
        if (!row.employee_id || !row.date || !row.project_id) return;
        const key = `${row.employee_id}|${row.date}`;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key)!.push({ projectId: row.project_id, index });
    });

    groups.forEach((entries) => {
        const uniqueProjects = new Set(entries.map(e => e.projectId));
        if (uniqueProjects.size > 1) {
            entries.forEach(({ index }) => {
                errors[index] ??= {};
                errors[index].project_id =
                    'This employee is already assigned to a different project on this date';
            });
        }
    });

    rowErrors.value = errors;

    if (Object.keys(errors).length > 0) {
        submitError.value = 'Please fix the highlighted errors before submitting.';
        return false;
    }

    return true;
}

// ─── Live error clearing ──────────────────────────────────────────────────────
function clearRowError(index: number, field: string): void {
    if (!rowErrors.value[index]) return;
    delete rowErrors.value[index][field];
    if (Object.keys(rowErrors.value[index]).length === 0) {
        delete rowErrors.value[index];
    }
    if (Object.keys(rowErrors.value).length === 0) {
        submitError.value = null;
    }
}

// ─── Validation error parsing ─────────────────────────────────────────────────
// Laravel returns { errors: { "entries.0.date": ["..."], ... } }
function parseValidationErrors(errors: Record<string, string[]>): void {
    const parsed: Record<number, Record<string, string>> = {};
    for (const [key, messages] of Object.entries(errors)) {
        const match = key.match(/^entries\.(\d+)\.(.+)$/);
        if (match) {
            const index = parseInt(match[1], 10);
            const field = match[2];
            parsed[index] ??= {};
            parsed[index][field] = messages[0];
        }
    }
    if (Object.keys(parsed).length > 0) {
        rowErrors.value = parsed;
        submitError.value = 'Some entries have validation errors. Please review and resubmit.';
    } else {
        submitError.value = 'Submission failed. Please try again.';
    }
}

function isValidationError(e: unknown): e is { status: number; body: { errors: Record<string, string[]> } } {
    return (
        typeof e === 'object' &&
        e !== null &&
        'status' in e &&
        (e as { status: number }).status === 422 &&
        'body' in e
    );
}

function onUpdateRow(index: number, updated: BulkInsertEntry): void {
    rows.value[index] = updated;
}
</script>
