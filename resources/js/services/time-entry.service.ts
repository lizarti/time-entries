import { http } from './http';
import type { ApiCollection, ApiItem, BulkInsertPayload, TimeEntry, UpdateTimeEntryPayload } from '@/types/api';

export const timeEntryService = {
    async getAll(companyId?: number): Promise<TimeEntry[]> {
        const params = companyId ? `?company_id=${companyId}` : '';
        const res = await http.get<ApiCollection<TimeEntry>>(`/time-entries${params}`);
        return res.data;
    },

    async bulkInsert(payload: BulkInsertPayload): Promise<TimeEntry[]> {
        const res = await http.post<ApiCollection<TimeEntry>>('/time-entries/bulk', payload);
        return res.data;
    },

    async update(id: number, payload: UpdateTimeEntryPayload): Promise<TimeEntry> {
        const res = await http.put<ApiItem<TimeEntry>>(`/time-entries/${id}`, payload);
        return res.data;
    },
};
