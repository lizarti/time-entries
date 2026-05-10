---
date: 2026-05-08T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, history, summary, ux]
last_updated: 2026-05-08
---

# History summary UX rearrangement

The Summary panel currently sits between the filter bar and the table, and shows five
widgets. This plan moves it above the filters and trims it to the three most useful
dimensions.

## Goal

The History tab will show the Summary panel at the very top — above the filter bar —
so totals are immediately visible before the user applies any filters. Only the
"By Employee", "By Project", and "By Company" cards will be shown; "By Task" and
"By Date" are removed.

---

## Steps

### Step 1 — Trim HistorySummary to three cards

Remove the `byTask` and `byDate` props and update the internal grid.

- [x] Remove `byTask` and `byDate` from the `defineProps` block in `HistorySummary.vue`
- [x] Remove the corresponding entries from the `cards` computed array
- [x] Change the grid from `grid-cols-2 lg:grid-cols-5` to `grid-cols-1 sm:grid-cols-3`

---

### Step 2 — Reorder HistoryTab layout

Move the summary above the filter bar and drop the two removed props.

- [x] In `HistoryTab.vue`, move `<HistorySummary>` above `<HistoryFilterBar>`
- [x] Remove the `:by-task` and `:by-date` bindings from the `<HistorySummary>` element

---

## Notes

- No backend changes needed — `GET /time-entries/summary` still returns all five
  dimensions; we simply stop consuming two of them on the frontend.
- The visibility guard (`v-if="loading || (meta?.total ?? 0) > 0"`) stays unchanged.
