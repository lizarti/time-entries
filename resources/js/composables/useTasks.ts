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

    async function fetch(id: number): Promise<void> {
        loading.value = true;
        try {
            tasks.value = await taskService.getByCompany(id);
        } finally {
            loading.value = false;
        }
    }

    watch(
        companyId,
        (id) => {
            if (id === null) {
                tasks.value = [];
            } else {
                fetch(id);
            }
        },
        { immediate: true },
    );

    return { tasks, loading };
}
