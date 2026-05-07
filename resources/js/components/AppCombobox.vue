<template>
    <Combobox v-model="model" :disabled="disabled">
        <ComboboxAnchor class="w-full">
            <ComboboxTrigger class="w-full">
                <Button
                    variant="outline"
                    class="w-full justify-between font-normal"
                    :class="{ 'text-muted-foreground': !selectedLabel }"
                    :disabled="disabled"
                    type="button"
                >
                    <span class="truncate">{{ selectedLabel || placeholder }}</span>
                    <ChevronsUpDownIcon class="ml-2 shrink-0 opacity-50" />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxInput :placeholder="searchPlaceholder" />
            <ComboboxViewport>
                <ComboboxGroup>
                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        <ComboboxItemIndicator>
                            <CheckIcon class="size-4" />
                        </ComboboxItemIndicator>
                        {{ option.label }}
                    </ComboboxItem>
                </ComboboxGroup>
                <ComboboxEmpty>No results found.</ComboboxEmpty>
            </ComboboxViewport>
        </ComboboxList>
    </Combobox>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ChevronsUpDownIcon, CheckIcon } from 'lucide-vue-next';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';

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
}>(), {
    placeholder: 'Select...',
    searchPlaceholder: 'Search...',
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const model = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value),
});

const selectedLabel = computed(
    () => props.options.find(o => o.value === props.modelValue)?.label ?? '',
);
</script>
