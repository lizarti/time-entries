---
date: 2026-05-07T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, vue, components, composables, new-entries, history]
last_updated: 2026-05-07
---

# Vue application components

Build the complete Vue UI on top of the existing service layer: composables, the global
company shell, the New Entries tab with its per-row logic, and the read-only History tab.

## Goal

The application will be fully functional as a single-page experience — a global company
dropdown controlling both tabs, a New Entries table where users build and submit bulk time
entries, and a History table that re-fetches live data whenever the company selection changes.

## Target Structure

```
resources/js/
  composables/
    useSelectedCompany.ts    ← global singleton state
    useCompanies.ts
    useEmployees.ts
    useProjects.ts
    useTasks.ts
    useTimeEntries.ts
  components/
    ui/                       ← shadcn-vue generated (added via CLI per step)
    CompanyDropdown.vue
    NewEntriesTab.vue
    NewTimeEntryRow.vue
    HistoryTab.vue
  App.vue                     ← updated: full shell with dropdown + tabs
```

---

## Steps

### Step 1 — Composables

Create all five composables that wrap the service layer in reactive Vue state.

- [x] `useSelectedCompany`
  - `selectedCompany: Ref<Company | null>` defined at **module scope** — shared singleton across all consumers
  - `setSelectedCompany(company: Company | null)` — mutation helper
- [x] `useCompanies`
  - `companies: Ref<Company[]>`, `loading: Ref<boolean>`
  - `fetch(search?: string)` — calls `companyService.getAll`
  - Auto-fetches on mount
- [x] `useEmployees`
  - Accepts a reactive `companyId: Ref<number | null>`
  - `employees: Ref<Employee[]>`, `loading: Ref<boolean>`
  - Watches `companyId` — fetches on change, resets to `[]` when `null`
- [x] `useProjects`
  - Same reactive pattern as `useEmployees`
  - Watches `companyId`, fetches `projectService.getByCompany`
- [x] `useTasks`
  - Same reactive pattern — watches `companyId`, fetches `taskService.getByCompany`
- [x] `useTimeEntries`
  - `entries: Ref<TimeEntry[]>`, `loading: Ref<boolean>`, `error: Ref<string | null>`
  - `fetch(companyId?: number)` — calls `timeEntryService.getAll`
  - `bulkInsert(payload: BulkInsertPayload)` — calls `timeEntryService.bulkInsert`, refreshes `entries` on success

---

### Step 2 — Global shell

Build the page frame: company dropdown and tabbed layout that all other components live inside.

- [x] Install shadcn-vue components: `select`, `tabs`
- [x] `CompanyDropdown.vue`
  - Uses `useCompanies` internally
  - Renders an "All" option plus one option per company
  - Emits `update:modelValue` with `Company | null`
- [x] Update `App.vue`
  - Renders `CompanyDropdown` at the top of the page
  - Renders `Tabs` with two panels: **New Entries** and **History**
  - No prop passing or provide needed — tabs consume `useSelectedCompany` directly

---

### Step 3 — New Entries tab

Build the multi-row entry form and the per-row component with its reactive available-options pattern.

- [x] Install shadcn-vue components: `table`, `button`, `input`
- [x] `NewTimeEntryRow.vue`
  - Props: `modelValue: BulkInsertEntry`, `lockedCompanyId: number | null`
  - Internal `companyId: Ref<number | null>` derived from `lockedCompanyId` or the row's own company selection
  - `availableEmployeeOptions` — result of `useEmployees(companyId)`
  - `availableProjectOptions` — result of `useProjects(companyId)`
  - `availableTaskOptions` — result of `useTasks(companyId)`
  - When company changes: reset `employee_id`, `project_id`, `task_id` and let the composables reload
  - When `lockedCompanyId` is set: company cell renders as read-only text, not a dropdown
  - Emits `update:modelValue` on any field change
- [x] `NewEntriesTab.vue`
  - Calls `useSelectedCompany()` to read `selectedCompany`
  - Maintains `rows: Ref<BulkInsertEntry[]>` — starts with one blank row
  - **Add row** button — appends a blank row pre-filled with `selectedCompany?.id`
  - **Submit** button — calls `useTimeEntries().bulkInsert({ entries: rows })`, clears rows on success
  - Displays API validation errors per row when the server returns 422

---

### Step 4 — History tab

Build the read-only history table that reacts to the global company selection.

- [x] `HistoryTab.vue`
  - Calls `useSelectedCompany()` to read `selectedCompany`
  - Uses `useTimeEntries` — calls `fetch(selectedCompany?.value?.id)` on mount
  - Watches `selectedCompany` — re-fetches whenever it changes
  - Renders a read-only `Table` with columns: Company, Date, Employee, Project, Task, Hours
  - Shows a `Skeleton` placeholder while loading
  - Shows an empty state message when the list is empty

---

## Notes

- `useEmployees`, `useProjects`, `useTasks` all accept a `Ref<number | null>` so that `NewTimeEntryRow` can pass a reactive `companyId` and get automatic re-fetching without any manual wiring.
- `selectedCompany` lives at module scope inside `useSelectedCompany` — imported directly by any component that needs it, no `provide/inject` and no prop drilling.
- `NewTimeEntryRow` derives its effective `companyId` from `lockedCompanyId` (when global company is set) or from its own row's `company_id` field — the composables watch that derived ref so options always stay in sync.
- When `lockedCompanyId` changes (user switches global company mid-session), existing rows are not reset — only newly added rows pick up the new default. This was an explicit UX decision.
- shadcn-vue components are added via CLI (`npx shadcn-vue@latest add <component>`) per step, only when needed.
