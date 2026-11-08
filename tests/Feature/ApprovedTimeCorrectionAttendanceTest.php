<?php

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\TimeCorrection;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Admin', 'HR'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

test('attendance is updated when both HR and Admin approve a time correction', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = Employee::create([
        'employee_id' => 'CORRECTION-001',
        'name' => 'Correction Test Employee',
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'correction-test@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
        'employment_status' => 'regular',
    ]);

    $attendance = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'date' => '2026-11-02',
        'time_in' => '07:10',
        'time_out' => '15:00',
        'hours_worked' => 6.5,
        'status' => 'Undertime',
        'archived' => true,
    ]);

    $correction = TimeCorrection::create([
        'employee_id' => $employee->id,
        'correction_date' => '2026-11-02',
        'correction_type' => 'Time Out',
        'incorrect_time' => '15:00',
        'correct_time' => '16:30',
        'note' => 'The recorded time out was incorrect.',
        'hr_status' => TimeCorrection::PENDING,
        'admin_status' => TimeCorrection::PENDING,
    ]);

    $this->actingAs($hr)
        ->patch(route('decide'), [
            'time_correction_id' => $correction->id,
            'decision' => TimeCorrection::APPROVED,
        ])
        ->assertSessionHas('success', 'Request Approved.');

    expect($attendance->fresh()->time_out)->toBe('15:00:00')
        ->and($correction->fresh()->applied_at)->toBeNull();

    $this->actingAs($admin)
        ->patch(route('decide'), [
            'time_correction_id' => $correction->id,
            'decision' => TimeCorrection::APPROVED,
        ])
        ->assertSessionHas('success', 'Request Approved. Attendance has been updated.');

    $updatedAttendance = $attendance->fresh();
    expect($updatedAttendance->time_out)->toBe('16:30:00')
        ->and((float) $updatedAttendance->hours_worked)->toBe(8.0)
        ->and($updatedAttendance->status)->toBe('Present')
        ->and($updatedAttendance->archived)->toBeFalse()
        ->and($correction->fresh()->applied_at)->not->toBeNull();
});

test('admin cannot decide before HR approval and HR rejection automatically rejects for admin', function () {
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $employee = Employee::create([
        'employee_id' => 'CORRECTION-002',
        'name' => 'HR First Test Employee',
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'hr-first-test@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
        'employment_status' => 'regular',
    ]);

    $correction = TimeCorrection::create([
        'employee_id' => $employee->id,
        'correction_date' => '2026-11-02',
        'correction_type' => 'Time Out',
        'incorrect_time' => '15:00',
        'correct_time' => '16:30',
        'note' => 'The recorded time out was incorrect.',
        'hr_status' => TimeCorrection::PENDING,
        'admin_status' => TimeCorrection::PENDING,
    ]);

    foreach ([TimeCorrection::APPROVED, TimeCorrection::REJECTED] as $decision) {
        $this->actingAs($admin)
            ->patch(route('decide'), [
                'time_correction_id' => $correction->id,
                'decision' => $decision,
                'rejection_reason' => $decision === TimeCorrection::REJECTED ? 'Admin review' : null,
            ])
            ->assertSessionHasErrors([
                'decision' => 'You can review this request only after HR approves it.',
            ]);
    }

    expect($correction->fresh()->admin_status)->toBe(TimeCorrection::PENDING);

    $this->actingAs($hr)
        ->patch(route('decide'), [
            'time_correction_id' => $correction->id,
            'decision' => TimeCorrection::REJECTED,
            'rejection_reason' => 'The submitted proof does not support the correction.',
        ])
        ->assertSessionHas('success', 'Request Rejected.');

    $correction->refresh();
    expect($correction->hr_status)->toBe(TimeCorrection::REJECTED)
        ->and($correction->admin_status)->toBe(TimeCorrection::REJECTED)
        ->and($correction->admin_reviewed_at)->not->toBeNull()
        ->and($correction->admin_reviewed_by)->toBeNull();

    $this->actingAs($admin)
        ->patch(route('decide'), [
            'time_correction_id' => $correction->id,
            'decision' => TimeCorrection::APPROVED,
        ])
        ->assertSessionHasErrors([
            'decision' => 'You have already reviewed this request.',
        ]);
});
