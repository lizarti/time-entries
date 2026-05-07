---
date: 2026-05-07T00:00:00Z
branch: main
repository: time-tracker
status: done
tags: [plan, seeders, database]
last_updated: 2026-05-07
---

# Database seeders

Populate the database with realistic seed data for Companies, Employees, Projects and Tasks,
including all pivot relationships, so the application is immediately usable after a fresh
migrate.

## Goal

Running `php artisan db:seed` will produce a fully connected dataset: 8 companies with
Adjective + Fruit names, ~25 employees distributed across companies, 2–4 projects per
company with employees assigned to them, and 4–6 reusable tasks per company.

---

## Steps

### Step 1 — Company seeder

Create 8 companies using hardcoded Adjective + Fruit names.

- [x] Create `database/seeders/CompanySeeder.php`
  - Hardcoded list: Golden Mango, Bright Kiwi, Sharp Lemon, Bold Papaya, Crisp Apple,
    Swift Peach, Vivid Lime, Iron Grape

---

### Step 2 — Employee seeder

Create ~25 employees using realistic Faker names and attach each to one company.

- [x] Create `database/seeders/EmployeeSeeder.php`
  - Use `fake()->name()` for realistic names
  - Distribute employees across companies (roughly 3–4 per company)
  - Attach each employee to their company via `company->employees()->attach()`

---

### Step 3 — Project seeder

Create 2–4 projects per company using realistic project names, then assign the company's
employees to projects.

- [x] Create `database/seeders/ProjectSeeder.php`
  - Hardcoded pool of project names: Website Redesign, Mobile Application, API Integration,
    Customer Portal, ERP Implementation, Cloud Migration, E-Commerce Platform, Data Pipeline,
    Internal Dashboard, Brand Refresh
  - Pick 2–4 names at random per company (no repeats within a company)
  - For each project, attach a random subset of that company's employees (at least one)

---

### Step 4 — Task seeder

Create 4–6 company-scoped tasks per company using realistic task label names.

- [x] Create `database/seeders/TaskSeeder.php`
  - Hardcoded pool: Development, Design, Testing, Code Review, Documentation, Deployment,
    Bug Fix, Planning, QA, Technical Writing
  - Pick 4–6 names at random per company (no repeats within a company)

---

### Step 5 — DatabaseSeeder orchestration

Wire all seeders in dependency order and verify with a fresh seed run.

- [x] Update `database/seeders/DatabaseSeeder.php` to call seeders in order:
  `CompanySeeder → EmployeeSeeder → ProjectSeeder → TaskSeeder`
- [x] Run `php artisan migrate:fresh --seed` and confirm all tables are populated

---

## Notes

- For seed data, each employee belongs to exactly one company — the M2M structure supports
  shared employees but there is no need to exercise that in seeds.
- Project names are drawn from a shared pool and shuffled per company to avoid repetition
  within a company while keeping variety across companies.
- Tasks are company-scoped by design (not tied to a specific project); the seed reflects
  this by creating them directly on the company with no project reference.
- Seeders use model relationships (`->attach()`, `->create()`) rather than raw DB inserts
  to stay consistent with the Eloquent layer and respect pivot table conventions.
