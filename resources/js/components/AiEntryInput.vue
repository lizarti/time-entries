<template>
    <div class="flex flex-col gap-3">

        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">
                Add entry with AI
            </label>

            <div class="flex gap-2">
                <Textarea
                    v-model="message"
                    placeholder="e.g. John worked on Website Redesign for Golden Mango yesterday doing Development for 4 hours"
                    class="min-h-[72px] resize-none text-sm"
                    :disabled="loading"
                    @keydown.ctrl.enter="onParse"
                    @keydown.meta.enter="onParse"
                />

                <Button
                    class="self-end shrink-0"
                    :disabled="loading || message.trim().length === 0"
                    @click="onParse"
                >
                    <Loader2Icon v-if="loading" class="animate-spin" data-icon="inline-start" />
                    <SparklesIcon v-else data-icon="inline-start" />
                    Parse
                </Button>
            </div>

            <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
            <p v-else class="text-xs text-muted-foreground">Tip: press Ctrl+Enter to parse</p>
        </div>

    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Loader2Icon, SparklesIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useAiParser } from '@/composables/useAiParser';
import type { BulkInsertEntry } from '@/types/api';

const emit = defineEmits<{
    'entry-parsed': [entry: BulkInsertEntry];
}>();

const message          = ref('');
const { loading, error, parse } = useAiParser();

async function onParse(): Promise<void> {
    if (!message.value.trim() || loading.value) return;

    const entry = await parse(message.value.trim());

    if (entry) {
        message.value = '';
        emit('entry-parsed', entry);
    }
}
</script>
