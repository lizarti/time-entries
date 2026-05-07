import { http } from './http';
import type { ApiCollection, Task } from '@/types/api';

export const taskService = {
    async getByCompany(companyId: number): Promise<Task[]> {
        const res = await http.get<ApiCollection<Task>>(`/companies/${companyId}/tasks`);
        return res.data;
    },
};
