<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\SchoolEmployee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Sample data for testing: the last 60 days (weekdays only) for every employee. */
class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $patterns = [
            'Present'  => ['07:50:00', '17:05:00', 8.00],
            'Late'     => ['08:35:00', '17:00:00', 7.50],
            'Half Day' => ['08:00:00', '12:00:00', 4.00],
            'Absent'   => [null, null, null],
        ];

        foreach (SchoolEmployee::all() as $employee) {
            for ($i = 0; $i < 60; $i++) {
                $date = Carbon::today()->subDays($i);
                if ($date->isWeekend()) {
                    continue;
                }

                $roll   = mt_rand(1, 100);
                $status = $roll <= 78 ? 'Present' : ($roll <= 88 ? 'Late' : ($roll <= 94 ? 'Half Day' : 'Absent'));
                [$in, $out, $hours] = $patterns[$status];

                AttendanceRecord::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    ['time_in' => $in, 'time_out' => $out, 'hours_worked' => $hours, 'status' => $status, 'archived' => false]
                );
            }
        }
    }
}