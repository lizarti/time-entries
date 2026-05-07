<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    private const NAMES = [
        'Development',
        'Design',
        'Testing',
        'Code Review',
        'Documentation',
        'Deployment',
        'Bug Fix',
        'Planning',
        'QA',
        'Technical Writing',
    ];

    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $names = collect(self::NAMES)->shuffle()->take(fake()->numberBetween(4, 6));

            foreach ($names as $name) {
                Task::create([
                    'company_id' => $company->id,
                    'name'       => $name,
                ]);
            }
        }
    }
}
