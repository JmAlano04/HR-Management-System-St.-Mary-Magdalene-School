<?php

use App\Models\Employee;
use App\Models\SchoolEmployee;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Admin', 'HR', 'Accounting', 'Employee'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

test('internal staff accounts are excluded while regular school employees remain visible', function () {
    $internalHr = User::factory()->create(['email' => 'hr@stmary.edu']);
    $internalHr->assignRole('HR');

    $internalAdmin = User::factory()->create(['email' => 'admin@stmary.edu']);
    $internalAdmin->assignRole('Admin');

    $internalAccounting = User::factory()->create(['email' => 'accounting@stmary.edu']);
    $internalAccounting->assignRole('Accounting');

    $schoolEmployee = User::factory()->create(['email' => 'teacher@stmary.edu']);
    $schoolEmployee->assignRole('Employee');

    $externalAdmin = User::factory()->create(['email' => 'admin@example.com']);
    $externalAdmin->assignRole('Admin');

    $employees = collect([
        [$internalHr, 'INTERNAL-HR'],
        [$internalAdmin, 'INTERNAL-ADMIN'],
        [$internalAccounting, 'INTERNAL-ACCOUNTING'],
        [$schoolEmployee, 'SCHOOL-EMPLOYEE'],
        [$externalAdmin, 'EXTERNAL-ADMIN'],
    ])->mapWithKeys(function (array $entry) {
        [$user, $employeeId] = $entry;

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'name' => $user->name,
            'position' => 'Teacher',
            'department' => 'elementary',
            'role_type' => 'teaching',
            'email' => $user->email,
            'birthday' => '1990-01-01',
            'address' => 'Test address',
            'emergency_name' => 'Emergency Contact',
            'emergency_contact' => '09123456789',
            'employment_status' => 'regular',
        ]);

        return [$employeeId => $employee->id];
    });

    $visibleEmployeeIds = Employee::excludeInternalStaff()->pluck('id')->all();
    $visibleSchoolEmployeeIds = SchoolEmployee::excludeInternalStaff()->pluck('id')->all();

    foreach (['INTERNAL-HR', 'INTERNAL-ADMIN', 'INTERNAL-ACCOUNTING'] as $employeeId) {
        expect($visibleEmployeeIds)->not->toContain($employees[$employeeId])
            ->and($visibleSchoolEmployeeIds)->not->toContain($employees[$employeeId]);
    }

    expect($visibleEmployeeIds)->toContain($employees['SCHOOL-EMPLOYEE'])
        ->toContain($employees['EXTERNAL-ADMIN'])
        ->and($visibleSchoolEmployeeIds)->toContain($employees['SCHOOL-EMPLOYEE'])
        ->toContain($employees['EXTERNAL-ADMIN']);
});
