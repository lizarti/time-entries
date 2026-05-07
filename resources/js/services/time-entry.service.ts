import { http } from './http';
import type { ApiCollection, BulkInsertPayload, TimeEntry } from '@/types/api';

export const timeEntryService = {
    async getAll(companyId?: number): Promise<TimeEntry[]> {
        const params = companyId ? `?company_id=${companyId}` : '';
        const res = await http.get<ApiCollection<TimeEntry>>(`/time-entries${params}`);
        return res.data;
    },

    async bulkInsert(payload: BulkInsertPayload): Promise<TimeEntry[]> {
        const res = await http.post<ApiCollection<TimeEntry>>('/time-entries', payload);
        return res.data;
    },
};
