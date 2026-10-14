<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $permissions = [
            'manage users',
            'manage employees',
            'view employees',
            'manage attendance',
            'view attendance',
            'manage leave',
            'request leave',
            'manage payroll',
            'view payroll',
            'view own profile',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'Admin' => $permissions,

            'HR' => [
                'manage employees',
                'view employees',
                'manage attendance',
                'view attendance',
                'manage leave',
            ],

            'Employee' => [
                'view attendance',
                'request leave',
                'view own profile',
            ],

            'Accounting' => [
                'manage payroll',
                'view payroll',
            ],

            'Applicant' => [
                'view own profile',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }
    }
}