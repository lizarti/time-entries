<?php

use App\Models\Company;
use App\Models\Project;

it('returns projects belonging to the given company', function () {
    $company = Company::factory()->create();
    Project::factory()->count(2)->for($company)->create();

    Project::factory()->create(); // belongs to another company

    $this->getJson("/api/companies/{$company->id}/projects")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'company_id', 'name']]]);
});

it('returns 404 when the company does not exist', function () {
    $this->getJson('/api/companies/999/projects')
        ->assertNotFound();
});
