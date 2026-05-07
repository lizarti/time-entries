<?php

use App\Models\Company;

it('returns a list of companies', function () {
    Company::factory()->count(3)->create();

    $this->getJson('/api/companies')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name']]]);
});

it('filters companies by name', function () {
    Company::factory()->create(['name' => 'Acme Corp']);
    Company::factory()->create(['name' => 'Globex Corp']);

    $this->getJson('/api/companies?search=acme')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Acme Corp');
});

it('returns an empty list when no company matches the search', function () {
    Company::factory()->create(['name' => 'Acme Corp']);

    $this->getJson('/api/companies?search=xyz')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
