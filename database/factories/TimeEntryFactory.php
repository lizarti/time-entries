<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'company_id'  => $company->id,
            'employee_id' => Employee::factory(),
            'project_id'  => Project::factory()->for($company),
            'task_id'     => Task::factory()->for($company),
            'date'        => $this->faker->dateTimeBetween('-1 year')->format('Y-m-d'),
            'hours'       => $this->faker->randomFloat(2, 0.5, 8),
        ];
    }
}
