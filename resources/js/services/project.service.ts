import { http } from './http';
import type { ApiCollection, Project } from '@/types/api';

export const projectService = {
    async getAll(companyId?: number): Promise<Project[]> {
        const qs = companyId ? `?company_id=${companyId}` : '';
        const res = await http.get<ApiCollection<Project>>(`/projects${qs}`);
        return res.data;
    },
};
