import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { timeEntryService } from '@/services/time-entry.service';
import type {
    BulkInsertPayload,
    PaginationMeta,
    SummaryData,
    TimeEntry,
    TimeEntryFilters,
    TimeEntryListParams,
    UpdateTimeEntryPayload,
} from '@/types/api';

// Internal state shape — sort/page/perPage are always defined.
interface HistoryParams extends TimeEntryListParams {
    sort_by: string;
    sort_dir: 'asc' | 'desc';
    page: number;
    per_page: number;
}

const DEFAULT_PARAMS: HistoryParams = {
    sort_by:  'date',
    sort_dir: 'desc',
    page:     1,
    per_page: 25,
};

export function useTimeEntries(): {
    entries:    Ref<TimeEntry[]>;
    loading:    Ref<boolean>;
    error:      Ref<string | null>;
    meta:       Ref<PaginationMeta | null>;
    summary:    Ref<SummaryData | null>;
    params:     Ref<HistoryParams>;
    setFilters: (partial: Partial<TimeEntryFilters>) => void;
    setSort:    (sort_by: string, sort_dir: 'asc' | 'desc') => void;
    setPage:    (page: number) => void;
    setPerPage: (per_page: number) => void;
    update:     (id: number, payload: UpdateTimeEntryPayload) => Promise<void>;
    bulkInsert: (payload: BulkInsertPayload) => Promise<void>;
} {
    const entries = ref<TimeEntry[]>([]);
    const loading = ref(false);
    const error   = ref<string | null>(null);
    const meta    = ref<PaginationMeta | null>(null);
    const summary = ref<SummaryData | null>(null);
    const params  = ref<HistoryParams>({ ...DEFAULT_PARAMS });

    // ─── Load ─────────────────────────────────────────────────────────────────

    async function load(): Promise<void> {
        loading.value = true;
        error.value   = null;
        try {
            const [pageResult, summaryResult] = await Promise.all([
                timeEntryService.getAll(params.value),
                timeEntryService.getSummary(params.value),
            ]);
            entries.value = pageResult.data;
            meta.value    = pageResult.meta;
            summary.value = summaryResult;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to load entries.';
        } finally {
            loading.value = false;
        }
    }

    // Fire load whenever params changes. HistoryTab triggers the first load
    // by calling setFilters({ company_id }) on mount, which replaces params.
    watch(params, load, { deep: true });

    // ─── Mutators ─────────────────────────────────────────────────────────────

    function setFilters(partial: Partial<TimeEntryFilters>): void {
        params.value = { ...params.value, ...partial, page: 1 };
    }

    function setSort(sort_by: string, sort_dir: 'asc' | 'desc'): void {
        params.value = { ...params.value, sort_by, sort_dir, page: 1 };
    }

    function setPage(page: number): void {
        params.value = { ...params.value, page };
    }

    function setPerPage(per_page: number): void {
        params.value = { ...params.value, per_page, page: 1 };
    }

    // ─── update (used by HistoryTab) ──────────────────────────────────────────

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

    // ─── bulkInsert (used by NewEntriesTab) ───────────────────────────────────

    async function bulkInsert(payload: BulkInsertPayload): Promise<void> {
        loading.value = true;
        error.value   = null;
        try {
            const created = await timeEntryService.bulkInsert(payload);
            entries.value = [...entries.value, ...created];
        } finally {
            loading.value = false;
        }
    }

    return { entries, loading, error, meta, summary, params, setFilters, setSort, setPage, setPerPage, update, bulkInsert };
}
