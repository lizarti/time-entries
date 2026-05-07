<template>
    <div class="min-h-screen bg-background text-foreground">
        <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col gap-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold tracking-tight">Time Tracker</h1>
                <CompanyDropdown />
            </div>

            <!-- Tabs -->
            <Tabs default-value="new-entries">
                <TabsList>
                    <TabsTrigger value="new-entries">New Entries</TabsTrigger>
                    <TabsTrigger value="history">History</TabsTrigger>
                </TabsList>

                <TabsContent value="new-entries">
                    <NewEntriesTab />
                </TabsContent>

                <TabsContent value="history">
                    <HistoryTab />
                </TabsContent>
            </Tabs>

        </div>
    </div>
</template>

<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import CompanyDropdown from '@/components/CompanyDropdown.vue';
import NewEntriesTab from '@/components/NewEntriesTab.vue';
import HistoryTab from '@/components/HistoryTab.vue';
</script>
