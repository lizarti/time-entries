import { ref } from 'vue';
import type { Ref } from 'vue';
import { timeEntryService } from '@/services/time-entry.service';
import type { BulkInsertEntry, ParsedTimeEntry } from '@/types/api';

function toEntry(parsed: ParsedTimeEntry): BulkInsertEntry {
    return {
        company_id:  parsed.company_id,
        employee_id: parsed.employee_id,
        project_id:  parsed.project_id,
        task_id:     parsed.task_id,
        date:        parsed.date,
        hours:       parsed.hours,
    };
}

export function useAiParser(): {
    loading: Ref<boolean>;
    error: Ref<string | null>;
    parse: (message: string) => Promise<BulkInsertEntry | null>;
} {
    const loading = ref(false);
    const error   = ref<string | null>(null);

    async function parse(message: string): Promise<BulkInsertEntry | null> {
        loading.value = true;
        error.value   = null;

        try {
            const parsed = await timeEntryService.parse(message);
            return toEntry(parsed);
        } catch (e: unknown) {
            error.value = e instanceof Error ? e.message : 'Failed to parse entry.';
            return null;
        } finally {
            loading.value = false;
        }
    }

    return { loading, error, parse };
}
