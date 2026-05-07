import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { projectService } from '@/services/project.service';
import type { Project } from '@/types/api';

export function useProjects(companyId: Ref<number | null>): {
    projects: Ref<Project[]>;
    loading: Ref<boolean>;
} {
    const projects = ref<Project[]>([]);
    const loading = ref(false);

    async function fetch(id: number): Promise<void> {
        loading.value = true;
        try {
            projects.value = await projectService.getByCompany(id);
        } finally {
            loading.value = false;
        }
    }

    watch(
        companyId,
        (id) => {
            if (id === null) {
                projects.value = [];
            } else {
                fetch(id);
            }
        },
        { immediate: true },
    );

    return { projects, loading };
}
