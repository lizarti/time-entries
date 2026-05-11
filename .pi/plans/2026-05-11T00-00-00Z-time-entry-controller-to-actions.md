---
date: 2026-05-11T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, refactor, actions, controllers, time-entry]
last_updated: 2026-05-11
---

# Refactor TimeEntryController to actions

Extract the `index` and `summary` logic out of `TimeEntryController` into dedicated
single-responsibility Actions, leaving the controller as a thin dispatcher.

## Goal

`TimeEntryController` will contain no business or query logic — only input extraction
and delegation. All filtering, sorting, pagination and aggregation will live in their
respective Action classes, each independently testable.

## Target Structure

```
app/
  Actions/
    TimeEntry/
      BulkInsertTimeEntriesAction.php   ← unchanged
      UpdateTimeEntryAction.php         ← unchanged
      ListTimeEntriesAction.php         ← new
      SummarizeTimeEntriesAction.php    ← new
  Models/
    TimeEntry.php                       ← new: scopeWithFilters()
```

---

## Steps

### Step 1 — TimeEntry filter scope

Extract the shared `filteredQuery` logic from the controller into a reusable Eloquent
local scope on the `TimeEntry` model so both new Actions can consume it without duplication.

- [x] Add `scopeWithFilters(Builder $query, array $filters): Builder` to `TimeEntry`
  - Moves the seven `->when()` clauses (company, employee, project, task, date_from, date_to, search) verbatim from `filteredQuery()`
  - Accepts a plain `array $filters` — no dependency on `Request`
- [x] Verify the scope can be called as `TimeEntry::query()->withFilters($filters)`

---

### Step 2 — ListTimeEntriesAction

Extract all listing logic — filtering, sorting, and pagination — into a dedicated Action.

- [x] Create `App\Actions\TimeEntry\ListTimeEntriesAction`
  - Signature: `execute(array $filters, string $sortBy, string $sortDir, int $perPage): LengthAwarePaginator`
  - Uses `TimeEntry::query()->withFilters($filters)` for filtering
  - Moves the relation-join sort logic (`employee`, `project`, `task`, `company`) and the scalar-column sort (`date`, `hours`) verbatim from the controller
  - Applies `->with([...])` eager-loading and `->paginate($perPage)`
- [x] Update `TimeEntryController@index` to extract params from `Request` and delegate entirely to the Action
- [x] Remove the `filteredQuery()` private method from the controller once both actions are in place

---

### Step 3 — SummarizeTimeEntriesAction

Extract all aggregation logic into a dedicated Action.

- [x] Create `App\Actions\TimeEntry\SummarizeTimeEntriesAction`
  - Signature: `execute(array $filters): array`
  - Uses `TimeEntry::query()->withFilters($filters)` as the base query
  - Moves the five `(clone $base)->join()->groupBy()->selectRaw()->get()->map()` blocks (by_employee, by_project, by_task, by_date, by_company) verbatim from the controller
  - Returns the shaped `['by_employee' => ..., 'by_project' => ..., ...]` array
- [x] Update `TimeEntryController@summary` to extract params from `Request` and delegate entirely to the Action
- [x] Delete the now-empty `filteredQuery()` method from the controller

---

### Step 4 — Tests

Cover both new Actions with focused unit/feature tests and verify the controller stays thin.

- [x] Feature tests for `ListTimeEntriesAction` via `GET /time-entries`
  - Filtering by each param (already partially covered — confirm they still pass)
  - Sorting by relation name (`employee`, `project`) and by scalar (`date`, `hours`)
  - Pagination: correct `per_page` cap at 100
- [x] Feature tests for `SummarizeTimeEntriesAction` via `GET /time-entries/summary`
  - Returns correct structure (`by_employee`, `by_project`, `by_task`, `by_date`, `by_company`)
  - Aggregated `hours` values are correct
  - Filters are respected (already partially covered — confirm they still pass)
- [x] Assert `TimeEntryController` has no methods longer than ~10 lines

---

## Notes

- The `filteredQuery()` private method on the controller is the shared dependency between `index` and `summary`. Moving it to `TimeEntry::scopeWithFilters()` is the idiomatic Eloquent solution — it keeps both Actions self-contained without a shared base class or trait.
- Actions receive plain data (`array $filters`, `string $sortBy`, etc.) — not a `Request` object. The controller is solely responsible for extracting and passing typed values.
- `bulkStore` and `update` already delegate to Actions and are not in scope.
- `parseUsingAI` is small but also contains logic (AI agent instantiation, JSON decoding, error handling). It is a candidate for a follow-up `ParseTimeEntryWithAIAction` but is intentionally excluded from this plan to keep scope tight.
