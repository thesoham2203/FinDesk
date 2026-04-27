<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@findesk.test',
        ]);

        $departments = Department::all();

        foreach ($departments as $department) {
            User::factory()->manager()->create([
                'department_id' => $department->id,
                'name' => $department->name.' Manager',
                'email' => mb_strtolower((string) $department->name).'_manager@findesk.test',
            ]);
        }

        User::factory()->count(3)->employee()->create();

        User::factory()->accountant()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@findesk.test',
        ]);
    }
}
