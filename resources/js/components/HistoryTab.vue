<template>
    <div class="flex flex-col gap-4 pt-4">

        <!-- Error banner -->
        <div
            v-if="error || saveError"
            class="rounded-md border border-destructive bg-destructive/10 px-4 py-3 text-sm text-destructive"
        >
            {{ error || saveError }}
        </div>

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Company</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Employee</TableHead>
                    <TableHead>Project</TableHead>
                    <TableHead>Task</TableHead>
                    <TableHead class="text-right">Hours</TableHead>
                    <TableHead class="w-10" />
                </TableRow>
            </TableHeader>

            <!-- Loading skeletons -->
            <TableBody v-if="loading">
                <TableRow v-for="n in 5" :key="n">
                    <TableCell v-for="col in 7" :key="col">
                        <Skeleton class="h-4 w-24" />
                    </TableCell>
                </TableRow>
            </TableBody>

            <!-- Empty state -->
            <TableBody v-else-if="entries.length === 0">
                <TableRow>
                    <TableCell colspan="7" class="py-12 text-center text-muted-foreground">
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
                        :errors="editErrors"
                        :saving="saving"
                        @save="(payload) => onSave(entry.id, payload)"
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
                                <PencilIcon class="size-4" />
                            </Button>
                        </TableCell>
                    </TableRow>

                </template>
            </TableBody>
        </Table>

    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { PencilIcon } from 'lucide-vue-next';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { useSelectedCompany } from '@/composables/useSelectedCompany';
import { useTimeEntries } from '@/composables/useTimeEntries';
import HistoryEntryRow from '@/components/HistoryEntryRow.vue';
import type { UpdateTimeEntryPayload } from '@/types/api';

const { selectedCompany } = useSelectedCompany();
const { entries, loading, error, fetch, update } = useTimeEntries();

onMounted(() => fetch(selectedCompany.value?.id));

watch(selectedCompany, (company) => {
    onCancel();
    fetch(company?.id);
});

// ─── Edit state ───────────────────────────────────────────────────────────────
const editingId  = ref<number | null>(null);
const editErrors = ref<Record<string, string>>({});
const saving     = ref(false);
const saveError  = ref<string | null>(null);

function onEdit(id: number): void {
    editingId.value  = id;
    editErrors.value = {};
    saveError.value  = null;
}

function onCancel(): void {
    editingId.value  = null;
    editErrors.value = {};
    saveError.value  = null;
}

async function onSave(id: number, payload: UpdateTimeEntryPayload): Promise<void> {
    saving.value    = true;
    saveError.value = null;
    editErrors.value = {};

    try {
        await update(id, payload);
        editingId.value = null;
    } catch (e: unknown) {
        if (isValidationError(e)) {
            const fieldErrors: Record<string, string> = {};
            for (const [field, messages] of Object.entries(e.body.errors)) {
                fieldErrors[field] = messages[0];
            }
            editErrors.value = fieldErrors;
            saveError.value  = 'Please fix the validation errors below.';
        } else {
            saveError.value = e instanceof Error ? e.message : 'Failed to save entry.';
        }
    } finally {
        saving.value = false;
    }
}

// ─── Error type guard ─────────────────────────────────────────────────────────
function isValidationError(e: unknown): e is { status: number; body: { errors: Record<string, string[]> } } {
    return (
        typeof e === 'object' &&
        e !== null &&
        'status' in e &&
        (e as { status: number }).status === 422 &&
        'body' in e &&
        typeof (e as { body: unknown }).body === 'object' &&
        (e as { body: { errors?: unknown } }).body !== null &&
        'errors' in ((e as { body: object }).body as object)
    );
}
</script>
