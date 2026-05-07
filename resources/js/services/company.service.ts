import { http } from './http';
import type { ApiCollection, Company } from '@/types/api';

export const companyService = {
    async getAll(search?: string): Promise<Company[]> {
        const params = search ? `?search=${encodeURIComponent(search)}` : '';
        const res = await http.get<ApiCollection<Company>>(`/companies${params}`);
        return res.data;
    },
};
