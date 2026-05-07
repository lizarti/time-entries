<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    private const NAMES = [
        'Website Redesign',
        'Mobile Application',
        'API Integration',
        'Customer Portal',
        'ERP Implementation',
        'Cloud Migration',
        'E-Commerce Platform',
        'Data Pipeline',
        'Internal Dashboard',
        'Brand Refresh',
    ];

    public function run(): void
    {
        $companies = Company::with('employees')->get();

        foreach ($companies as $company) {
            $names = collect(self::NAMES)->shuffle()->take(fake()->numberBetween(2, 4));

            foreach ($names as $name) {
                $project = Project::create([
                    'company_id' => $company->id,
                    'name'       => $name,
                ]);

                // Assign a random non-empty subset of this company's employees
                $assigned = $company->employees
                    ->shuffle()
                    ->take(fake()->numberBetween(1, max(1, $company->employees->count())));

                $project->employees()->attach($assigned);
            }
        }
    }
}
