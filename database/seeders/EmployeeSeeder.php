<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;


class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'email' => 'admin@stmary.edu',
                'employee_id' => 'EMP-000001',
                'position' => 'School Administrator',
                'department' => 'highschool',
                'role_type' => 'nonteaching',
                'employment_status' => 'regular',
                'birthday' => '1980-03-12',
                'address' => 'General Trias, Cavite',
                'emergency_name' => 'Maria Stmary',
                'emergency_contact' => '09170000001',
            ],
            [
                'email' => 'hr@stmary.edu',
                'employee_id' => 'EMP-000002',
                'position' => 'HR Officer',
                'department' => 'highschool',
                'role_type' => 'nonteaching',
                'employment_status' => 'regular',
                'birthday' => '1988-07-21',
                'address' => 'General Trias, Cavite',
                'emergency_name' => 'Pedro Stmary',
                'emergency_contact' => '09170000002',
            ],
            [
                'email' => 'accounting@stmary.edu',
                'employee_id' => 'EMP-000003',
                'position' => 'Accounting Staff',
                'department' => 'highschool',
                'role_type' => 'nonteaching',
                'employment_status' => 'regular',
                'birthday' => '1990-11-05',
                'address' => 'General Trias, Cavite',
                'emergency_name' => 'Ana Stmary',
                'emergency_contact' => '09170000003',
            ],
            [
                'email' => 'john.doe@stmary.edu',
                'employee_id' => 'EMP-000005',
                'position' => 'Science Teacher',
                'department' => 'highschool',
                'role_type' => 'teaching',
                'employment_status' => 'regular',
                'birthday' => '1992-02-18',
                'address' => 'General Trias, Cavite',
                'emergency_name' => 'Jane Doe',
                'emergency_contact' => '09170000005',
            ],
        ];

        foreach ($employees as $data) {
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                $this->command?->warn(
                    "Skipped {$data['email']}: user not found."
                );
                continue;
            }

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => $data['employee_id'],
                    'name' => $user->name,
                    'position' => $data['position'],
                    'department' => $data['department'],
                    'role_type' => $data['role_type'],
                    'employment_status' => $data['employment_status'],
                    'email' => $user->email,
                    'birthday' => $data['birthday'],
                    'address' => $data['address'],
                    'emergency_name' => $data['emergency_name'],
                    'emergency_contact' => $data['emergency_contact'],
                ]
            );

            $this->command?->info(
                "Employee linked: {$user->email}"
            );
        }
    }
}