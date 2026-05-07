<template>
    <div class="flex flex-col gap-4 pt-4">

        <!-- Error -->
        <div
            v-if="error"
            class="rounded-md border border-destructive bg-destructive/10 px-4 py-3 text-sm text-destructive"
        >
            {{ error }}
        </div>

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Company</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Employee</TableHead>
                    <TableHead>Project</TableHead>
                    <TableHead>Task</TableHead>
                    <TableHead class="text-right">Hours</TableHead>
                </TableRow>
            </TableHeader>

            <!-- Loading skeletons -->
            <TableBody v-if="loading">
                <TableRow v-for="n in 5" :key="n">
                    <TableCell v-for="col in 6" :key="col">
                        <Skeleton class="h-4 w-24" />
                    </TableCell>
                </TableRow>
            </TableBody>

            <!-- Empty state -->
            <TableBody v-else-if="entries.length === 0">
                <TableRow>
                    <TableCell colspan="6" class="py-12 text-center text-muted-foreground">
                        No time entries found.
                    </TableCell>
                </TableRow>
            </TableBody>

            <!-- Data -->
            <TableBody v-else>
                <TableRow v-for="entry in entries" :key="entry.id">
                    <TableCell>{{ entry.company.name }}</TableCell>
                    <TableCell>{{ entry.date }}</TableCell>
                    <TableCell>{{ entry.employee.name }}</TableCell>
                    <TableCell>{{ entry.project.name }}</TableCell>
                    <TableCell>{{ entry.task.name }}</TableCell>
                    <TableCell class="text-right tabular-nums">{{ entry.hours }}</TableCell>
                </TableRow>
            </TableBody>
        </Table>

    </div>
</template>

<script setup lang="ts">
import { watch, onMounted } from 'vue';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
import { useSelectedCompany } from '@/composables/useSelectedCompany';
import { useTimeEntries } from '@/composables/useTimeEntries';

const { selectedCompany } = useSelectedCompany();
const { entries, loading, error, fetch } = useTimeEntries();

onMounted(() => fetch(selectedCompany.value?.id));

watch(selectedCompany, (company) => {
    fetch(company?.id);
});
</script>
