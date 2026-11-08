<?php

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

function createAttendanceArchiveTestEmployee(string $suffix): int
{
    return DB::table('employees')->insertGetId([
        'employee_id' => 'ARCHIVE-'.$suffix,
        'name' => 'Archive Test '.$suffix,
        'position' => 'Teacher',
        'department' => 'elementary',
        'role_type' => 'teaching',
        'email' => 'archive-'.$suffix.'@example.com',
        'birthday' => '1990-01-01',
        'address' => 'Test address',
        'emergency_name' => 'Emergency Contact',
        'emergency_contact' => '09123456789',
        'employment_status' => 'regular',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('archives selected attendance into the archive view without changing delete behavior', function () {
    Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
    $hr = User::factory()->create();
    $hr->assignRole('HR');

    $archivedEmployeeId = createAttendanceArchiveTestEmployee('move');
    $deletedEmployeeId = createAttendanceArchiveTestEmployee('delete');

    $attendanceToArchive = Attendance::create([
        'employee_id' => $archivedEmployeeId,
        'date' => '2026-11-05',
        'status' => Attendance::PRESENT,
        'archived' => false,
    ]);
    $attendanceToDelete = Attendance::create([
        'employee_id' => $deletedEmployeeId,
        'date' => '2026-11-05',
        'status' => Attendance::PRESENT,
        'archived' => false,
    ]);

    $this->actingAs($hr)
        ->patch(route('attendance.archive'), ['ids' => [$attendanceToArchive->id]])
        ->assertRedirect(route('attendance.index', ['view' => 'archive']))
        ->assertSessionHas('success', '1 record(s) archived.');

    expect($attendanceToArchive->fresh()->archived)->toBeTrue();

    $this->actingAs($hr)
        ->delete(route('attendance.destroyMany'), ['ids' => [$attendanceToDelete->id]]);

    expect(Attendance::whereKey($attendanceToDelete->id)->exists())->toBeFalse()
        ->and(Attendance::whereKey($attendanceToArchive->id)->exists())->toBeTrue();
});
