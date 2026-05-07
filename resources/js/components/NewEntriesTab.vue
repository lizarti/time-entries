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
                    @update:model-value="(updated) => rows[index] = updated"
                    @remove="removeRow(index)"
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
    submitError.value = null;
    submitSuccess.value = false;
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
</script>
