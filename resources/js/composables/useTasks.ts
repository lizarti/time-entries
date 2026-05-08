import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { taskService } from '@/services/task.service';
import type { Task } from '@/types/api';

export function useTasks(companyId: Ref<number | null>): {
    tasks: Ref<Task[]>;
    loading: Ref<boolean>;
} {
    const tasks = ref<Task[]>([]);
    const loading = ref(false);

    async function fetch(id: number | null): Promise<void> {
        loading.value = true;
        try {
            tasks.value = await taskService.getAll(id ?? undefined);
        } finally {
            loading.value = false;
        }
    }

    watch(companyId, (id) => fetch(id), { immediate: true });

    return { tasks, loading };
}
