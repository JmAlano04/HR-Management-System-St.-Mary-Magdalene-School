<?php

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Accounting', 'Admin', 'Employee', 'HR'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function createPayslipNotificationEmployee(User $user): Employee
{
    return Employee::create([
        'user_id' => $user->id,
        'employee_id' => 'PAYSLIP-'.$user->id,
        'name' => $user->name,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'payslip-'.$user->id.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
    ]);
}

test('payslip is announced to reviewers, decisions notify Accounting, and employee is notified on release', function () {
    $employeeUser = User::factory()->create();
    $employee = createPayslipNotificationEmployee($employeeUser);
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $accounting = User::factory()->create();
    $accounting->assignRole('Accounting');
    $payslip = Payslip::create([
        'employee_id' => $employee->id,
        'cutoff' => '15th',
        'transfer_date' => now()->toDateString(),
    ]);

    $this->actingAs($accounting)
        ->post(route('salary-monitoring.send'), [
            'role' => 'admin',
            'ids' => [$payslip->id],
        ])
        ->assertSessionHasNoErrors();

    foreach ([$hr, $admin] as $reviewer) {
        expect($reviewer->notifications()->first()->data['title'])->toBe('Payslip needs review')
            ->and($reviewer->notifications()->first()->data['message'])->toContain('15th Pay Period')
            ->and($reviewer->notifications()->first()->data['url'])->toBe(route('payslips.index'));
    }

    $this->actingAs($hr)
        ->patch(route('payroll.approve'), ['payslip_id' => $payslip->id])
        ->assertSessionHasNoErrors();

    expect($accounting->notifications()->count())->toBe(1)
        ->and($accounting->notifications()->first()->data['message'])->toContain('15th Pay Period')
        ->and($admin->notifications()->count())->toBe(2)
        ->and($employeeUser->notifications()->count())->toBe(0);

    $this->actingAs($admin)
        ->patch(route('payroll.approve'), ['payslip_id' => $payslip->id])
        ->assertSessionHasNoErrors();

    expect($accounting->notifications()->count())->toBe(3)
        ->and($accounting->notifications()->get()->pluck('data')->pluck('title'))
        ->toContain('Admin approved a payslip', 'Payslip ready for distribution')
        ->and($accounting->notifications()->get()->pluck('data')->pluck('message')->first(fn ($message) => str_contains($message, 'ready to send')))
        ->toContain('15th Pay Period')
        ->and($employeeUser->notifications()->count())->toBe(0);

    $this->actingAs($accounting)
        ->post(route('salary-monitoring.send'), [
            'role' => 'employee',
            'ids' => [$payslip->id],
        ])
        ->assertSessionHasNoErrors();

    expect($employeeUser->notifications()->count())->toBe(1)
        ->and($employeeUser->notifications()->first()->data['title'])->toBe('Your payslip is available')
        ->and($employeeUser->notifications()->first()->data['message'])->toContain('15th Pay Period')
        ->and($employeeUser->notifications()->first()->data['url'])->toBe(route('salary-records.employee.index'));

    $nextPayslip = Payslip::create([
        'employee_id' => $employee->id,
        'cutoff' => '30th',
        'transfer_date' => now()->addMonth()->toDateString(),
    ]);

    $this->actingAs($accounting)
        ->post(route('salary-monitoring.send'), [
            'role' => 'admin',
            'ids' => [$nextPayslip->id],
        ])
        ->assertSessionHasNoErrors();

    expect($hr->notifications()->orderByDesc('id')->first()->data['message'])->toContain('30th Pay Period')
        ->and($admin->notifications()->orderByDesc('id')->first()->data['message'])->toContain('30th Pay Period');
});
