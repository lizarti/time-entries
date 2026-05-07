---
date: 2026-05-07T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, migrations, models, relationships]
last_updated: 2026-05-07
---

# Migrations, models and relationships

Define the full database schema and Eloquent layer for the time-tracker domain: Companies,
Employees, Projects, Tasks and TimeEntries, including all pivot tables and relationships.

## Goal

All migrations run cleanly, every Model is created with correct fillable fields, casts and
Eloquent relationship methods so the rest of the application can rely on them without
touching the schema again.

## Target Structure

```
database/
  migrations/
    xxxx_xx_xx_000001_create_companies_table.php
    xxxx_xx_xx_000002_create_employees_table.php
    xxxx_xx_xx_000003_create_company_employee_table.php       # pivot
    xxxx_xx_xx_000004_create_projects_table.php
    xxxx_xx_xx_000005_create_employee_project_table.php       # pivot
    xxxx_xx_xx_000006_create_tasks_table.php
    xxxx_xx_xx_000007_create_time_entries_table.php

app/
  Models/
    Company.php
    Employee.php
    Project.php
    Task.php
    TimeEntry.php
```

---

## Steps

### Step 1 — Core entity migrations

Create the migrations for every standalone table (no pivots yet).

- [x] `companies` — `id`, `name`, `timestamps`
- [x] `employees` — `id`, `name`, `timestamps`
- [x] `projects` — `id`, `company_id` (FK → companies), `name`, `timestamps`
- [x] `tasks` — `id`, `company_id` (FK → companies), `name`, `timestamps`
- [x] `time_entries` — `id`, `company_id`, `employee_id`, `project_id`, `task_id` (all FK), `date` (date), `hours` (decimal 5,2), `timestamps`

---

### Step 2 — Pivot migrations

Create the migrations for many-to-many join tables.

- [x] `company_employee` — `company_id`, `employee_id` (composite PK, no timestamps)
- [x] `employee_project` — `employee_id`, `project_id` (composite PK, no timestamps)

---

### Step 3 — Models

Create each Eloquent model with `$fillable` and any necessary `$casts`.

- [x] `Company` — fillable: `name`
- [x] `Employee` — fillable: `name`
- [x] `Project` — fillable: `company_id`, `name`
- [x] `Task` — fillable: `company_id`, `name`
- [x] `TimeEntry` — fillable: `company_id`, `employee_id`, `project_id`, `task_id`, `date`, `hours`; cast `date` → `date`, `hours` → `decimal:2`

---

### Step 4 — Relationships

Add all Eloquent relationship methods to each model.

- [x] `Company` → `hasMany(Project)`, `hasMany(Task)`, `belongsToMany(Employee)`, `hasMany(TimeEntry)`
- [x] `Employee` → `belongsToMany(Company)`, `belongsToMany(Project)`, `hasMany(TimeEntry)`
- [x] `Project` → `belongsTo(Company)`, `belongsToMany(Employee)`, `hasMany(TimeEntry)`
- [x] `Task` → `belongsTo(Company)`, `hasMany(TimeEntry)`
- [x] `TimeEntry` → `belongsTo(Company)`, `belongsTo(Employee)`, `belongsTo(Project)`, `belongsTo(Task)`

---

### Step 5 — Tests

Write Pest tests to assert relationships are wired correctly.

- [x] Factory for each model (`Company`, `Employee`, `Project`, `Task`, `TimeEntry`)
- [x] Test `Company` ↔ `Employee` many-to-many
- [x] Test `Company` ↔ `Project` / `Task` one-to-many
- [x] Test `Employee` ↔ `Project` many-to-many
- [x] Test `TimeEntry` belongs to all four entities
- [x] Run `php artisan migrate:fresh` and confirm all migrations execute without error

---

## Notes

- Pivot tables use composite primary keys (`company_id` + `employee_id`, `employee_id` + `project_id`) and no timestamps, keeping them lean.
- `Task` is intentionally scoped to `Company` only — it is a reusable label, not tied to a specific Project.
- `TimeEntry.hours` is `decimal(5,2)` — supports up to 999.99 hours per entry, enough for any realistic input.
- `company_id` is stored directly on `TimeEntry` (not derived) so membership validation in FormRequests has a single, explicit anchor.
- No authentication is in scope; no `user_id` or ownership columns are needed.
