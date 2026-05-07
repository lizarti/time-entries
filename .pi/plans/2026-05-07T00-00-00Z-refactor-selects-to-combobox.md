---
date: 2026-05-07T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, refactor, combobox, vue, components]
last_updated: 2026-05-07
---

# Refactor selects to combobox

Replace all `Select` dropdowns with searchable `Combobox` dropdowns across
`CompanyDropdown.vue` and `NewTimeEntryRow.vue`.

## Goal

Every dropdown in the application will support live search filtering. A single reusable
`AppCombobox.vue` wrapper will encapsulate the full Combobox composition so the consumer
components stay clean.

## Target Structure

```
resources/js/
  components/
    ui/
      combobox/              ← installed via shadcn-vue CLI
    AppCombobox.vue          ← new reusable wrapper
    CompanyDropdown.vue      ← refactored: Select → AppCombobox
    NewTimeEntryRow.vue      ← refactored: 4× Select → AppCombobox
```

---

## Steps

### Step 1 — Install combobox component

Add the shadcn-vue combobox (and its `input-group` registry dependency) to the project.

- [x] Run `npx shadcn-vue@latest add @shadcn/combobox`
- [x] Verify all combobox sub-components are present under `resources/js/components/ui/combobox/`

---

### Step 2 — AppCombobox wrapper

Create a single reusable wrapper that hides the full Combobox composition behind a clean prop interface.

- [x] Create `resources/js/components/AppCombobox.vue`
  - Props: `modelValue: string`, `options: { value: string; label: string }[]`,
    `placeholder?: string`, `searchPlaceholder?: string`, `disabled?: boolean`
  - Emits: `update:modelValue`
  - Derives `selectedLabel` from `modelValue` + `options` for display in the trigger
  - Composes: `Combobox` → `ComboboxAnchor` → `ComboboxTrigger` → `ComboboxList`
    → `ComboboxInput` → `ComboboxViewport` → `ComboboxItem` + `ComboboxItemIndicator`
    + `ComboboxEmpty`

---

### Step 3 — Refactor CompanyDropdown

Swap the `Select` in `CompanyDropdown.vue` for `AppCombobox`.

- [x] Replace `Select` + `SelectTrigger` + `SelectContent` + `SelectItem` + `SelectValue`
  with a single `<AppCombobox>`
- [x] Map options as `[{ value: 'all', label: 'All companies' }, ...companies]`
- [x] Remove all `Select*` imports

---

### Step 4 — Refactor NewTimeEntryRow

Swap all four `Select` instances in `NewTimeEntryRow.vue` for `AppCombobox`.

- [x] Replace Company `Select` with `AppCombobox` — options from `allCompanies`
- [x] Replace Employee `Select` with `AppCombobox` — options from `availableEmployeeOptions`
- [x] Replace Project `Select` with `AppCombobox` — options from `availableProjectOptions`
- [x] Replace Task `Select` with `AppCombobox` — options from `availableTaskOptions`
- [x] Remove all `Select*` imports; add `AppCombobox`
- [x] Confirm `npm run build` passes with zero type errors

---

## Notes

- A reusable `AppCombobox.vue` wrapper is introduced to avoid repeating the 10-sub-component
  Combobox composition in every dropdown — consumer components only deal with `modelValue`
  + `options`.
- All option values are typed as `string` inside `AppCombobox`; call sites that work with
  numeric IDs convert with `String(id)` on the way in and `Number(value)` on the way out,
  consistent with the existing Select pattern.
- The `Select` component is kept installed — it may be useful elsewhere. Only the imports
  in `CompanyDropdown` and `NewTimeEntryRow` are removed.
