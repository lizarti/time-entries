<?php

namespace App\Actions\TimeEntry;

use App\Exceptions\ConflictException;
use App\Models\TimeEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkInsertTimeEntriesAction
{
    public function execute(array $entries): Collection
    {
        $this->checkIntraBatchConflicts($entries);

        return DB::transaction(function () use ($entries): Collection {
            $ids = collect($entries)->map(function (array $entry): int {
                $this->checkDatabaseConflict($entry);

                return TimeEntry::create($entry)->id;
            });

            return TimeEntry::with(['company', 'employee', 'project', 'task'])
                ->whereIn('id', $ids)
                ->get();
        });
    }

    private function checkIntraBatchConflicts(array $entries): void
    {
        $conflict = collect($entries)
            ->groupBy(fn ($e) => $e['employee_id'] . '|' . $e['date'])
            ->contains(fn ($group) => $group->pluck('project_id')->unique()->count() > 1);

        if ($conflict) {
            throw new ConflictException(
                'An employee cannot be assigned to more than one project on the same date within the same batch.'
            );
        }
    }

    private function checkDatabaseConflict(array $entry): void
    {
        $conflict = TimeEntry::where('employee_id', $entry['employee_id'])
            ->where('date', $entry['date'])
            ->where('project_id', '!=', $entry['project_id'])
            ->exists();

        if ($conflict) {
            throw new ConflictException(
                "Employee {$entry['employee_id']} already has a different project on {$entry['date']}."
            );
        }
    }
}
