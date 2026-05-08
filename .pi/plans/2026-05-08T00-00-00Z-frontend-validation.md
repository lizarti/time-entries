---
date: 2026-05-08T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, validation, NewTimeEntryRow, NewEntriesTab]
last_updated: 2026-05-08
---

# Frontend validation for time entry form

Client-side validation will be added to the bulk time-entry form so that errors surface
immediately, before any network request is made. The primary business rule — one employee
may only work on one project per date — will be enforced in the browser as well as on
the server.

## Goal

`NewEntriesTab.vue` will validate all rows before calling `bulkInsert`, blocking submission
and populating `rowErrors` with friendly messages when rules are violated.
`NewTimeEntryRow.vue` will render those errors as visible text beneath each field and will
ask the parent to clear a field's error the moment the user changes it.

---

## Steps

### Step 1 — Show inline error text in NewTimeEntryRow.vue

Currently only `date` and `hours` receive `border-destructive`; no readable message is
ever shown. This step makes all six fields display their error text beneath the control.

- [x] Wrap each field cell content in a `<div class="flex flex-col gap-1">` to stack the
      control and the error message vertically
- [x] Add `<p v-if="fieldError('X')" class="text-xs text-destructive">{{ fieldError('X') }}</p>`
      beneath every field: `company_id`, `date`, `employee_id`, `project_id`, `task_id`, `hours`
- [x] Apply `border-destructive` (or equivalent prop) to the `AppCombobox` triggers for
      `employee_id`, `project_id`, `task_id`, and `company_id` — currently only the date
      button and hours input get it
      - `AppCombobox` already forwards a `class` prop to its internal trigger; check and
        confirm this works, or add a dedicated `:error` / `:class` binding

---

### Step 2 — Required-field validation before submission

Add a `validateRows()` function in `NewEntriesTab.vue` that runs synchronously before
`bulkInsert` is called, populating `rowErrors` with friendly messages and returning `false`
if anything fails.

- [x] Write `validateRows(): boolean` in `NewEntriesTab.vue`
  - Iterate over `rows.value` with index
  - For each row check:
    - `company_id` is truthy → `"Company is required"`
    - `date` is non-empty → `"Date is required"`
    - `employee_id` is truthy → `"Employee is required"`
    - `project_id` is truthy → `"Project is required"`
    - `task_id` is truthy → `"Task is required"`
    - `hours > 0` → `"Hours must be greater than 0"`
  - Accumulate into a local `Record<number, Record<string, string>>`; assign to
    `rowErrors.value` only once at the end
  - Return `false` if any error was found, `true` otherwise
- [x] In `submit()`, call `validateRows()` before the `try` block and `return` early
      if it returns `false`
- [x] Set `submitError.value` to a summary like
      `'Please fix the highlighted errors before submitting.'` when validation fails

---

### Step 3 — Cross-row business-rule validation

Extend `validateRows()` (or add a dedicated `validateConflicts()` called from the same
place) to detect the employee-per-project-per-date rule across rows in the current batch.

- [x] After the per-field required checks, build a conflict map:
  ```
  key = `${employee_id}|${date}`
  value = set of distinct project_ids for that key
  ```
- [x] If any key maps to more than one distinct `project_id`, mark **every row** that
      participates in that conflict with an error on `project_id`:
      `"This employee is already assigned to a different project on this date"`
- [x] Return `false` if any conflict was found; the required-field errors and conflict
      errors can coexist in the same `rowErrors` object

---

### Step 4 — Live error clearing on field change

Errors should disappear as soon as the user corrects the offending field, not wait for
the next submission attempt.

- [x] Add an `emit('clear-error', field: string)` event to `NewTimeEntryRow.vue`
  - Fire it inside each update handler as the first thing, before emitting
    `'update:modelValue'`
  - For `onCompanyChange`, clear `company_id`, `employee_id`, `project_id`, and `task_id`
    (because changing the company resets all dependents)
- [x] In `NewEntriesTab.vue`, handle `@clear-error="(field) => clearRowError(index, field)"`
  - `clearRowError(index, field)`: delete `rowErrors.value[index]?.[field]`; if the row's
    error object becomes empty, delete the row key too
- [x] Also clear `submitError` (the banner) when all `rowErrors` are gone, or at least when
      the user starts fixing things — simplest approach: clear the banner on any field
      change if `rowErrors` is now empty

---

## Notes

- The database-level conflict (employee already has a different project on this date in a
  *previous* submission) cannot be caught client-side without an extra API call. That case
  will still surface via the backend 409/422 error path that already exists; Step 3 only
  covers intra-batch conflicts visible in the current set of rows.
- `AppCombobox` forwards `class` to its trigger button — verify this before Step 1;
  if it doesn't, a small change to `AppCombobox.vue` may be needed.
- Hours `0` is the initial blank value (`blankRow()` sets `hours: 0`), so the "hours > 0"
  check correctly catches untouched rows.
- No new composable or service file is needed; all logic lives in the two existing
  components.
