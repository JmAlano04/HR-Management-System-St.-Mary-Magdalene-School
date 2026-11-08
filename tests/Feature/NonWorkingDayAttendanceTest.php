<?php

use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);

    $this->hr = User::factory()->create();
    $this->hr->assignRole('HR');
});

function createNonWorkingDayTestEmployee(string $suffix): int
{
    return DB::table('employees')->insertGetId([
        'employee_id' => 'NWD-'.$suffix,
        'name' => 'Employee '.$suffix,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'nwd-'.$suffix.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('saving an unpaid non-working day logs every employee and flags existing work logs for review', function () {
    $employeeWithoutAttendance = createNonWorkingDayTestEmployee('one');
    $employeeWithAttendance = createNonWorkingDayTestEmployee('two');
    $date = '2026-12-25';

    $loggedAttendance = Attendance::create([
        'employee_id' => $employeeWithAttendance,
        'date' => $date,
        'time_in' => '08:00',
        'time_out' => '17:00',
        'hours_worked' => 8,
        'status' => Attendance::PRESENT,
        'archived' => false,
    ]);

    $this->actingAs($this->hr)
        ->post(route('hr.non-working-days.store'), [
            'date' => $date,
            'name' => 'Christmas Day',
            'category' => 'National Holiday',
            'pay_type' => NonWorkingDay::UNPAID,
        ])
        ->assertSessionHas('success');

    expect(Attendance::whereDate('date', $date)->count())->toBe(2)
        ->and(Attendance::where('employee_id', $employeeWithoutAttendance)->whereDate('date', $date)->value('status'))
        ->toBe(Attendance::NON_WORKING_DAY)
        ->and($loggedAttendance->fresh()->status)->toBe(Attendance::NWD_REVIEW)
        ->and(substr($loggedAttendance->fresh()->time_in, 0, 5))->toBe('08:00');
});

test('saving a paid holiday logs employees with the paid holiday status', function () {
    $employee = createNonWorkingDayTestEmployee('paid');
    $date = '2026-12-26';

    $this->actingAs($this->hr)
        ->post(route('hr.non-working-days.store'), [
            'date' => $date,
            'name' => 'Declared Paid Holiday',
            'category' => 'Local Holiday',
            'pay_type' => NonWorkingDay::PAID,
            'paid_days' => 1,
            'paid_hours' => 8,
        ])
        ->assertSessionHas('success');

    expect(Attendance::where('employee_id', $employee)->whereDate('date', $date)->value('status'))
        ->toBe(Attendance::PAID_HOLIDAY);
});

test('removing a non-working day deletes its attendance entries and database record', function () {
    $employeeWithoutAttendance = createNonWorkingDayTestEmployee('remove-one');
    $employeeWithAttendance = createNonWorkingDayTestEmployee('remove-two');
    $date = '2026-12-27';
    $loggedAttendance = Attendance::create([
        'employee_id' => $employeeWithAttendance,
        'date' => $date,
        'time_in' => '08:00',
        'time_out' => '17:00',
        'hours_worked' => 8,
        'status' => Attendance::PRESENT,
        'archived' => false,
    ]);

    $this->actingAs($this->hr)
        ->post(route('hr.non-working-days.store'), [
            'date' => $date,
            'name' => 'Removal Test Holiday',
            'category' => 'National Holiday',
            'pay_type' => NonWorkingDay::UNPAID,
        ]);

    $nonWorkingDay = NonWorkingDay::whereDate('date', $date)->firstOrFail();

    $this->actingAs($this->hr)
        ->delete(route('non-working-days.destroy', $nonWorkingDay))
        ->assertSessionHas('success', 'Non-working day removed.');

    expect(NonWorkingDay::whereKey($nonWorkingDay->id)->exists())->toBeFalse()
        ->and(Attendance::where('employee_id', $employeeWithoutAttendance)->whereDate('date', $date)->exists())->toBeFalse()
        ->and($loggedAttendance->fresh()->status)->toBe(Attendance::NWD_REVIEW)
        ->and(substr($loggedAttendance->fresh()->time_in, 0, 5))->toBe('08:00');
});

test('future non-working day attendance appears only when its date arrives', function () {
    Carbon::setTestNow('2026-11-05 12:00:00');
    $employee = createNonWorkingDayTestEmployee('future');

    $this->actingAs($this->hr)
        ->post(route('hr.non-working-days.store'), [
            'date' => '2026-11-12',
            'name' => 'Future Holiday',
            'category' => 'National Holiday',
            'pay_type' => NonWorkingDay::UNPAID,
        ]);

    $this->get(route('attendance.index'))
        ->assertViewHas('records', fn ($records) => $records->total() === 0);

    Carbon::setTestNow('2026-11-12 00:00:00');

    $this->get(route('attendance.index'))
        ->assertViewHas('records', fn ($records) => $records->getCollection()->contains('employee_code', 'NWD-future'));

    Carbon::setTestNow();
});
