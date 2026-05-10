---
date: 2026-05-08T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, history, summary, time-entry]
last_updated: 2026-05-08
---

# History page summary section

A Summary section will appear above the entries table on the History tab, showing total
hours broken down by employee, project, task, date, and company.

## Goal

The top of the History tab will display a collapsible Summary panel with five grouping
views (employee, project, task, date, company). Each view renders a small totals table
fed by the `GET /time-entries/summary` endpoint (added in the history-table-improvements
plan). Summary totals always reflect the active filters and the full dataset, not just the
current page.

---

## Steps

### Step 1 — Consume the summary data from `useTimeEntries`

The `useTimeEntries` composable (updated in the history-table-improvements plan) already
fetches `summary` in parallel with entries. This step wires that data into `HistoryTab`.

- [x] Destructure `summary` and `loading` from `useTimeEntries()` in `HistoryTab.vue`
- [x] Pass `summary.by_employee`, `summary.by_project`, `summary.by_task`,
  `summary.by_date`, `summary.by_company` as props to `<HistorySummary>`
- [x] The `SummaryRow` and `SummaryData` types are defined in `api.ts` as part of
  the improvements plan — no new types needed here

---

### Step 2 — Build the HistorySummary component

Create a self-contained component that accepts the five totals arrays and renders them.

- [x] Create `resources/js/components/HistorySummary.vue`
- [x] Props:
  - `byEmployee: SummaryRow[]`
  - `byProject: SummaryRow[]`
  - `byTask: SummaryRow[]`
  - `byDate: SummaryRow[]`
  - `byCompany: SummaryRow[]`
  - where `SummaryRow = { label: string; hours: number }`
- [x] `SummaryRow` is already defined in `api.ts` by the improvements plan; import it
  directly
- [x] Layout: a row of five named cards (one per dimension), each showing:
  - Card title (e.g. "By Employee")
  - A compact list of `label — X hrs` rows
  - A small "Total" footer row summing all hours in that card
- [x] Use existing shadcn/Tailwind primitives already in the project (no new components
  needed)
- [x] Show a single skeleton placeholder per card while `loading` is true

---

### Step 3 — Wire HistorySummary into HistoryTab

Integrate the new component above the entries table.

- [x] Import and register `HistorySummary` in `HistoryTab.vue`
- [x] Place `<HistorySummary>` above `<Table>`, passing the five computed arrays and
  the `loading` flag
- [x] Hide the summary section entirely when `entries.length === 0` and not loading
  (empty state should just show the table's empty message)

---

## Notes

- Aggregation is server-side (DB-level GROUP BY in `GET /time-entries/summary`); this
  plan depends on the history-table-improvements plan being completed first.
- Frontend aggregation was rejected because pagination means `entries` only holds the
  current page — totals would be wrong. The backend endpoint always sees the full dataset.
- Sorting within each card is by hours descending; `by_date` is sorted chronologically
  ascending — both orderings are applied by the backend.
- `byCompany` will usually be a single row when a specific company is selected, but
  shows multiple when "All companies" is active.
- The summary automatically reflects the active filters because `useTimeEntries` forwards
  the same `TimeEntryFilters` to both `getAll()` and `getSummary()` in parallel.
