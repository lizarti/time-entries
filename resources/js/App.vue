<template>
    <div class="min-h-screen bg-background">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="overflow-hidden rounded-lg border border-border bg-card shadow-[0_1px_3px_rgba(0,0,0,0.08)]">

                <!-- Toolbar -->
                <div class="flex items-center justify-between border-b border-border px-6 py-5">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        Time Tracker
                    </h1>
                    <CompanyDropdown />
                </div>

                <!-- Tab content -->
                <div class="px-6 py-5">
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
        </div>
    </div>
</template>

<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import CompanyDropdown from '@/components/CompanyDropdown.vue';
import NewEntriesTab from '@/components/NewEntriesTab.vue';
import HistoryTab from '@/components/HistoryTab.vue';
</script>
