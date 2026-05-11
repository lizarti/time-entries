<template>
    <div class="min-h-screen bg-background">

        <!-- Sticky header -->
        <header class="sticky top-0 z-10 border-b border-border bg-card">
            <AppToolbar />
        </header>

        <!-- Page content -->
        <div class="mx-auto max-w-7xl px-6 py-6">
            <Tabs default-value="new-entries" class="gap-12">
                <TabsList class="flex h-auto w-full justify-start rounded-none bg-transparent">
                    <TabsTrigger
                        value="new-entries"
                        class="relative h-auto flex-none rounded-none bg-transparent px-5 py-3 font-semibold text-muted-foreground shadow-none transition-colors hover:text-foreground data-[state=active]:bg-transparent data-[state=active]:text-primary data-[state=active]:shadow-none after:absolute after:bottom-[-1px] after:left-0 after:h-0.5 after:w-full after:bg-primary after:opacity-0 after:transition-opacity data-[state=active]:after:opacity-100"
                    >New Entries</TabsTrigger>
                    <TabsTrigger
                        value="history"
                        class="relative h-auto flex-none rounded-none bg-transparent px-5 py-3 font-semibold text-muted-foreground shadow-none transition-colors hover:text-foreground data-[state=active]:bg-transparent data-[state=active]:text-primary data-[state=active]:shadow-none after:absolute after:bottom-[-1px] after:left-0 after:h-0.5 after:w-full after:bg-primary after:opacity-0 after:transition-opacity data-[state=active]:after:opacity-100"
                    >History</TabsTrigger>
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
import NewEntriesTab from '@/components/NewEntriesTab.vue';
import HistoryTab from '@/components/HistoryTab.vue';
import AppToolbar from '@/components/AppToolbar.vue';
</script>
