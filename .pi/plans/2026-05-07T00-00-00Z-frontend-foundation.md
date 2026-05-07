---
date: 2026-05-07T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, frontend, types, services, vue, typescript]
last_updated: 2026-05-07
---

# Frontend foundation: types and services

Patch the backend TimeEntry response, set up the Vue + TypeScript + shadcn/vue toolchain,
define every API interface in a single types file, and implement the service layer that all
components will consume.

## Goal

The backend will return enriched TimeEntry objects with nested names. The frontend will have
a fully typed service layer that encapsulates every API call, ready to be consumed by
composables and components built in the next plan.

## Target Structure

```
app/
  Http/
    Resources/
      TimeEntryResource.php       ← updated: nested company/employee/project/task

resources/js/
  types/
    api.ts                        ← all API interfaces
  services/
    http.ts                       ← base fetch wrapper (base URL, JSON headers)
    company.service.ts
    employee.service.ts
    project.service.ts
    task.service.ts
    time-entry.service.ts
  app.ts                          ← Vue entry point (replaces app.js)
```

---

## Steps

### Step 1 — Backend: enrich TimeEntry response and add company filter

Update the backend so `GET /time-entries` returns nested names and supports `?company_id=`.

- [x] Update `TimeEntryResource` to nest related resources
  - Replace flat `company_id`, `employee_id`, `project_id`, `task_id` fields with nested `CompanyResource`, `EmployeeResource`, `ProjectResource`, `TaskResource`
- [x] Eager-load relationships in `TimeEntryController@index` to avoid N+1
- [x] Add optional `?company_id=` filter to `TimeEntryController@index`
- [x] Update `TimeEntryControllerTest`
  - Adjust existing JSON structure assertions to match new nested shape
  - Add test: `GET /time-entries?company_id=X` returns only entries for that company
  - Add test: `GET /time-entries` without param still returns all entries

---

### Step 2 — Frontend tooling

Install and configure Vue 3, TypeScript and shadcn/vue on top of the existing Vite + Tailwind setup.

- [x] Install Vue 3 and the Vite Vue plugin
  - `vue`, `@vitejs/plugin-vue`, `typescript`, `vue-tsc`
- [x] Update `vite.config.js` → `vite.config.ts` to add `@vitejs/plugin-vue`
- [x] Add `tsconfig.json` with paths configured for `resources/js`
- [x] Install shadcn/vue dependencies
  - `radix-vue`, `class-variance-authority`, `clsx`, `tailwind-merge`, `lucide-vue-next`
- [x] Run `shadcn-vue` init to scaffold `components.json` and `lib/utils.ts`
- [x] Rename `resources/js/app.js` → `app.ts`; create root `App.vue`; mount the Vue app
- [x] Update `vite.config.ts` entry point to `app.ts`
- [x] Confirm `npm run dev` serves without errors

---

### Step 3 — API types

Define every API interface consumed by the frontend in a single `types/api.ts` file.

- [x] `Company` — `{ id: number; name: string }`
- [x] `Employee` — `{ id: number; name: string }`
- [x] `Project` — `{ id: number; company_id: number; name: string }`
- [x] `Task` — `{ id: number; company_id: number; name: string }`
- [x] `TimeEntry` — nested shape: `{ id, company, employee, project, task, date, hours }`
- [x] `BulkInsertEntry` — flat payload shape for one entry sent to the API
- [x] `BulkInsertPayload` — `{ entries: BulkInsertEntry[] }`
- [x] `ApiCollection<T>` — wrapper for Laravel resource collection responses `{ data: T[] }`

---

### Step 4 — Service layer

Implement one service file per entity, all built on top of a shared `http.ts` base wrapper.

- [x] `services/http.ts` — base fetch wrapper
  - Resolves base URL from `import.meta.env` or a hardcoded `/api` prefix
  - Sets `Content-Type: application/json` and `Accept: application/json`
  - Throws a typed error on non-2xx responses
- [x] `services/company.service.ts`
  - `getCompanies(search?: string): Promise<Company[]>`
- [x] `services/employee.service.ts`
  - `getEmployees(companyId: number): Promise<Employee[]>`
- [x] `services/project.service.ts`
  - `getProjects(companyId: number): Promise<Project[]>`
- [x] `services/task.service.ts`
  - `getTasks(companyId: number): Promise<Task[]>`
- [x] `services/time-entry.service.ts`
  - `getTimeEntries(companyId?: number): Promise<TimeEntry[]>`
  - `bulkInsert(payload: BulkInsertPayload): Promise<TimeEntry[]>`

---

## Notes

- `TimeEntryResource` now requires eager-loading `company`, `employee`, `project`, `task` in the controller — without it the nested resource calls would trigger N+1 queries.
- `BulkInsertEntry` uses flat IDs (matching the API payload), while `TimeEntry` uses nested objects (matching the API response) — they are intentionally different shapes.
- `ApiCollection<T>` wraps every Laravel `Resource::collection()` response; all service methods unwrap `.data` before returning, so consumers always receive plain arrays.
- `http.ts` uses `/api` as a hardcoded prefix since there is no auth and no multi-environment complexity needed at this stage.
- Composables and components are out of scope for this plan; they will be covered in a follow-up plan.
