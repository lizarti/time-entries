import { ref, onMounted } from 'vue';
import type { Ref } from 'vue';
import { companyService } from '@/services/company.service';
import type { Company } from '@/types/api';

export function useCompanies(): {
    companies: Ref<Company[]>;
    loading: Ref<boolean>;
    fetch: (search?: string) => Promise<void>;
} {
    const companies = ref<Company[]>([]);
    const loading = ref(false);

    async function fetch(search?: string): Promise<void> {
        loading.value = true;
        try {
            companies.value = await companyService.getAll(search);
        } finally {
            loading.value = false;
        }
    }

    onMounted(() => fetch());

    return { companies, loading, fetch };
}
