import { ref } from 'vue';
import type { Ref } from 'vue';
import { timeEntryService } from '@/services/time-entry.service';
import type { BulkInsertPayload, TimeEntry, UpdateTimeEntryPayload } from '@/types/api';

export function useTimeEntries(): {
    entries: Ref<TimeEntry[]>;
    loading: Ref<boolean>;
    error: Ref<string | null>;
    fetch: (companyId?: number) => Promise<void>;
    bulkInsert: (payload: BulkInsertPayload) => Promise<void>;
    update: (id: number, payload: UpdateTimeEntryPayload) => Promise<void>;
} {
    const entries = ref<TimeEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetch(companyId?: number): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            entries.value = await timeEntryService.getAll(companyId);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to load entries.';
        } finally {
            loading.value = false;
        }
    }

    async function bulkInsert(payload: BulkInsertPayload): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            const created = await timeEntryService.bulkInsert(payload);
            entries.value = [...entries.value, ...created];
        } finally {
            loading.value = false;
        }
    }

    async function update(id: number, payload: UpdateTimeEntryPayload): Promise<void> {
        const updated = await timeEntryService.update(id, payload);
        const idx = entries.value.findIndex(e => e.id === id);
        if (idx !== -1) {
            entries.value = [
                ...entries.value.slice(0, idx),
                updated,
                ...entries.value.slice(idx + 1),
            ];
        }
    }

    return { entries, loading, error, fetch, bulkInsert, update };
}
