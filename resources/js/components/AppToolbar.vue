<template>
   <!-- Toolbar -->
      <div class="flex items-center justify-between px-6 py-4">
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
              Time Tracker
          </h1>
          <div class="w-64">
            <CompanyDropdown />
          </div>
      </div>
</template>

<script setup lang="ts">
import CompanyDropdown from '@/components/CompanyDropdown.vue';

</script>