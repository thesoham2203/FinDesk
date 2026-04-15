<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

final class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::query()->create([
            'name' => 'Engineering',
            'description' => 'Software development and technical operations',
            'monthly_budget' => 5000000, // ₹50,000 in paise
        ]);

        Department::query()->create([
            'name' => 'Marketing',
            'description' => 'Marketing and business development',
            'monthly_budget' => 3000000, // ₹30,000 in paise
        ]);

        Department::query()->create([
            'name' => 'Sales',
            'description' => 'Sales and client relations',
            'monthly_budget' => 4000000, // ₹40,000 in paise
        ]);
    }
}
