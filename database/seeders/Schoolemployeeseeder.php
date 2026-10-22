<?php

namespace Database\Seeders;

use App\Models\SchoolEmployee;
use Illuminate\Database\Seeder;

class SchoolEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['HR-321942',    'Maria Santos',      'HR Manager',          'seniorhigh', 'teaching',    'maria.santos@smms.edu.ph',       '1979-05-12', '123 Saint Ave, City',  'Ana Santos',      '+639171234567'],
            ['EMP-234482',   'Joshua Aguilera',   'Mathematics Teacher', 'elementary', 'teaching',    'joshua.aguilera@smms.edu.ph',    '1994-02-28', '45 School Rd, City',   'Liza Aguilera',   '+639181234567'],
            ['ACC-054535',   'Joselito Marasigan','Accounting Clerk',    'highschool', 'nonteaching', 'joselito.marasigan@smms.edu.ph', '1987-09-14', '78 Maple St, City',    'Ramon Marasigan', '+639191234567'],
            ['ADMIN-094773', 'Daniel Reyes',      'Administrator',       'seniorhigh', 'nonteaching', 'daniel.reyes@smms.edu.ph',       '1976-11-02', '9 Health Blvd, City',  'Marta Reyes',     '+639121234567'],
        ];

        foreach ($rows as [$id, $name, $position, $dept, $role, $email, $bday, $addr, $ecName, $ecNum]) {
            SchoolEmployee::updateOrCreate(['employee_id' => $id], [
                'name' => $name, 'position' => $position, 'department' => $dept, 'role_type' => $role,
                'email' => $email, 'birthday' => $bday, 'address' => $addr,
                'emergency_name' => $ecName, 'emergency_contact' => $ecNum,
            ]);
        }
    }
}