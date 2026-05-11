<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <button
                type="button"
                role="combobox"
                :aria-expanded="open"
                :disabled="disabled"
                :class="cn(
                    'flex h-9 w-full items-center justify-between rounded-md border border-border bg-white px-3 text-sm text-foreground transition-colors hover:border-ring/40 focus:outline-none focus:border-primary/60 focus:ring-3 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-50',
                    !selectedLabel && 'text-muted-foreground',
                    props.class,
                )"
            >
                <span class="truncate">{{ selectedLabel || placeholder }}</span>
                <ChevronsUpDownIcon class="ml-2 size-4 shrink-0 opacity-40" />
            </button>
        </PopoverTrigger>
        <PopoverContent class="w-[--reka-popover-trigger-width] p-0">
            <Command
                :model-value="modelValue"
                @update:model-value="(v) => { emit('update:modelValue', v as string); open = false }"
            >
                <CommandInput :placeholder="searchPlaceholder" @keydown="onInputKeydown" />
                <CommandList>
                    <CommandEmpty>No results found.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="option in options"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { ChevronsUpDownIcon } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

export interface ComboboxOption {
    value: string;
    label: string;
}

const props = withDefaults(defineProps<{
    modelValue: string;
    options: ComboboxOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    class?: HTMLAttributes['class'];
}>(), {
    placeholder: 'Select...',
    searchPlaceholder: 'Search...',
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const open = ref(false);
const virtualFocusEl = ref<HTMLElement | null>(null);

watch(open, (val) => { if (!val) clearVirtualFocus(); });

function clearVirtualFocus() {
    virtualFocusEl.value?.removeAttribute('data-virtual-focus');
    virtualFocusEl.value = null;
}

function onInputKeydown(e: KeyboardEvent) {
    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') return;

    const command = (e.currentTarget as HTMLElement).closest('[data-slot="command"]');
    if (!command) return;
    const items = Array.from(
        command.querySelectorAll<HTMLElement>('[data-slot="command-item"]:not([data-disabled="true"])')
    );

    if (e.key === 'Enter') {
        if (virtualFocusEl.value && items.includes(virtualFocusEl.value)) {
            e.preventDefault();
            virtualFocusEl.value.click();
        }
        return;
    }

    if (!items.length) return;
    e.preventDefault();

    const idx = virtualFocusEl.value ? items.indexOf(virtualFocusEl.value) : -1;
    const next = e.key === 'ArrowDown'
        ? (idx + 1) % items.length
        : (idx - 1 + items.length) % items.length;

    clearVirtualFocus();
    virtualFocusEl.value = items[next];
    virtualFocusEl.value.setAttribute('data-virtual-focus', 'true');
    virtualFocusEl.value.scrollIntoView({ block: 'nearest' });
}

const selectedLabel = computed(
    () => props.options.find(o => o.value === props.modelValue)?.label ?? '',
);
</script>
