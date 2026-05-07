<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Golden Mango',
            'Bright Kiwi',
            'Sharp Lemon',
            'Bold Papaya',
            'Crisp Apple',
            'Swift Peach',
            'Vivid Lime',
            'Iron Grape',
        ];

        foreach ($names as $name) {
            Company::create(['name' => $name]);
        }
    }
}
