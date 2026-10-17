<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['employee_id' => 'HR-321942',    'name' => 'Maria Santos',       'position' => 'HR Manager',          'department' => 'seniorhigh', 'role_type' => 'teaching',    'email' => 'maria.santos@smms.edu.ph',       'birthday' => '1979-05-12', 'address' => '123 Saint Ave, City', 'emergency_name' => 'Ana Santos',       'emergency_contact' => '+639171234567'],
            ['employee_id' => 'EMP-234482',   'name' => 'Joshua Aguilera',    'position' => 'Mathematics Teacher', 'department' => 'elementary', 'role_type' => 'teaching',    'email' => 'joshua.aguilera@smms.edu.ph',    'birthday' => '1994-02-28', 'address' => '45 School Rd, City',  'emergency_name' => 'Liza Aguilera',    'emergency_contact' => '+639181234567'],
            ['employee_id' => 'ACC-054535',   'name' => 'Joselito Marasigan', 'position' => 'Accounting Clerk',    'department' => 'highschool', 'role_type' => 'teaching',    'email' => 'joselito.marasigan@smms.edu.ph', 'birthday' => '1987-09-14', 'address' => '78 Maple St, City',   'emergency_name' => 'Ramon Marasigan',  'emergency_contact' => '+639191234567'],
            ['employee_id' => 'ADMIN-094773', 'name' => 'Daniel Reyes',       'position' => 'Administrator',       'department' => 'seniorhigh', 'role_type' => 'nonteaching', 'email' => 'daniel.reyes@smms.edu.ph',       'birthday' => '1976-11-02', 'address' => '9 Health Blvd, City', 'emergency_name' => 'Marta Reyes',      'emergency_contact' => '+639121234567'],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(['employee_id' => $employee['employee_id']], $employee);
        }
    }
}