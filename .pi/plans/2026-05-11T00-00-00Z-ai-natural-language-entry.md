---
date: 2026-05-11T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, ai, natural-language, vue, new-entries]
last_updated: 2026-05-11
---

# AI natural language entry input

Add a natural language input panel to the New Entries tab that lets the user describe a
time entry in plain text, sends it to `POST /time-entries/parse`, and pre-fills a
reviewed row in the table for final submission.

## Goal

The user will be able to type a sentence like "John worked on Website Redesign for Golden
Mango yesterday doing Development for 4 hours", click Parse, and see a pre-filled,
editable row appear in the New Entries table — ready to submit alongside any other rows.

## Target Structure

```
resources/js/
  types/
    api.ts                      ← new: ParsedTimeEntry interface
  services/
    time-entry.service.ts       ← new: parse(message) method
  composables/
    useAiParser.ts              ← new
  components/
    AiEntryInput.vue            ← new
    NewEntriesTab.vue           ← updated: hosts AiEntryInput above the table
```

---

## Steps

### Step 1 — Type and service method

Extend the type system and service layer to cover the AI parse endpoint.

- [x] Add `ParsedTimeEntry` interface to `types/api.ts`
  - Shape mirrors the agent's JSON output: `company_id`, `company_name`, `employee_id`,
    `employee_name`, `project_id`, `project_name`, `task_id`, `task_name`, `date`, `hours`
- [x] Add `parse(message: string): Promise<ParsedTimeEntry>` to `time-entry.service.ts`
  - `POST /time-entries/parse` with `{ message }`
  - Returns `http.post<ParsedTimeEntry>(...)` directly — the controller responds without
    Laravel's `{ data: ... }` envelope, so no `.data` unwrapping is needed

---

### Step 2 — Composable

Wrap the parse call in a composable that manages loading and error state.

- [x] Create `composables/useAiParser.ts`
  - `loading: Ref<boolean>`
  - `error: Ref<string | null>`
  - `parse(message: string): Promise<BulkInsertEntry | null>`
    - Calls `timeEntryService.parse(message)`
    - Maps `ParsedTimeEntry` → `BulkInsertEntry` (drops the `*_name` fields)
    - On success: clears `error`, returns the mapped entry
    - On failure: sets `error` to the response message, returns `null`

---

### Step 3 — AiEntryInput component

Build the self-contained input panel with its own loading and error states.

- [x] Create `components/AiEntryInput.vue`
  - Textarea with a descriptive placeholder
    - e.g. *"John worked on Website Redesign for Golden Mango yesterday doing Development
      for 4 hours"*
  - **Parse** button — disabled while loading or while textarea is empty
  - Spinner on the button while `loading` is true
  - Inline error message below the textarea when `error` is set
  - On success: clears the textarea and emits `entry-parsed` with the `BulkInsertEntry`
  - Uses `useAiParser` internally — no fetch calls in the template

---

### Step 4 — Wire into NewEntriesTab

Integrate `AiEntryInput` into the existing New Entries tab.

- [x] Add `AiEntryInput` above the table in `NewEntriesTab.vue`
  - Visually separated from the manual table (e.g. a labelled section or a subtle divider)
- [x] Handle the `entry-parsed` event: push the received `BulkInsertEntry` into `rows`
  - If `lockedCompanyId` is set, override `company_id` on the parsed entry with the
    locked value so it stays consistent with the global selector
  - The row appears pre-filled and editable before the user submits

---

## Notes

- `POST /time-entries/parse` returns the parsed object **without Laravel's `{ data: ... }` envelope** — the controller calls `response()->json($jsonResponse)` directly, not via a `JsonResource`. The service method therefore returns `http.post<ParsedTimeEntry>(...)` directly, consistent with how `getAll` works (which also returns the full response body without unwrapping a `data` key).
- The `*_name` fields in `ParsedTimeEntry` (`company_name`, `employee_name`, etc.) are
  not needed by `BulkInsertEntry` and are dropped in the composable mapping step. They
  could be used in the future to display a human-readable preview before committing.
- The AI-parsed row lands in the same `rows` array as manually added rows — it goes
  through the same frontend validation and bulk-submit flow, so no special-casing is needed
  at submission time.
- If the global company is locked, the `company_id` on the parsed entry is overridden with
  the locked value on insertion. This keeps the UX consistent: the global company dropdown
  is always the source of truth for company context.
- Parsing can be slow (AI round-trip). The loading state on the button is essential UX —
  the user should not be able to double-submit or clear the input mid-flight.
