import { http } from './http';
import type {
    ApiCollection,
    ApiItem,
    ApiPage,
    BulkInsertPayload,
    SummaryData,
    TimeEntry,
    TimeEntryFilters,
    TimeEntryListParams,
    UpdateTimeEntryPayload,
} from '@/types/api';

function buildQueryString(params: Record<string, unknown>): string {
    const parts: string[] = [];
    for (const [k, v] of Object.entries(params)) {
        if (v !== undefined && v !== null && v !== '') {
            parts.push(`${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
        }
    }
    return parts.length ? `?${parts.join('&')}` : '';
}

export const timeEntryService = {
    async getAll(params: TimeEntryListParams = {}): Promise<ApiPage<TimeEntry>> {
        const qs = buildQueryString(params as Record<string, unknown>);
        return http.get<ApiPage<TimeEntry>>(`/time-entries${qs}`);
    },

    async getSummary(filters: TimeEntryFilters = {}): Promise<SummaryData> {
        // Only forward filter fields — sort/pagination are irrelevant to the summary.
        const filterOnly: TimeEntryFilters = {
            company_id:  filters.company_id,
            employee_id: filters.employee_id,
            project_id:  filters.project_id,
            task_id:     filters.task_id,
            date_from:   filters.date_from,
            date_to:     filters.date_to,
            search:      filters.search,
        };
        const qs = buildQueryString(filterOnly as Record<string, unknown>);
        const res = await http.get<{ data: SummaryData }>(`/time-entries/summary${qs}`);
        return res.data;
    },

    async update(id: number, payload: UpdateTimeEntryPayload): Promise<TimeEntry> {
        const res = await http.put<ApiItem<TimeEntry>>(`/time-entries/${id}`, payload);
        return res.data;
    },

    async bulkInsert(payload: BulkInsertPayload): Promise<TimeEntry[]> {
        const res = await http.post<ApiCollection<TimeEntry>>('/time-entries/bulk', payload);
        return res.data;
    },
};
