<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn('border-b border-[#F0F1F3] hover:bg-[#FAFAFA] transition-colors has-aria-expanded:bg-[#FAFAFA]', props.class)"
  >
    <slot />
  </tr>
</template>
