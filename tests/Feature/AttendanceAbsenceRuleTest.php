<?php

use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\User;
use App\Services\AttendanceAbsenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Cache::flush();
});

function createAbsenceRuleEmployee(string $suffix, string $roleType = 'teaching', string $position = 'Teacher'): int
{
    return DB::table('employees')->insertGetId([
        'employee_id' => 'ABS-'.$suffix,
        'name' => 'Employee '.$suffix,
        'position' => $position,
        'department' => 'elementary',
        'role_type' => $roleType,
        'email' => 'absence-'.$suffix.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
        'employment_status' => 'regular',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('marks employees without attendance absent at 4:30 PM, but not before', function () {
    $employeeId = createAbsenceRuleEmployee('cutoff');
    $service = app(AttendanceAbsenceService::class);

    expect($service->markUnpunchedEmployeesAbsent(Carbon::parse('2026-11-05 16:29:00')))
        ->toBe(0)
        ->and(Attendance::where('employee_id', $employeeId)->exists())->toBeFalse()
        ->and($service->markUnpunchedEmployeesAbsent(Carbon::parse('2026-11-05 16:30:00')))
        ->toBe(1)
        ->and(Attendance::where('employee_id', $employeeId)->firstOrFail()->status)
        ->toBe(Attendance::ABSENT)
        ->and($service->markUnpunchedEmployeesAbsent(Carbon::parse('2026-11-05 16:31:00')))
        ->toBe(0);
});

it('preserves existing attendance and skips holidays and non-working weekends', function () {
    $employeeWithRecord = createAbsenceRuleEmployee('leave');
    $saturdayEmployee = createAbsenceRuleEmployee('saturday');
    $maintenanceEmployee = createAbsenceRuleEmployee('maintenance', 'nonteaching', 'Maintenance Staff');
    $date = '2026-11-07';

    Attendance::create([
        'employee_id' => $employeeWithRecord,
        'date' => $date,
        'hours_worked' => 0,
        'status' => Attendance::PAID_LEAVE,
        'archived' => false,
    ]);

    $created = app(AttendanceAbsenceService::class)->markUnpunchedEmployeesAbsent(
        Carbon::parse($date.' 16:30:00')
    );

    expect($created)->toBe(1)
        ->and(Attendance::where('employee_id', $employeeWithRecord)->whereDate('date', $date)->value('status'))
        ->toBe(Attendance::PAID_LEAVE)
        ->and(Attendance::where('employee_id', $saturdayEmployee)->whereDate('date', $date)->exists())
        ->toBeFalse()
        ->and(Attendance::where('employee_id', $maintenanceEmployee)->whereDate('date', $date)->value('status'))
        ->toBe(Attendance::ABSENT);

    Cache::flush();
    NonWorkingDay::create([
        'date' => '2026-11-09',
        'name' => 'School Suspension',
        'category' => 'School Suspension',
        'pay_type' => NonWorkingDay::UNPAID,
    ]);

    expect(app(AttendanceAbsenceService::class)->markUnpunchedEmployeesAbsent(
        Carbon::parse('2026-11-09 16:30:00')
    ))->toBe(0)
        ->and(Attendance::whereDate('date', '2026-11-09')->exists())->toBeFalse();
});

it('marks missing attendance when HR opens attendance management after the cutoff', function () {
    Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
    $hr = User::factory()->create();
    $hr->assignRole('HR');
    $employeeId = createAbsenceRuleEmployee('hr-module');
    Carbon::setTestNow('2026-11-05 16:30:00');

    $this->actingAs($hr)->get(route('attendance.index'))->assertOk();

    expect(Attendance::where('employee_id', $employeeId)->whereDate('date', '2026-11-05')->value('status'))
        ->toBe(Attendance::ABSENT);

    Carbon::setTestNow();
});
