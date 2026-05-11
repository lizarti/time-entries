# Time Tracker

A full-stack time entry management application built with **Laravel** and **Vue**. Employees can log hours against companies, projects, and tasks. The interface supports bulk entry via a structured form, a searchable history view with inline editing, and natural language entry powered by an AI agent.

This project was built as a technical assessment for a full-stack role.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Domain Model](#domain-model)
- [Architecture](#architecture)
  - [Backend](#backend)
  - [Frontend](#frontend)
- [API Reference](#api-reference)
- [Getting Started](#getting-started)
- [Running Tests](#running-tests)
- [Seeding the Database](#seeding-the-database)
- [AI Feature](#ai-feature)
- [Design System](#design-system)
- [Engineering Considerations](#engineering-considerations)

---

## Tech Stack

**Backend**
|---|---|
| Runtime | PHP 8.3 |
| Framework | Laravel 13 |
| Database | MySQL 8.4 |
| Testing | Pest 4 |
| AI | Laravel AI (`laravel/ai`) |

**Frontend**
|---|---|
| Framework | Vue 3 (Composition API) |
| Language | TypeScript |
| Build tool | Vite 8 |
| UI components | shadcn-vue (Reka UI) |
| Styling | Tailwind CSS v4 |
| HTTP | Native Fetch API |

**Infrastructure**
|---|---|
| Server | PHP built-in (`php artisan serve`) or any standard web server |

---

## Domain Model

```
Company ──< Project          (one company has many projects)
Company ──< Task             (one company has many tasks; tasks are NOT scoped to a project)
Company >──< Employee        (many-to-many via company_employee)
Employee >──< Project        (many-to-many via employee_project — assignment)
TimeEntry >── Company
TimeEntry >── Employee
TimeEntry >── Project
TimeEntry >── Task
```

### Business rules

1. An employee can only work on **one project per date**, but can log **multiple tasks** on the same project and date.
2. The `employee`, `project`, and `task` on a time entry must all belong to the **same company**.
3. The employee must be **assigned** to the time entry's project.

### Validation layers

| Rule | Where |
|---|---|
| Required fields, types, formats | `FormRequest` |
| Employee / Project / Task belong to the same Company | `FormRequest` — custom `BelongsToCompany` rule |
| Employee is assigned to the Project | `FormRequest` — custom `EmployeeAssignedToProject` rule |
| Employee has no different project on that date (DB check) | `BulkInsertTimeEntriesAction` |
| Employee conflict within the same submitted batch | `BulkInsertTimeEntriesAction` |

---

## Architecture

### Backend

The backend follows a **thin controller, single-responsibility action** pattern:

```
app/
├── Actions/
│   └── TimeEntry/
│       ├── BulkInsertTimeEntriesAction.php   # validates conflicts + inserts in a transaction
│       ├── ListTimeEntriesAction.php          # filtering, sorting, pagination
│       ├── SummarizeTimeEntriesAction.php     # aggregations by employee/project/task/date/company
│       └── UpdateTimeEntryAction.php          # conflict check + update
├── Ai/
│   ├── Agents/
│   │   └── TimeTracker.php                   # agent with structured output schema
│   └── Tools/
│       ├── SearchCompany.php
│       ├── SearchEmployee.php
│       ├── SearchProject.php
│       └── SearchTask.php
├── Http/
│   ├── Controllers/                          # thin — extract from Request, delegate to Action
│   ├── Requests/
│   │   ├── BulkInsertTimeEntriesRequest.php
│   │   └── UpdateTimeEntryRequest.php
│   └── Resources/                            # API serialisation (nested names on TimeEntry)
├── Models/
│   └── TimeEntry.php                         # includes scopeWithFilters() query scope
└── Rules/
    ├── BelongsToCompany.php
    └── EmployeeAssignedToProject.php
```

**Key conventions:**
- One Action class, one responsibility.
- FormRequests own all input validation and cross-field rules.
- The shared filter logic lives on the `TimeEntry` model as `scopeWithFilters(array $filters)` — consumed by `ListTimeEntriesAction` and `SummarizeTimeEntriesAction` without duplication.
- `BulkInsertTimeEntriesAction` wraps all inserts in a single DB transaction — any conflict causes a full rollback.

### Frontend

```
resources/js/
├── types/
│   └── api.ts              # all API interfaces in one file
├── services/               # one file per entity, all fetch calls live here
│   ├── http.ts             # base fetch wrapper (headers, error throwing)
│   ├── company.service.ts
│   ├── employee.service.ts
│   ├── project.service.ts
│   ├── task.service.ts
│   └── time-entry.service.ts
├── composables/            # reactive wrappers over services
│   ├── useSelectedCompany.ts   # module-scoped singleton — global company state
│   ├── useCompanies.ts
│   ├── useEmployees.ts
│   ├── useProjects.ts
│   ├── useTasks.ts
│   ├── useTimeEntries.ts
│   └── useAiParser.ts
└── components/
    ├── App.vue
    ├── AppCombobox.vue         # reusable searchable combobox wrapper
    ├── CompanyDropdown.vue     # global company selector
    ├── AiEntryInput.vue        # natural language entry panel
    ├── NewEntriesTab.vue
    ├── NewTimeEntryRow.vue     # per-row form with availableXOptions pattern
    ├── HistoryTab.vue
    ├── HistoryFilterBar.vue
    ├── HistoryEntryRow.vue     # inline edit row
    └── summary/
        ├── HistorySummary.vue
        └── SummaryCard.vue
```

**Key conventions:**
- No fetch calls directly in components — all HTTP goes through services.
- Composables wrap services in reactive state; components only call composables.
- Global company state lives in a **module-scoped `ref`** inside `useSelectedCompany` — imported directly by any component, no `provide/inject`.
- All API interfaces are centralised in `types/api.ts`. `BulkInsertEntry` (flat IDs, sent to the API) and `TimeEntry` (nested objects with names, received from the API) are intentionally different shapes.

---

## API Reference

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/companies` | List companies. Supports `?search=` |
| `GET` | `/api/employees` | List employees. Supports `?company_id=` |
| `GET` | `/api/projects` | List projects. Supports `?company_id=` |
| `GET` | `/api/tasks` | List tasks. Supports `?company_id=` |
| `GET` | `/api/time-entries` | List time entries. Supports filters, sort, pagination |
| `POST` | `/api/time-entries/bulk` | Bulk insert time entries (all-or-nothing) |
| `PUT` | `/api/time-entries/{id}` | Update a time entry |
| `GET` | `/api/time-entries/summary` | Aggregated totals (by employee, project, task, date, company) |
| `POST` | `/api/time-entries/parse` | Parse a natural language message into a time entry via AI |

### Filtering (`GET /api/time-entries`)

| Param | Type | Description |
|---|---|---|
| `company_id` | integer | Filter by company |
| `employee_id` | integer | Filter by employee |
| `project_id` | integer | Filter by project |
| `task_id` | integer | Filter by task |
| `date_from` | date | Entries on or after this date |
| `date_to` | date | Entries on or before this date |
| `search` | string | Matches employee, project, task, or company name |
| `sort_by` | string | `date` (default), `hours`, `employee`, `project`, `task`, `company` |
| `sort_dir` | string | `asc` or `desc` (default) |
| `per_page` | integer | Results per page, max 100 (default 25) |
| `page` | integer | Page number |

---

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- MySQL 8.4+
- Node.js 20+

### Setup

```bash
# 1. Clone the repository
git clone <repo-url> && cd time-tracker

# 2. Install PHP dependencies
composer install

# 3. Copy the environment file and configure your database credentials
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Run migrations and seed the database
php artisan migrate --seed

# 6. Install frontend dependencies
npm install

# 7. Start the development server
php artisan serve

# 8. In a separate terminal, start the Vite dev server
npm run dev
```

The application will be available at **http://localhost:8000**.

### Environment variables

The only non-standard variable required for the AI feature:

```env
OPENAI_API_KEY=your-key-here
```

---

## Running Tests

```bash
php vendor/bin/pest
```

The test suite uses a dedicated `testing` MySQL database (provisioned automatically by Sail) and `RefreshDatabase` to isolate each test. **65+ tests** cover:

- Model relationships
- All API endpoints (listing, filtering, sorting, pagination)
- All validation rules (FormRequest + custom rules)
- Business rule enforcement (conflict detection, all-or-nothing rollback)
- Summary aggregations

---

## Seeding the Database

The seeder creates a realistic connected dataset:

| Entity | Count |
|---|---|
| Companies | 8 (Adjective + Fruit names: *Golden Mango*, *Bright Kiwi*, etc.) |
| Employees | ~29 (Faker-generated names, 3–4 per company) |
| Projects | ~26 (2–4 per company, drawn from a pool of realistic names) |
| Tasks | ~37 (4–6 per company: *Development*, *Design*, *Testing*, etc.) |

```bash
php artisan migrate:fresh --seed
```

---

## AI Feature

The **Add entry with AI** panel in the New Entries tab accepts free-text descriptions:

> *"John worked on Website Redesign for Golden Mango yesterday doing Development for 4 hours"*

**Flow:**

1. The frontend sends `POST /api/time-entries/parse` with `{ message }`.
2. The `TimeTracker` agent receives the message and uses four tools — `SearchCompany`, `SearchEmployee`, `SearchProject`, `SearchTask` — to resolve names against the real database.
3. The agent returns a structured JSON object with resolved IDs and names.
4. The frontend maps the response to a `BulkInsertEntry` and pre-fills a new row in the table for the user to review and submit.

The AI-parsed row goes through the same frontend validation and bulk-submit flow as manually entered rows — no special-casing at submission time.

---

## Design System

The UI follows a custom design system defined in `DESIGN.md`:

- **Palette:** White surfaces (`#FFFFFF`) on a light gray background (`#F5F6F8`). Single accent — coral orange (`#FF6B35`) — used exclusively for primary CTAs.
- **Typography:** Inter, single font family throughout.
- **Buttons:** Pill-shaped (`rounded-full`). Orange primary, white bordered secondary.
- **Tables:** Uppercase tracked headers, `1px` subtle row separators, `#FAFAFA` hover only — no zebra striping, no row shadows.
- **Philosophy:** Clinical utility — precision of a spreadsheet, polish of a modern SaaS product. Every element serves a function.

---

## Engineering Considerations

This project was built as a focused technical exercise. Several deliberate trade-offs were made that would be revisited in a production context.

### What was prioritised

- **Layered validation** — validation responsibility is explicitly assigned: input shape in FormRequests, domain ownership in custom Rule classes, business rule enforcement in Actions. This makes each layer independently testable.
- **Thin controllers** — controllers extract from `Request` and delegate to Actions. No query logic, no business rules.
- **Single-responsibility Actions** — one class, one use case. Shared query logic lives on the model as a scope (`scopeWithFilters`), not duplicated across Actions or hidden in a base class.
- **Typed frontend layer** — all API shapes are declared in `types/api.ts`. Services own all HTTP calls; composables own reactive state; components consume composables only.
- **Module-scoped global state** — `useSelectedCompany` holds a `ref` at module scope. Any component imports it directly — no `provide/inject`, no Pinia, no prop drilling.

### Task scoping — why Task belongs to Company, not Project

Tasks are intentionally scoped to a Company rather than a Project. This models a real-world pattern where a company maintains a reusable catalogue of work categories (*Development*, *Code Review*, *Testing*) that can appear across any project. The association between a task and a project exists only through the TimeEntry itself, giving the model more flexibility without schema changes when projects evolve.

### Route design — scoped vs. root-level endpoints

The project started with company-scoped routes (`/companies/{company}/employees`) but evolved to root-level routes with an optional `?company_id=` query parameter (`/employees?company_id=1`). The scoped routes were removed once the frontend settled on the query-param pattern. The root-level approach was chosen because several parts of the UI need to load entities without a company context (e.g. the AI parser resolves entities independently), making the optional filter more flexible than a required path segment.

### Filter comboboxes load all entities client-side

The filter comboboxes in the History tab (Employee, Project, Task) load all entities for the selected company upfront. The text filtering inside each combobox is client-side only. In a production application with large datasets, this would be replaced with server-side search — debounced API calls that pass the search term as a query parameter and return a subset of results. The current approach is acceptable at the scale of this exercise and keeps the implementation simple.

### No caching layer

Neither the frontend nor the backend implements caching. In a real-world scenario, two layers would be appropriate:

- **Client-side:** a query cache such as [TanStack Query](https://tanstack.com/query) would deduplicate concurrent fetches, keep the UI optimistic during refetches, and provide stale-while-revalidate behaviour without manual `loading` flags in every composable.
- **API-side:** Laravel's cache system (Redis-backed in production) would cache expensive or frequently repeated queries — particularly the summary aggregations, which perform five GROUP BY joins on every request.

### No authentication

Authentication was explicitly excluded from scope. In a production system, all endpoints would be protected and scoped to the authenticated user's company memberships.

### Bulk insert is all-or-nothing

The bulk insert endpoint wraps all inserts in a single database transaction. Any conflict — whether detected before the transaction (intra-batch project conflict) or during it (DB-level project conflict) — causes a full rollback. This is the safest default for a time entry system where partial saves would be confusing, though a partial-success mode with per-entry error reporting would be a valid alternative for high-volume workflows.

### AI agent limitations

The `TimeTracker` agent resolves entity names using exact-match database lookups. It does not handle fuzzy matching, abbreviations, or partial names. In production, the search tools would benefit from full-text search or a similarity function to handle common input variations gracefully.
