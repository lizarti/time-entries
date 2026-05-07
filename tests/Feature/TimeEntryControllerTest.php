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
        ->assertJsonStructure(['data' => [['id', 'company_id', 'employee_id', 'project_id', 'task_id', 'date', 'hours']]]);
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
        ->assertJsonStructure(['data' => [['id', 'company_id', 'employee_id', 'project_id', 'task_id', 'date', 'hours']]]);

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
