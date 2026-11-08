<?php

use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\HrmsNotifier;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Admin', 'HR'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function createPendingLeaveRequest(User $employee, string $type = 'Vacation Leave'): LeaveRequest
{
    Employee::create([
        'user_id' => $employee->id,
        'employee_id' => 'LEAVE-'.$employee->id,
        'name' => $employee->name,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'leave-'.$employee->id.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
    ]);

    return LeaveRequest::create([
        'user_id' => $employee->id,
        'employee_name' => $employee->name,
        'type' => $type,
        'requested_date' => now()->toDateString(),
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeek()->addDay()->toDateString(),
        'reason' => 'Family event',
        'status' => LeaveRequest::PENDING,
        'hr_status' => LeaveRequest::PENDING,
        'admin_status' => LeaveRequest::PENDING,
    ]);
}

test('new leave requests notify HR and Admin', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = User::factory()->create();
    $leave = createPendingLeaveRequest($employee);

    HrmsNotifier::leaveSubmitted($leave);

    expect($hr->notifications()->count())->toBe(1)
        ->and($admin->notifications()->count())->toBe(1)
        ->and($hr->notifications()->first()->data['url'])->toBe(route('leaves.index'));
});

test('employee is notified only after HR and Admin both approve', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = User::factory()->create();
    $leave = createPendingLeaveRequest($employee);

    $this->actingAs($hr)
        ->patch(route('leave.status', $leave), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($leave->fresh()->status)->toBe(LeaveRequest::PENDING)
        ->and($employee->notifications()->count())->toBe(0)
        ->and($admin->notifications()->count())->toBe(1)
        ->and($admin->notifications()->first()->data['title'])->toBe('HR approved a leave request');

    $this->actingAs($admin)
        ->patch(route('leave.status', $leave), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    $attendance = Attendance::where('employee_id', $employee->employee->id)
        ->orderBy('date')
        ->get();

    expect($leave->fresh()->status)->toBe(LeaveRequest::APPROVED)
        ->and($attendance)->toHaveCount(2)
        ->and($attendance->pluck('status')->unique()->all())->toBe([Attendance::PAID_LEAVE])
        ->and($attendance->pluck('hours_worked')->unique()->all())->toBe(['0.00'])
        ->and($employee->notifications()->count())->toBe(1)
        ->and($employee->notifications()->first()->data['title'])->toBe('Leave request approved')
        ->and($employee->notifications()->first()->data['url'])->toBe(route('leaves.employee.index'));
});

test('approved non-convertible leave is recorded as unpaid leave in attendance', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = User::factory()->create();
    $leave = createPendingLeaveRequest($employee, 'Non-Convertible Leave');

    $this->actingAs($hr)
        ->patch(route('leave.status', $leave), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch(route('leave.status', $leave), ['decision' => 'approved'])
        ->assertSessionHasNoErrors();

    $attendance = Attendance::where('employee_id', $employee->employee->id)->get();

    expect($attendance)->toHaveCount(2)
        ->and($attendance->pluck('status')->unique()->all())->toBe([Attendance::UNPAID_LEAVE]);
});

test('Admin cannot decide before HR approves', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = User::factory()->create();
    $leave = createPendingLeaveRequest($employee);

    $this->actingAs($admin)
        ->patch(route('leave.status', $leave), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    expect($leave->fresh()->status)->toBe(LeaveRequest::PENDING)
        ->and($leave->fresh()->admin_status)->toBe(LeaveRequest::PENDING)
        ->and($employee->notifications()->count())->toBe(0);
});

test('a rejection notifies the employee once with the reason', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = User::factory()->create();
    $leave = createPendingLeaveRequest($employee);

    $this->actingAs($hr)
        ->patch(route('leave.status', $leave), [
            'decision' => 'rejected',
            'rejection_reason' => 'Insufficient leave balance.',
        ])
        ->assertSessionHasNoErrors();

    expect($leave->fresh()->status)->toBe(LeaveRequest::REJECTED)
        ->and($leave->fresh()->admin_status)->toBe(LeaveRequest::REJECTED)
        ->and($admin->notifications()->count())->toBe(1)
        ->and($admin->notifications()->first()->data['title'])->toBe('HR rejected a leave request')
        ->and($admin->notifications()->first()->data['message'])->toContain('Insufficient leave balance.')
        ->and($employee->notifications()->count())->toBe(1)
        ->and($employee->notifications()->first()->data['message'])->toContain('Insufficient leave balance.');

    $this->actingAs($admin)
        ->patch(route('leave.status', $leave), ['decision' => 'approved'])
        ->assertSessionHasErrors('decision');

    expect($employee->notifications()->count())->toBe(1);
});
