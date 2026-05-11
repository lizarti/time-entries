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
    _id: string; // temporary ID used on frontend for tracking rows before they get real IDs from backend
    company_id: number;
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

/** Payload sent to PUT /time-entries/:id (company not updatable). */
export interface UpdateTimeEntryPayload {
    employee_id: number;
    project_id:  number;
    task_id:     number;
    date:        string;
    hours:       number;
}

// ─── Filters / sort / pagination ──────────────────────────────────────────────

export interface TimeEntryFilters {
    company_id?: number;
    employee_id?: number;
    project_id?: number;
    task_id?: number;
    date_from?: string;
    date_to?: string;
    search?: string;
}

export interface TimeEntrySortParams {
    sort_by?: string;
    sort_dir?: 'asc' | 'desc';
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

/** Combined params passed to getAll(). */
export type TimeEntryListParams = TimeEntryFilters &
    TimeEntrySortParams & {
        page?: number;
        per_page?: number;
    };

// ─── Summary ──────────────────────────────────────────────────────────────────

export interface SummaryRow {
    label: string;
    hours: number;
}

export interface SummaryData {
    by_employee: SummaryRow[];
    by_project: SummaryRow[];
    by_task: SummaryRow[];
    by_date: SummaryRow[];
    by_company: SummaryRow[];
}

// ─── AI parsing ─────────────────────────────────────────────────────────────

/** Shape returned by POST /time-entries/parse — flat object with IDs and names. */
export interface ParsedTimeEntry {
    company_id:    number;
    company_name:  string;
    employee_id:   number;
    employee_name: string;
    project_id:    number;
    project_name:  string;
    task_id:       number;
    task_name:     string;
    date:          string; // "YYYY-MM-DD"
    hours:         number;
}

// ─── API envelopes ────────────────────────────────────────────────────────────

/** Laravel Resource::collection() always wraps arrays in { data: T[] }. */
export interface ApiCollection<T> {
    data: T[];
}

/** Laravel's paginated resource collection adds a meta key. */
export interface ApiPage<T> {
    data: T[];
    meta: PaginationMeta;
}

/** Laravel JsonResource wraps a single resource as { data: T }. */
export interface ApiItem<T> {
    data: T;
}
