<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $count = fake()->numberBetween(3, 4);

            $employees = Employee::factory()->count($count)->create();

            $company->employees()->attach($employees);
        }
    }
}
