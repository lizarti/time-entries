import { ref } from 'vue';
import type { Ref } from 'vue';
import type { Company } from '@/types/api';

const selectedCompany = ref<Company | null>(null);

export function useSelectedCompany(): {
    selectedCompany: Ref<Company | null>;
    setSelectedCompany: (company: Company | null) => void;
} {
    function setSelectedCompany(company: Company | null): void {
        selectedCompany.value = company;
    }

    return { selectedCompany, setSelectedCompany };
}
