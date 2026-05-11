<?php

namespace App\Http\Controllers;

use App\Actions\TimeEntry\BulkInsertTimeEntriesAction;
use App\Actions\TimeEntry\UpdateTimeEntryAction;
use App\Ai\Agents\TimeTracker;
use App\Http\Requests\BulkInsertTimeEntriesRequest;
use App\Http\Requests\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TimeEntryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sortBy  = $request->input('sort_by', 'date');
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc'])
            ? $request->input('sort_dir')
            : 'desc';

        $query = $this->filteredQuery($request)
            ->with(['company', 'employee', 'project', 'task'])
            ->select('time_entries.*');

        // Sorting by a relation name requires a JOIN so we can ORDER BY the name column.
        $relationSorts = [
            'employee' => ['employees', 'employee_id'],
            'project'  => ['projects',  'project_id'],
            'task'     => ['tasks',     'task_id'],
            'company'  => ['companies', 'company_id'],
        ];

        if (isset($relationSorts[$sortBy])) {
            [$table, $fk] = $relationSorts[$sortBy];
            $query->join($table, "time_entries.{$fk}", '=', "{$table}.id")
                  ->orderBy("{$table}.name", $sortDir);
        } else {
            $column = in_array($sortBy, ['date', 'hours']) ? $sortBy : 'date';
            $query->orderBy("time_entries.{$column}", $sortDir);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return TimeEntryResource::collection($query->paginate($perPage));
    }

    public function summary(Request $request): JsonResponse
    {
        $base = $this->filteredQuery($request);

        $byEmployee = (clone $base)
            ->join('employees', 'time_entries.employee_id', '=', 'employees.id')
            ->groupBy('employees.id', 'employees.name')
            ->selectRaw('employees.name as label, SUM(time_entries.hours) as hours')
            ->orderByDesc('hours')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);

        $byProject = (clone $base)
            ->join('projects', 'time_entries.project_id', '=', 'projects.id')
            ->groupBy('projects.id', 'projects.name')
            ->selectRaw('projects.name as label, SUM(time_entries.hours) as hours')
            ->orderByDesc('hours')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);

        $byTask = (clone $base)
            ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
            ->groupBy('tasks.id', 'tasks.name')
            ->selectRaw('tasks.name as label, SUM(time_entries.hours) as hours')
            ->orderByDesc('hours')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);

        $byDate = (clone $base)
            ->selectRaw('DATE(date) as label, SUM(hours) as hours')
            ->groupByRaw('DATE(date)')
            ->orderBy('label', 'asc')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);

        $byCompany = (clone $base)
            ->join('companies', 'time_entries.company_id', '=', 'companies.id')
            ->groupBy('companies.id', 'companies.name')
            ->selectRaw('companies.name as label, SUM(time_entries.hours) as hours')
            ->orderByDesc('hours')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'hours' => (float) $r->hours]);

        return response()->json([
            'data' => [
                'by_employee' => $byEmployee,
                'by_project'  => $byProject,
                'by_task'     => $byTask,
                'by_date'     => $byDate,
                'by_company'  => $byCompany,
            ],
        ]);
    }

    public function update(
        UpdateTimeEntryRequest $request,
        UpdateTimeEntryAction  $action,
        TimeEntry              $timeEntry,
    ): JsonResponse {
        try {
            $entry = $action->execute($timeEntry, $request->validated());
        } catch (\App\Exceptions\ConflictException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new TimeEntryResource($entry))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function bulkStore(
        BulkInsertTimeEntriesRequest $request,
        BulkInsertTimeEntriesAction $action,
    ): JsonResponse {
        $entries = $action->execute($request->input('entries'));

        return TimeEntryResource::collection($entries)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    // ─── Shared filter builder ────────────────────────────────────────────────

    private function filteredQuery(Request $request): Builder
    {
        return TimeEntry::query()
            ->when(
                $request->filled('company_id'),
                fn ($q) => $q->where('company_id', $request->input('company_id')),
            )
            ->when(
                $request->filled('employee_id'),
                fn ($q) => $q->where('employee_id', $request->input('employee_id')),
            )
            ->when(
                $request->filled('project_id'),
                fn ($q) => $q->where('project_id', $request->input('project_id')),
            )
            ->when(
                $request->filled('task_id'),
                fn ($q) => $q->where('task_id', $request->input('task_id')),
            )
            ->when(
                $request->filled('date_from'),
                fn ($q) => $q->whereDate('date', '>=', $request->input('date_from')),
            )
            ->when(
                $request->filled('date_to'),
                fn ($q) => $q->whereDate('date', '<=', $request->input('date_to')),
            )
            ->when(
                $request->filled('search'),
                function ($q) use ($request) {
                    $search = $request->input('search');
                    $q->where(function ($inner) use ($search) {
                        $inner->whereHas('employee', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                              ->orWhereHas('project',  fn ($q) => $q->where('name', 'like', "%{$search}%"))
                              ->orWhereHas('task',     fn ($q) => $q->where('name', 'like', "%{$search}%"))
                              ->orWhereHas('company',  fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    });
                },
            );
    }

     public function parseUsingAI(Request $request): JsonResponse {
        $agent = new TimeTracker();
        $userMessage = $request->input('message');
        if (empty($userMessage)) {
            return response()->json(['message' => 'Message is required.'], 400);
        }

        try {
            $agentResponse = $agent->prompt($userMessage);
            $jsonResponse = json_decode($agentResponse->text);
    
            return response()->json($jsonResponse, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unable to create time entry. Invalid or missing information.'], 422);
        }

    }
}
