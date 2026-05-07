<?php

use App\Models\Company;
use App\Models\Employee;

it('returns employees belonging to the given company', function () {
    $company   = Company::factory()->create();
    $employees = Employee::factory()->count(2)->create();
    $company->employees()->attach($employees);

    Employee::factory()->create(); // belongs to no company

    $this->getJson("/api/companies/{$company->id}/employees")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'name']]]);
});

it('returns 404 when the company does not exist', function () {
    $this->getJson('/api/companies/999/employees')
        ->assertNotFound();
});
