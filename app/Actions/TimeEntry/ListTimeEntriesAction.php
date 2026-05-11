<?php

namespace App\Actions\TimeEntry;

use App\Models\TimeEntry;
use Illuminate\Pagination\LengthAwarePaginator;

class ListTimeEntriesAction
{
    private const RELATION_SORTS = [
        'employee' => ['employees', 'employee_id'],
        'project'  => ['projects',  'project_id'],
        'task'     => ['tasks',     'task_id'],
        'company'  => ['companies', 'company_id'],
    ];

    private const SCALAR_SORTS = ['date', 'hours'];

    public function execute(
        array  $filters,
        string $sortBy,
        string $sortDir,
        int    $perPage,
    ): LengthAwarePaginator {
        $query = TimeEntry::query()
            ->withFilters($filters)
            ->with(['company', 'employee', 'project', 'task'])
            ->select('time_entries.*');

        if (isset(self::RELATION_SORTS[$sortBy])) {
            [$table, $fk] = self::RELATION_SORTS[$sortBy];
            $query->join($table, "time_entries.{$fk}", '=', "{$table}.id")
                  ->orderBy("{$table}.name", $sortDir);
        } else {
            $column = in_array($sortBy, self::SCALAR_SORTS) ? $sortBy : 'date';
            $query->orderBy("time_entries.{$column}", $sortDir);
        }

        return $query->paginate($perPage);
    }
}
