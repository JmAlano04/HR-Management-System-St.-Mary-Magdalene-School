<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        // Gamitin ang mga existing employees; kung kulang, gumawa ng sample
        if (Employee::count() < 5) {
            $samples = [
                ['HR-100001', 'Joshua Aguilera', 'Teacher',         'elementary', 'teaching'],
                ['HR-100002', 'Maria Santos',    'Teacher',         'highschool', 'teaching'],
                ['HR-100003', 'Pedro Dela Cruz', 'Registrar Staff', 'seniorhigh', 'nonteaching'],
                ['HR-100004', 'Ana Reyes',       'Teacher',         'elementary', 'teaching'],
                ['HR-100005', 'Mark Villanueva', 'Teacher',         'seniorhigh', 'teaching'],
            ];

            foreach ($samples as [$code, $name, $position, $department, $roleType]) {
                Employee::firstOrCreate(
                    ['employee_id' => $code],
                    [
                        'name'              => $name,
                        'position'          => $position,
                        'department'        => $department,
                        'role_type'         => $roleType,
                        'email'             => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                        'birthday'          => now()->subYears(30)->toDateString(),
                        'address'           => 'General Trias, Cavite',
                        'emergency_name'    => 'Emergency Contact',
                        'emergency_contact' => '0917 000 0000',
                    ]
                );
            }
        }

        $employees = Employee::take(5)->get();

        // [status, time in, time out, hours]
        $patterns = [
            ['Present',  '08:00', '17:00', 8.0],
            ['Present',  '07:55', '17:05', 8.2],
            ['Late',     '08:35', '17:10', 7.6],
            ['Half Day', '08:00', '12:00', 4.0],
            ['Absent',   null,    null,    0.0],
        ];

        // Current records: huling 6 araw (hindi Linggo)
        // Archived records: mga petsa noong nakaraang buwan
        $dates = [];
        for ($d = 0; $d < 6; $d++) {
            $dates[] = [now()->subDays($d), false];
        }
        foreach ([40, 41, 42] as $d) {
            $dates[] = [now()->subDays($d), true];
        }

        foreach ($dates as $dIndex => [$date, $archived]) {
            if ($date->isSunday()) {
                continue;
            }

            foreach ($employees as $eIndex => $employee) {
                [$status, $in, $out, $hours] = $patterns[($dIndex + $eIndex) % count($patterns)];

                AttendanceRecord::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'time_in'      => $in,
                        'time_out'     => $out,
                        'hours_worked' => $hours,
                        'status'       => $status,
                        'archived'     => $archived,
                    ]
                );
            }
        }
    }
}