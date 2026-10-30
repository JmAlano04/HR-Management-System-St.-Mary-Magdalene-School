<?php

use App\Models\AttendanceRecord;
use App\Services\ZKTecoAttendanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('employee_id');
        $table->string('zkteco_user_id')->nullable();
    });

    Schema::create('attendance_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employee_id');
        $table->date('date');
        $table->time('time_in')->nullable();
        $table->time('time_out')->nullable();
        $table->decimal('hours_worked', 5, 2)->nullable();
        $table->string('status');
        $table->boolean('archived')->default(false);
        $table->timestamps();
        $table->unique(['employee_id', 'date']);
    });

    Schema::create('zkteco_attendances', function (Blueprint $table) {
        $table->id();
        $table->string('user_id');
        $table->dateTime('punched_at');
        $table->string('timezone')->nullable();
        $table->string('direction');
    });
});

it('matches device users by employee id and pairs punches without a reported direction', function () {
    $employeeId = DB::table('employees')->insertGetId([
        'employee_id' => 'EMP-001',
        'zkteco_user_id' => null,
    ]);

    DB::table('zkteco_attendances')->insert([
        [
            'user_id' => 'EMP-001',
            'punched_at' => '2026-10-28 08:00:00',
            'timezone' => 'Asia/Manila',
            'direction' => 'Unspecified',
        ],
        [
            'user_id' => 'EMP-001',
            'punched_at' => '2026-10-28 17:00:00',
            'timezone' => 'Asia/Manila',
            'direction' => 'Unspecified',
        ],
    ]);

    $stats = app(ZKTecoAttendanceService::class)->syncToAttendanceRecords();
    $attendance = AttendanceRecord::where('employee_id', $employeeId)->firstOrFail();

    expect($stats)->toMatchArray([
        'processed' => 2,
        'created' => 1,
        'updated' => 1,
        'skipped' => 0,
    ])
        ->and($attendance->time_in)->toBe('08:00:00')
        ->and($attendance->time_out)->toBe('17:00:00')
        ->and((float) $attendance->hours_worked)->toBe(8.0)
        ->and($attendance->status)->toBe('Present');
});
