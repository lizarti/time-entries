<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SummarizeTimeEntriesAction
{
    public function execute(array $filters): array
    {
        $base = TimeEntry::query()->withFilters($filters);

        return [
            'by_employee' => $this->aggregateByRelation(clone $base, 'employees', 'employee_id'),
            'by_project'  => $this->aggregateByRelation(clone $base, 'projects',  'project_id'),
            'by_task'     => $this->aggregateByRelation(clone $base, 'tasks',     'task_id'),
            'by_company'  => $this->aggregateByRelation(clone $base, 'companies', 'company_id'),
            'by_date'     => $this->aggregateByDate(clone $base),
        ];
    }

    private function aggregateByRelation(Builder $query, string $table, string $fk): Collection
    {
        return $query
            ->join($table, "time_entries.{$fk}", '=', "{$table}.id")
            ->groupBy("{$table}.id", "{$table}.name")
            ->selectRaw("{$table}.name as label, SUM(time_entries.hours) as hours")
            ->orderByDesc('hours')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);
    }

    private function aggregateByDate(Builder $query): Collection
    {
        return $query
            ->selectRaw('DATE(date) as label, SUM(hours) as hours')
            ->groupByRaw('DATE(date)')
            ->orderBy('label', 'asc')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);
    }
}
