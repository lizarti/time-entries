---
date: 2026-05-08T00:00:00Z
branch: main
repository: time-tracker
status: ready
tags: [plan, history, filters, search, sort, pagination, summary]
last_updated: 2026-05-08
---

# History table improvements

The History tab currently loads all time entries in one unfiltered, unsorted, unpaginated
request. This plan adds server-side filtering, full-text search, sorting, and pagination to
the API, then wires up a filter bar, sortable headers, and pagination controls on the
frontend.

## Goal

`GET /time-entries` will accept filter, search, sort, and pagination parameters so the
frontend never loads the whole table at once. A companion `GET /time-entries/summary`
endpoint will return aggregated totals under the same filters. The History tab will expose
all controls to the user and remain fully reactive to changes.

---

## Steps

### Step 1 — Backend: filters, search, and sort on `GET /time-entries`

Extend the `index` query with every new parameter, keeping `company_id` unchanged.

- [x] Add filter params to `TimeEntryController::index()`
  - `employee_id` — `where('time_entries.employee_id', ...)`
  - `project_id` — `where('time_entries.project_id', ...)`
  - `task_id` — `where('time_entries.task_id', ...)`
  - `date_from` / `date_to` — `whereDate('time_entries.date', '>=', ...)` / `<=`
- [x] Add `search` param — LIKE search across all four name columns using `whereHas`
  subqueries (employee.name, project.name, task.name, company.name)
- [x] Add sorting
  - `sort_by` (default `date`) — allowed values: `date`, `hours`, `employee`,
    `project`, `task`, `company`
  - `sort_dir` (default `desc`) — `asc` | `desc`
  - Sorting by `date` or `hours` uses `orderBy` on the main table
  - Sorting by a relation name requires a `join()` + `select('time_entries.*')` to
    avoid column ambiguity; the join is only applied when `sort_by` targets a relation

---

### Step 2 — Backend: pagination on `GET /time-entries`

Switch the full-table dump to a paginated response.

- [x] Replace `->get()` with `->paginate($perPage)` where
  `$perPage = min((int) $request->input('per_page', 25), 100)`
- [x] Laravel's `ResourceCollection` on a `LengthAwarePaginator` automatically injects
  a `meta` key; no manual serialisation needed
- [x] Update the return type annotation from `AnonymousResourceCollection` to reflect
  the paginated shape (or leave it — the runtime behaviour is correct either way)

---

### Step 3 — Backend: summary endpoint

A lightweight aggregation endpoint driven by the same filter set.

- [x] Add `summary()` to `TimeEntryController`
  - Accepts the same filter params as `index` (`company_id`, `employee_id`,
    `project_id`, `task_id`, `date_from`, `date_to`, `search`) — no pagination params
  - Runs five DB-level GROUP BY + SUM queries, one per dimension
  - Returns `JsonResponse` shaped as:
    ```json
    {
      "data": {
        "by_employee": [{ "label": "Alice", "hours": 40.0 }, ...],
        "by_project":  [...],
        "by_task":     [...],
        "by_date":     [...],
        "by_company":  [...]
      }
    }
    ```
  - Each dimension is sorted by `hours DESC`; `by_date` is sorted by `date ASC`
- [x] Register `Route::get('/time-entries/summary', ...)` **before** the
  `{time_entry}` wildcard route (from the history-edit plan) to prevent the literal
  string `summary` being bound as a model ID

---

### Step 4 — Backend: tests

Cover all new query parameters and the summary endpoint.

- [x] Filters: one test per param (`employee_id`, `project_id`, `task_id`,
  `date_from`, `date_to`) — assert correct subset returned
- [x] Search: test that matching on each name field (employee, project, task, company)
  returns the right entries
- [x] Sorting: test `sort_by=date asc`, `date desc`, `hours`, `employee`, `project`,
  `task`, `company` — assert first item is the expected one
- [x] Pagination: test `per_page` respected, `meta` keys present (`current_page`,
  `last_page`, `per_page`, `total`), `page=2` returns the correct slice
- [x] `per_page` cap: assert requesting `per_page=9999` returns at most 100 items
- [x] Summary endpoint: assert correct structure; assert totals match seeded data;
  assert filters are honoured (e.g. `company_id` limits which entries are summed)

---

### Step 5 — Frontend: types and service layer

Align the TypeScript layer with the new API shape.

- [x] Add to `resources/js/types/api.ts`:
  - `TimeEntryFilters` — all optional: `company_id?`, `employee_id?`, `project_id?`,
    `task_id?`, `date_from?`, `date_to?`, `search?`
  - `TimeEntrySortParams` — `sort_by?: string`, `sort_dir?: 'asc' | 'desc'`
  - `PaginationMeta` — `current_page`, `last_page`, `per_page`, `total`
  - `SummaryRow` — `{ label: string; hours: number }` (replaces the same type in the
    summary plan)
  - `SummaryData` — `{ by_employee, by_project, by_task, by_date, by_company }` each
    `SummaryRow[]`
- [x] Update `timeEntryService.getAll()` to accept `TimeEntryFilters &
  TimeEntrySortParams & { page?: number; per_page?: number }` and build the query
  string from all provided params
- [x] Update `getAll()` return type from `Promise<TimeEntry[]>` to
  `Promise<{ data: TimeEntry[]; meta: PaginationMeta }>`
- [x] Add `timeEntryService.getSummary(filters: TimeEntryFilters): Promise<SummaryData>`
  calling `GET /time-entries/summary`

---

### Step 6 — Frontend: update `useTimeEntries` composable

Centralise filter/page/sort state and drive fetches reactively.

- [x] Replace `fetch(companyId?)` with a reactive `filters` ref of type
  `TimeEntryFilters & TimeEntrySortParams & { page: number; per_page: number }`
- [x] Expose `meta: Ref<PaginationMeta | null>` alongside `entries`
- [x] Add `setFilters(partial: Partial<TimeEntryFilters>)` — merges into `filters`,
  resets `page` to 1, triggers a fetch
- [x] Add `setSort(sort_by: string, sort_dir: 'asc' | 'desc')` — updates sort params,
  resets page, triggers fetch
- [x] Add `setPage(page: number)` — updates page, triggers fetch without resetting it
- [x] Add `summary: Ref<SummaryData | null>` and fetch it in parallel with entries
  (same filter params, no pagination)
- [x] Update `HistoryTab.vue` to call `setFilters({ company_id: company?.id })` when
  `selectedCompany` changes, instead of calling `fetch()` directly

---

### Step 7 — Frontend: filter bar, sortable headers, pagination

Wire all controls into `HistoryTab.vue`.

- [x] Create `resources/js/components/HistoryFilterBar.vue`
  - Search text input (debounced ~300 ms before calling `setFilters`)
  - Employee, Project, Task comboboxes (using existing `AppCombobox`, options scoped
    to the selected company)
  - Date-from / Date-to date pickers
  - "Clear filters" button that resets to defaults
  - Emits filter changes up via `setFilters` from the composable
- [x] Add sort indicators to `TableHead` cells in `HistoryTab.vue`
  - Clicking a header calls `setSort(column, toggledDirection)`
  - Active column shows an up/down arrow icon
- [x] Add a pagination row below the table
  - Previous / Next buttons (disabled at boundaries)
  - "Page X of Y" label
  - Per-page selector (25 / 50 / 100)
  - Calls `setPage()` / `setPerPage()` accordingly
- [x] Update skeleton row count to reflect `filters.per_page` rather than hardcoded 5
- [x] Place `<HistoryFilterBar>` above `<HistorySummary>` and `<Table>` in layout order

---

## Notes

- Pagination is the forcing function for moving summary aggregation to the backend —
  frontend computed properties only see the current page, not the full dataset.
- Sorting by relation name columns (employee, project, task, company) requires a `join()`
  in the query. Use `select('time_entries.*')` to prevent wildcard column collisions.
- `GET /time-entries/summary` must be declared before any `{time_entry}` route-model
  binding route or Laravel will try to resolve `"summary"` as an integer ID.
- The `search` param uses `whereHas` subqueries rather than explicit joins, keeping the
  query builder simpler at the cost of slightly less optimal SQL — acceptable for this
  dataset size.
- `setFilters` resets `page` to 1; `setPage` does not reset filters. This asymmetry is
  intentional — changing a filter always takes you back to the first page.
- Debouncing the search input on the frontend avoids hammering the API on every keystroke.
- The `HistoryFilterBar` combobox options should only load when a company is selected,
  matching the behaviour already in `NewEntriesTab`.
