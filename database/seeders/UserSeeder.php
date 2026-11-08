<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@example.com',
                'password' => 'password123',
                'role' => 'Admin',
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr@example.com',
                'password' => 'password123',
                'role' => 'HR',
            ],
            [
                'name' => 'Regular Employee',
                'email' => 'employee@example.com',
                'password' => 'password123',
                'role' => 'Employee',
            ],
            [
                'name' => 'Accounting Staff',
                'email' => 'accounting@example.com',
                'password' => 'password123',
                'role' => 'Accounting',
            ],
            [
                'name' => 'Job Applicant',
                'email' => 'applicant@example.com',
<<<<<<< HEAD
                'password' => 'passwor123',
=======
                'password' => 'password123',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                'role' => 'Applicant',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );

            $role = Role::where('name', $userData['role'])
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user->syncRoles([$role]);
        }
    }
}
<<<<<<< HEAD
=======

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
