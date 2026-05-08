<?php

namespace App\Actions\TimeEntry;

use App\Exceptions\ConflictException;
use App\Models\TimeEntry;

class UpdateTimeEntryAction
{
    public function execute(TimeEntry $entry, array $data): TimeEntry
    {
        $this->checkConflict($entry, $data);

        $entry->update($data);

        return $entry->load(['company', 'employee', 'project', 'task']);
    }

    private function checkConflict(TimeEntry $entry, array $data): void
    {
        $conflict = TimeEntry::where('employee_id', $data['employee_id'])
            ->where('date', $data['date'])
            ->where('project_id', '!=', $data['project_id'])
            ->where('id', '!=', $entry->id)
            ->exists();

        if ($conflict) {
            throw new ConflictException(
                "Employee {$data['employee_id']} already has a different project on {$data['date']}."
            );
        }
    }
}
