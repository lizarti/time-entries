import { http } from './http';
import type { ApiCollection, Task } from '@/types/api';

export const taskService = {
    async getAll(companyId?: number): Promise<Task[]> {
        const qs = companyId ? `?company_id=${companyId}` : '';
        const res = await http.get<ApiCollection<Task>>(`/tasks${qs}`);
        return res.data;
    },
};
