import { http } from './http';
import type { ApiCollection, Employee } from '@/types/api';

export const employeeService = {
    async getAll(companyId?: number): Promise<Employee[]> {
        const qs = companyId ? `?company_id=${companyId}` : '';
        const res = await http.get<ApiCollection<Employee>>(`/employees${qs}`);
        return res.data;
    },
};
