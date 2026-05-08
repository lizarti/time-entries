// ─── Primitives ───────────────────────────────────────────────────────────────

export interface Company {
    id: number;
    name: string;
}

export interface Employee {
    id: number;
    name: string;
}

export interface Project {
    id: number;
    company_id: number;
    name: string;
}

export interface Task {
    id: number;
    company_id: number;
    name: string;
}

// ─── Time Entry ───────────────────────────────────────────────────────────────

/** Shape returned by GET /time-entries — nested objects with names. */
export interface TimeEntry {
    id: number;
    company: Company;
    employee: Employee;
    project: Project;
    task: Task;
    date: string; // "YYYY-MM-DD"
    hours: number;
}

/** One row in the bulk-insert payload — flat IDs sent to POST /time-entries/bulk. */
export interface BulkInsertEntry {
    company_id: number;
    employee_id: number;
    project_id: number;
    task_id: number;
    date: string; // "YYYY-MM-DD"
    hours: number;
}

/** Flat-ID payload sent to PUT /time-entries/:id (company is not updatable). */
export interface UpdateTimeEntryPayload {
    employee_id: number;
    project_id: number;
    task_id: number;
    date: string; // "YYYY-MM-DD"
    hours: number;
}

/** Full payload sent to POST /time-entries/bulk. */
export interface BulkInsertPayload {
    entries: BulkInsertEntry[];
}

// ─── API envelope ─────────────────────────────────────────────────────────────

/** Laravel Resource::collection() always wraps arrays in { data: T[] }. */
export interface ApiCollection<T> {
    data: T[];
}

/** Laravel JsonResource wraps a single resource in { data: T }. */
export interface ApiItem<T> {
    data: T;
}
