---
name: plan
description: Creates a structured plan file at .pi/plans/<name>.md to track multi-step work before executing it. Use when the user asks to write a plan, document next steps, track a refactor, or capture a sequence of tasks. Also use to update an existing plan (mark steps done, add steps, revise scope).
---

# Plan

Creates and maintains structured plan files at `<repo-root>/.pi/plans/<timestamp_in_iso_format>-<name>.md`.
Example: `.pi/plans/2024-06-01T12-00-00Z-dashboard-redesign.md`.

## When to use

- User asks to "write a plan", "create a plan", or "track this as a plan"
- About to start a large refactor, migration, or multi-step feature
- User wants to discuss scope first and execute later
- Updating an existing plan (checking off steps, revising scope, adding steps)

## Process

### Creating a new plan

1. **Derive the plan name** from context or ask the user.
   - Use lowercase words separated by hyphens: `admin-feature-refactor`, `auth-migration`, `dashboard-redesign`
   - Keep it short and descriptive (3–5 words max)

2. **Understand the full scope** before writing. If the conversation already established
   the scope (steps, decisions, structure), use it. Do not ask for information already known.

3. **Identify genuine gaps before writing.**
   If the scope leaves open questions that would meaningfully change the plan's structure or
   steps, ask them *before* writing — not after.

   Rules for asking:
   - Ask only questions whose answer would change what goes in the plan
   - Group them in a single message, never across multiple turns
   - Cap at 3 questions; if there are more unknowns than that, the scope needs scoping
     first — say so
   - If the user's intent is clear enough to write a reasonable first draft, write it and
     note assumptions in the **Notes** section instead of blocking on questions

4. **Create the directory** if it does not exist:
   ```bash
   # run from repo root
   mkdir -p .pi/plans
   ```

5. **Write the plan file** at `.pi/plans/<name>.md` following the format in
   [template.md](template.md).

6. **Confirm** to the user where the file was written and summarise the steps at a glance.

### Updating an existing plan

- To mark a step done: change `- [ ]` to `- [x]`
- To mark a step in-progress: change `- [ ]` to `- [~]`
- Add new steps at the end or insert them where they logically belong
- Update the Notes section if new decisions were made

## Format rules

Reference [template.md](template.md) for the canonical structure. Key rules:

- **Title**: plain sentence-case title, no prefix like "Plan:" or "Refactor:"
- **Goal**: 1–3 sentences, declarative ("X will Y"), not imperative ("Do X")
- **Target Structure**: include only when the plan involves a layout/architecture change; omit otherwise
- **Steps**: numbered, each with a short descriptive name. Every step must be independently
  executable and leave the codebase in a working state
- **Tasks**: use `- [ ]` checkboxes. Indent sub-details under the relevant task with plain `-`
- **Separator**: use `---` between steps to aid scanning
- **Notes**: capture decisions already made (especially trade-offs), not future todos.
  Notes are for things resolved in discussion — they should not duplicate the step tasks.
- **Granularity**: a step should be completable in one focused session. If a step feels too
  large, split it. If two steps always get done together, merge them. When a plan grows
  beyond ~8 steps, consider whether it should be split into two separate plans.

## File location

Always `.pi/plans/<name>.md` relative to the project root (current working directory).
Never create plan files elsewhere unless the user explicitly requests it.