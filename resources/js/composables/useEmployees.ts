import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { employeeService } from '@/services/employee.service';
import type { Employee } from '@/types/api';

export function useEmployees(companyId: Ref<number | null>): {
    employees: Ref<Employee[]>;
    loading: Ref<boolean>;
} {
    const employees = ref<Employee[]>([]);
    const loading = ref(false);

    async function fetch(id: number | null): Promise<void> {
        loading.value = true;
        try {
            employees.value = await employeeService.getAll(id ?? undefined);
        } finally {
            loading.value = false;
        }
    }

    watch(companyId, (id) => fetch(id), { immediate: true });

    return { employees, loading };
}
