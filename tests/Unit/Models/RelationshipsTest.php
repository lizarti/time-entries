<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;

describe('Company', function () {
    it('belongs to many employees', function () {
        $company   = Company::factory()->create();
        $employees = Employee::factory()->count(2)->create();
        $company->employees()->attach($employees);

        expect($company->fresh()->employees)->toHaveCount(2)
            ->each->toBeInstanceOf(Employee::class);
    });

    it('has many projects', function () {
        $company = Company::factory()->create();
        Project::factory()->count(3)->for($company)->create();

        expect($company->fresh()->projects)->toHaveCount(3)
            ->each->toBeInstanceOf(Project::class);
    });

    it('has many tasks', function () {
        $company = Company::factory()->create();
        Task::factory()->count(3)->for($company)->create();

        expect($company->fresh()->tasks)->toHaveCount(3)
            ->each->toBeInstanceOf(Task::class);
    });

    it('has many time entries', function () {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project  = Project::factory()->for($company)->create();
        $task     = Task::factory()->for($company)->create();

        TimeEntry::factory()->count(2)->create([
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
        ]);

        expect($company->fresh()->timeEntries)->toHaveCount(2)
            ->each->toBeInstanceOf(TimeEntry::class);
    });
});

describe('Employee', function () {
    it('belongs to many companies', function () {
        $employee  = Employee::factory()->create();
        $companies = Company::factory()->count(2)->create();
        $employee->companies()->attach($companies);

        expect($employee->fresh()->companies)->toHaveCount(2)
            ->each->toBeInstanceOf(Company::class);
    });

    it('belongs to many projects', function () {
        $employee = Employee::factory()->create();
        $projects = Project::factory()->count(2)->create();
        $employee->projects()->attach($projects);

        expect($employee->fresh()->projects)->toHaveCount(2)
            ->each->toBeInstanceOf(Project::class);
    });

    it('has many time entries', function () {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project  = Project::factory()->for($company)->create();
        $task     = Task::factory()->for($company)->create();

        TimeEntry::factory()->count(2)->create([
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
        ]);

        expect($employee->fresh()->timeEntries)->toHaveCount(2)
            ->each->toBeInstanceOf(TimeEntry::class);
    });
});

describe('Project', function () {
    it('belongs to a company', function () {
        $project = Project::factory()->create();

        expect($project->company)->toBeInstanceOf(Company::class)
            ->and($project->company->id)->toBe($project->company_id);
    });

    it('belongs to many employees', function () {
        $project   = Project::factory()->create();
        $employees = Employee::factory()->count(2)->create();
        $project->employees()->attach($employees);

        expect($project->fresh()->employees)->toHaveCount(2)
            ->each->toBeInstanceOf(Employee::class);
    });

    it('has many time entries', function () {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project  = Project::factory()->for($company)->create();
        $task     = Task::factory()->for($company)->create();

        TimeEntry::factory()->count(2)->create([
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
        ]);

        expect($project->fresh()->timeEntries)->toHaveCount(2)
            ->each->toBeInstanceOf(TimeEntry::class);
    });
});

describe('Task', function () {
    it('belongs to a company', function () {
        $task = Task::factory()->create();

        expect($task->company)->toBeInstanceOf(Company::class)
            ->and($task->company->id)->toBe($task->company_id);
    });

    it('has many time entries', function () {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project  = Project::factory()->for($company)->create();
        $task     = Task::factory()->for($company)->create();

        TimeEntry::factory()->count(2)->create([
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
        ]);

        expect($task->fresh()->timeEntries)->toHaveCount(2)
            ->each->toBeInstanceOf(TimeEntry::class);
    });
});

describe('TimeEntry', function () {
    it('belongs to a company, employee, project and task', function () {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project  = Project::factory()->for($company)->create();
        $task     = Task::factory()->for($company)->create();

        $entry = TimeEntry::factory()->create([
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
        ]);

        expect($entry->company)->toBeInstanceOf(Company::class)
            ->and($entry->employee)->toBeInstanceOf(Employee::class)
            ->and($entry->project)->toBeInstanceOf(Project::class)
            ->and($entry->task)->toBeInstanceOf(Task::class);
    });

    it('casts date as a Carbon instance', function () {
        $entry = TimeEntry::factory()->create();

        expect($entry->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('casts hours as a decimal string', function () {
        $company  = Company::factory()->create();
        $employee = Employee::factory()->create();
        $project  = Project::factory()->for($company)->create();
        $task     = Task::factory()->for($company)->create();

        $entry = TimeEntry::factory()->create([
            'company_id'  => $company->id,
            'employee_id' => $employee->id,
            'project_id'  => $project->id,
            'task_id'     => $task->id,
            'hours'       => 7.5,
        ]);

        expect($entry->fresh()->hours)->toBe('7.50');
    });
});
