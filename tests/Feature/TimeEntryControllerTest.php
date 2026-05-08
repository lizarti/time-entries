<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function setup(): array
{
    $company  = Company::factory()->create();
    $employee = Employee::factory()->create();
    $company->employees()->attach($employee);
    $project = Project::factory()->for($company)->create();
    $employee->projects()->attach($project);
    $task = Task::factory()->for($company)->create();

    return compact('company', 'employee', 'project', 'task');
}

function entry(array $ctx, string $date = '2026-05-07', float $hours = 8.0): array
{
    return [
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => $date,
        'hours'       => $hours,
    ];
}

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns all time entries', function () {
    TimeEntry::factory()->count(3)->create();

    $this->getJson('/api/time-entries')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [[
            'id',
            'company'  => ['id', 'name'],
            'employee' => ['id', 'name'],
            'project'  => ['id', 'company_id', 'name'],
            'task'     => ['id', 'company_id', 'name'],
            'date',
            'hours',
        ]]]);
});

it('filters time entries by company_id', function () {
    $ctx   = setup();
    $other = Company::factory()->create();

    TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
    ]);
    TimeEntry::factory()->create(); // belongs to a different company

    $this->getJson("/api/time-entries?company_id={$ctx['company']->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.company.id', $ctx['company']->id);
});

it('returns all time entries when no company_id filter is given', function () {
    TimeEntry::factory()->count(3)->create();

    $this->getJson('/api/time-entries')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

// ─── Bulk Insert: happy path ───────────────────────────────────────────────────

it('creates multiple time entries and returns 201', function () {
    $ctx = setup();

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [
            entry($ctx, '2026-05-07', 4.0),
            entry($ctx, '2026-05-08', 6.5),
        ],
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [[
            'id',
            'company'  => ['id', 'name'],
            'employee' => ['id', 'name'],
            'project'  => ['id', 'company_id', 'name'],
            'task'     => ['id', 'company_id', 'name'],
            'date',
            'hours',
        ]]]);

    expect(TimeEntry::count())->toBe(2);
});

// ─── Validation failures ──────────────────────────────────────────────────────

it('fails with 422 when entries is missing', function () {
    $this->postJson('/api/time-entries/bulk', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries']);
});

it('fails with 422 when entries is an empty array', function () {
    $this->postJson('/api/time-entries/bulk', ['entries' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries']);
});

it('fails with 422 when required fields are missing inside an entry', function () {
    $this->postJson('/api/time-entries/bulk', [
        'entries' => [
            ['company_id' => 1, 'employee_id' => 1, 'project_id' => 1, 'task_id' => 1],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.date', 'entries.0.hours']);
});

// ─── BelongsToCompany failures ────────────────────────────────────────────────

it('fails when the employee does not belong to the company', function () {
    $ctx          = setup();
    $otherEmployee = Employee::factory()->create(); // not attached to $ctx['company']

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [array_merge(entry($ctx), ['employee_id' => $otherEmployee->id])],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.employee_id']);
});

it('fails when the project does not belong to the company', function () {
    $ctx          = setup();
    $otherProject = Project::factory()->create(); // different company

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [array_merge(entry($ctx), ['project_id' => $otherProject->id])],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.project_id']);
});

it('fails when the task does not belong to the company', function () {
    $ctx       = setup();
    $otherTask = Task::factory()->create(); // different company

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [array_merge(entry($ctx), ['task_id' => $otherTask->id])],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.task_id']);
});

// ─── EmployeeAssignedToProject failure ────────────────────────────────────────

it('fails when the employee is not assigned to the project', function () {
    $company  = Company::factory()->create();
    $employee = Employee::factory()->create();
    $company->employees()->attach($employee);
    $project = Project::factory()->for($company)->create();
    // employee is NOT attached to project
    $task = Task::factory()->for($company)->create();

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [[
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
            'date'        => '2026-05-07',
            'hours'       => 8.0,
        ]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.employee_id']);
});

// ─── Business rule: DB conflict ───────────────────────────────────────────────

it('fails when the employee already has a different project on the same date', function () {
    $ctx          = setup();
    $otherProject = Project::factory()->for($ctx['company'])->create();
    $ctx['employee']->projects()->attach($otherProject);

    TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $otherProject->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 4.0,
    ]);

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [entry($ctx, '2026-05-07')],
    ])
        ->assertUnprocessable();
});

// ─── Business rule: intra-batch conflict ──────────────────────────────────────

it('fails when two entries in the same batch give the employee different projects on the same date', function () {
    $company  = Company::factory()->create();
    $employee = Employee::factory()->create();
    $company->employees()->attach($employee);
    $project1 = Project::factory()->for($company)->create();
    $project2 = Project::factory()->for($company)->create();
    $employee->projects()->attach([$project1->id, $project2->id]);
    $task = Task::factory()->for($company)->create();

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [
            ['company_id' => $company->id, 'employee_id' => $employee->id, 'project_id' => $project1->id, 'task_id' => $task->id, 'date' => '2026-05-07', 'hours' => 4.0],
            ['company_id' => $company->id, 'employee_id' => $employee->id, 'project_id' => $project2->id, 'task_id' => $task->id, 'date' => '2026-05-07', 'hours' => 4.0],
        ],
    ])
        ->assertUnprocessable();
});

// ─── All-or-nothing ───────────────────────────────────────────────────────────

it('rolls back all inserts when one entry conflicts with the database', function () {
    $ctx          = setup();
    $otherProject = Project::factory()->for($ctx['company'])->create();
    $ctx['employee']->projects()->attach($otherProject);

    // Pre-existing entry on 2026-05-08 for a different project
    TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $otherProject->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-08',
        'hours'       => 4.0,
    ]);

    $this->postJson('/api/time-entries/bulk', [
        'entries' => [
            entry($ctx, '2026-05-07'), // valid
            entry($ctx, '2026-05-08'), // conflicts with the pre-existing entry above
        ],
    ])
        ->assertUnprocessable();

    // Only the pre-existing entry should be in the database
    expect(TimeEntry::count())->toBe(1);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('updates a time entry and returns 200 with the updated resource', function () {
    $ctx   = setup();
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 8.0,
    ]);

    $this->putJson("/api/time-entries/{$entry->id}", [
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-09',
        'hours'       => 4.5,
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'id', 'company' => ['id', 'name'],
            'employee' => ['id', 'name'],
            'project'  => ['id', 'company_id', 'name'],
            'task'     => ['id', 'company_id', 'name'],
            'date', 'hours',
        ]])
        ->assertJsonPath('data.date', '2026-05-09')
        ->assertJsonPath('data.hours', 4.5);

    expect($entry->fresh()->hours)->toEqual('4.50');
});

it('returns 404 for a non-existent time entry', function () {
    $this->putJson('/api/time-entries/999999', [
        'employee_id' => 1,
        'project_id'  => 1,
        'task_id'     => 1,
        'date'        => '2026-05-07',
        'hours'       => 8.0,
    ])->assertNotFound();
});

it('returns 422 when required fields are missing on update', function () {
    $ctx   = setup();
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
    ]);

    $this->putJson("/api/time-entries/{$entry->id}", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['employee_id', 'project_id', 'task_id', 'date', 'hours']);
});

it('returns 422 when the employee does not belong to the company on update', function () {
    $ctx          = setup();
    $otherEmployee = Employee::factory()->create();
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
    ]);

    $this->putJson("/api/time-entries/{$entry->id}", [
        'employee_id' => $otherEmployee->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 8.0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['employee_id']);
});

it('returns 422 when the project does not belong to the company on update', function () {
    $ctx          = setup();
    $otherProject = Project::factory()->create();
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
    ]);

    $this->putJson("/api/time-entries/{$entry->id}", [
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $otherProject->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 8.0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);
});

it('returns 422 when the employee is not assigned to the project on update', function () {
    $ctx     = setup();
    $project2 = Project::factory()->for($ctx['company'])->create();
    // employee is NOT attached to project2
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
    ]);

    $this->putJson("/api/time-entries/{$entry->id}", [
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $project2->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 8.0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['employee_id']);
});

it('returns 422 when the update would create a project conflict on the same date', function () {
    $ctx      = setup();
    $project2 = Project::factory()->for($ctx['company'])->create();
    $ctx['employee']->projects()->attach($project2);

    // Another entry on the same date for a different project
    TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $project2->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-10',
        'hours'       => 4.0,
    ]);

    // Entry we want to update — currently on a different date
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 4.0,
    ]);

    // Moving it to 2026-05-10 would conflict with the other project on that date
    $this->putJson("/api/time-entries/{$entry->id}", [
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-10',
        'hours'       => 4.0,
    ])->assertUnprocessable();
});

it('allows updating an entry without triggering a self-conflict', function () {
    $ctx   = setup();
    $entry = TimeEntry::factory()->create([
        'company_id'  => $ctx['company']->id,
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 4.0,
    ]);

    // Updating only hours — same date, same project — should not conflict with itself
    $this->putJson("/api/time-entries/{$entry->id}", [
        'employee_id' => $ctx['employee']->id,
        'project_id'  => $ctx['project']->id,
        'task_id'     => $ctx['task']->id,
        'date'        => '2026-05-07',
        'hours'       => 6.0,
    ])->assertOk();
});

// ─── Filters ──────────────────────────────────────────────────────────────────

it('filters by employee_id', function () {
    $ctx1 = setup();
    $ctx2 = setup();

    TimeEntry::factory()->create(entry($ctx1));
    TimeEntry::factory()->create(entry($ctx2));

    $this->getJson("/api/time-entries?employee_id={$ctx1['employee']->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee.id', $ctx1['employee']->id);
});

it('filters by project_id', function () {
    $ctx1 = setup();
    $ctx2 = setup();

    TimeEntry::factory()->create(entry($ctx1));
    TimeEntry::factory()->create(entry($ctx2));

    $this->getJson("/api/time-entries?project_id={$ctx1['project']->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.project.id', $ctx1['project']->id);
});

it('filters by task_id', function () {
    $ctx1 = setup();
    $ctx2 = setup();

    TimeEntry::factory()->create(entry($ctx1));
    TimeEntry::factory()->create(entry($ctx2));

    $this->getJson("/api/time-entries?task_id={$ctx1['task']->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.task.id', $ctx1['task']->id);
});

it('filters by date_from', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-06'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-07'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08'));

    $this->getJson('/api/time-entries?date_from=2026-05-07')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by date_to', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-06'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-07'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08'));

    $this->getJson('/api/time-entries?date_to=2026-05-07')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by date range', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-05'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-06'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-07'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-09'));

    $this->getJson('/api/time-entries?date_from=2026-05-06&date_to=2026-05-08')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

// ─── Search ───────────────────────────────────────────────────────────────────

it('searches by employee name', function () {
    $company  = Company::factory()->create();
    $employee = Employee::factory()->create(['name' => 'uniqueemployeexyz']);
    $company->employees()->attach($employee);
    $project = Project::factory()->for($company)->create();
    $employee->projects()->attach($project);
    $task = Task::factory()->for($company)->create();

    TimeEntry::factory()->create([
        'company_id' => $company->id, 'employee_id' => $employee->id,
        'project_id' => $project->id, 'task_id' => $task->id,
    ]);
    TimeEntry::factory()->create(); // different, random employee

    $this->getJson('/api/time-entries?search=uniqueemployeexyz')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.employee.name', 'uniqueemployeexyz');
});

it('searches by project name', function () {
    $ctx     = setup();
    $project = Project::factory()->for($ctx['company'])->create(['name' => 'uniqueprojectxyz']);
    $ctx['employee']->projects()->attach($project);

    TimeEntry::factory()->create(array_merge(entry($ctx), ['project_id' => $project->id]));
    TimeEntry::factory()->create(entry($ctx)); // different project

    $this->getJson('/api/time-entries?search=uniqueprojectxyz')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.project.name', 'uniqueprojectxyz');
});

it('searches by company name', function () {
    $company  = Company::factory()->create(['name' => 'uniquecompanyxyz']);
    $employee = Employee::factory()->create();
    $company->employees()->attach($employee);
    $project = Project::factory()->for($company)->create();
    $employee->projects()->attach($project);
    $task = Task::factory()->for($company)->create();

    TimeEntry::factory()->create([
        'company_id' => $company->id, 'employee_id' => $employee->id,
        'project_id' => $project->id, 'task_id' => $task->id,
    ]);
    TimeEntry::factory()->create(); // different company

    $this->getJson('/api/time-entries?search=uniquecompanyxyz')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.company.name', 'uniquecompanyxyz');
});

// ─── Sorting ──────────────────────────────────────────────────────────────────

it('sorts by date ascending', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-09'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-07'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08'));

    $this->getJson("/api/time-entries?sort_by=date&sort_dir=asc&company_id={$ctx['company']->id}")
        ->assertOk()
        ->assertJsonPath('data.0.date', '2026-05-07');
});

it('sorts by date descending', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-07'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-09'));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08'));

    $this->getJson("/api/time-entries?sort_by=date&sort_dir=desc&company_id={$ctx['company']->id}")
        ->assertOk()
        ->assertJsonPath('data.0.date', '2026-05-09');
});

it('sorts by hours descending', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-07', 4.0));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08', 10.0));
    TimeEntry::factory()->create(entry($ctx, '2026-05-09', 6.0));

    $this->getJson("/api/time-entries?sort_by=hours&sort_dir=desc&company_id={$ctx['company']->id}")
        ->assertOk()
        ->assertJsonPath('data.0.hours', 10.0);
});

it('sorts by employee name ascending', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->for($company)->create();
    $task    = Task::factory()->for($company)->create();
    $empA    = Employee::factory()->create(['name' => 'Alice']);
    $empB    = Employee::factory()->create(['name' => 'Zara']);
    $company->employees()->attach([$empA->id, $empB->id]);
    $empA->projects()->attach($project);
    $empB->projects()->attach($project);

    TimeEntry::factory()->create(['company_id' => $company->id, 'employee_id' => $empB->id, 'project_id' => $project->id, 'task_id' => $task->id, 'date' => '2026-05-07', 'hours' => 4]);
    TimeEntry::factory()->create(['company_id' => $company->id, 'employee_id' => $empA->id, 'project_id' => $project->id, 'task_id' => $task->id, 'date' => '2026-05-08', 'hours' => 6]);

    $this->getJson("/api/time-entries?sort_by=employee&sort_dir=asc&company_id={$company->id}")
        ->assertOk()
        ->assertJsonPath('data.0.employee.name', 'Alice');

    $this->getJson("/api/time-entries?sort_by=employee&sort_dir=desc&company_id={$company->id}")
        ->assertOk()
        ->assertJsonPath('data.0.employee.name', 'Zara');
});

it('sorts by project name ascending', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create();
    $company->employees()->attach($employee);
    $task = Task::factory()->for($company)->create();
    $projA = Project::factory()->for($company)->create(['name' => 'Alpha']);
    $projB = Project::factory()->for($company)->create(['name' => 'Zeta']);
    $employee->projects()->attach([$projA->id, $projB->id]);

    TimeEntry::factory()->create(['company_id' => $company->id, 'employee_id' => $employee->id, 'project_id' => $projB->id, 'task_id' => $task->id, 'date' => '2026-05-07', 'hours' => 4]);
    TimeEntry::factory()->create(['company_id' => $company->id, 'employee_id' => $employee->id, 'project_id' => $projA->id, 'task_id' => $task->id, 'date' => '2026-05-08', 'hours' => 6]);

    $this->getJson("/api/time-entries?sort_by=project&sort_dir=asc&company_id={$company->id}")
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Alpha');
});

// ─── Pagination ───────────────────────────────────────────────────────────────

it('returns paginated results with meta', function () {
    TimeEntry::factory()->count(5)->create();

    $this->getJson('/api/time-entries?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.last_page', 3);
});

it('returns the second page', function () {
    TimeEntry::factory()->count(5)->create();

    $this->getJson('/api/time-entries?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

it('defaults to 25 entries per page', function () {
    TimeEntry::factory()->count(3)->create();

    $this->getJson('/api/time-entries')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 25);
});

it('caps per_page at 100', function () {
    TimeEntry::factory()->count(3)->create();

    $this->getJson('/api/time-entries?per_page=9999')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

// ─── Summary ──────────────────────────────────────────────────────────────────

it('returns summary with correct structure and aggregated totals', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-07', 4.0));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08', 6.0));

    $this->getJson('/api/time-entries/summary')
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'by_employee' => [['label', 'hours']],
            'by_project'  => [['label', 'hours']],
            'by_task'     => [['label', 'hours']],
            'by_date'     => [['label', 'hours']],
            'by_company'  => [['label', 'hours']],
        ]])
        ->assertJsonPath('data.by_employee.0.hours', 10.0)
        ->assertJsonCount(2, 'data.by_date');
});

it('summary respects company_id filter', function () {
    $ctx1 = setup();
    $ctx2 = setup();

    TimeEntry::factory()->create(entry($ctx1, '2026-05-07', 4.0));
    TimeEntry::factory()->create(entry($ctx2, '2026-05-07', 8.0));

    $this->getJson("/api/time-entries/summary?company_id={$ctx1['company']->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.by_company')
        ->assertJsonPath('data.by_company.0.hours', 4.0);
});

it('summary orders by_date ascending', function () {
    $ctx = setup();

    TimeEntry::factory()->create(entry($ctx, '2026-05-09', 2.0));
    TimeEntry::factory()->create(entry($ctx, '2026-05-07', 4.0));
    TimeEntry::factory()->create(entry($ctx, '2026-05-08', 6.0));

    $this->getJson("/api/time-entries/summary?company_id={$ctx['company']->id}")
        ->assertOk()
        ->assertJsonPath('data.by_date.0.label', '2026-05-07')
        ->assertJsonPath('data.by_date.2.label', '2026-05-09');
});

it('summary respects search filter', function () {
    $company  = Company::factory()->create();
    $employee = Employee::factory()->create(['name' => 'searchablesummaryxyz']);
    $company->employees()->attach($employee);
    $project = Project::factory()->for($company)->create();
    $employee->projects()->attach($project);
    $task = Task::factory()->for($company)->create();

    TimeEntry::factory()->create([
        'company_id' => $company->id, 'employee_id' => $employee->id,
        'project_id' => $project->id, 'task_id' => $task->id,
        'date' => '2026-05-07', 'hours' => 5.0,
    ]);
    TimeEntry::factory()->create(); // noise

    $this->getJson('/api/time-entries/summary?search=searchablesummaryxyz')
        ->assertOk()
        ->assertJsonCount(1, 'data.by_employee')
        ->assertJsonPath('data.by_employee.0.hours', 5.0);
});
