<?php

use App\Http\Controllers\AttendanceController;
use App\Models\Attendance;
use App\Models\SchoolEmployee;

it('shows existing noon to 1 PM clock-outs as half day in HR attendance', function () {
    $attendance = new Attendance([
        'date' => '2026-03-02',
        'time_in' => '07:00:00',
        'time_out' => '12:00:00',
        'hours_worked' => 5,
        'status' => Attendance::PRESENT,
    ]);
    $attendance->setRelation('employee', new SchoolEmployee([
        'position' => 'Teacher',
        'role_type' => 'teaching',
        'department' => 'elementary',
    ]));

    $present = new ReflectionMethod(new AttendanceController, 'present');
    $row = $present->invoke(new AttendanceController, $attendance, null);

    expect($row['status'])->toBe(Attendance::HALF_DAY)
        ->and($row['days_worked'])->toBe(0.5);
});

it('recalculates existing maintenance attendance using the 6 AM to 3 PM schedule', function () {
    $attendance = new Attendance([
        'date' => '2026-11-05',
        'time_in' => '07:00:00',
        'time_out' => '15:00:00',
        'hours_worked' => 7,
        'status' => Attendance::UNDERTIME,
    ]);
    $attendance->setRelation('employee', new SchoolEmployee([
        'position' => 'Maintenance Staff',
        'role_type' => 'nonteaching',
        'department' => 'elementary',
    ]));

    $present = new ReflectionMethod(new AttendanceController, 'present');
    $row = $present->invoke(new AttendanceController, $attendance, null);

    expect($row['hours'])->toBe('7.00')
        ->and($row['status'])->toBe(Attendance::LATE);
});
