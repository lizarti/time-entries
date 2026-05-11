<?php

use App\Models\Company;
use App\Models\Task;

it('returns tasks belonging to the given company', function () {
    $company = Company::factory()->create();
    Task::factory()->count(2)->for($company)->create();

    Task::factory()->create(); // belongs to another company

    $this->getJson("/api/tasks?company_id={$company->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'company_id', 'name']]]);
});

it('returns 404 when the company does not exist', function () {
    $this->getJson('/api/tasks?company_id=999')
        ->assertNotFound();
});

it('returns all tasks when no company_id is given', function () {
    Task::factory()->count(3)->create();

    $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
