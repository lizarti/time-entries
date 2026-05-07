import { http } from './http';
import type { ApiCollection, Employee } from '@/types/api';

export const employeeService = {
    async getByCompany(companyId: number): Promise<Employee[]> {
        const res = await http.get<ApiCollection<Employee>>(`/companies/${companyId}/employees`);
        return res.data;
    },
};
