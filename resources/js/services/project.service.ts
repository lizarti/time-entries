import { http } from './http';
import type { ApiCollection, Project } from '@/types/api';

export const projectService = {
    async getByCompany(companyId: number): Promise<Project[]> {
        const res = await http.get<ApiCollection<Project>>(`/companies/${companyId}/projects`);
        return res.data;
    },
};
