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
