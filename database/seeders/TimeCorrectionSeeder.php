<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\TimeCorrection;
use Illuminate\Database\Seeder;

class TimeCorrectionSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::orderBy('id')->take(4)->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('No employees found. Run: php artisan db:seed --class=EmployeeSeeder first.');
            return;
        }

        $samples = [
            ['days' => 1, 'type' => 'Time In',  'wrong' => '08:42:00', 'right' => '07:55:00', 'note' => 'Forgot to log in because the logbook was at the other gate.', 'admin' => 'Pending',  'hr' => 'Pending'],
            ['days' => 2, 'type' => 'Time Out', 'wrong' => null,       'right' => '17:05:00', 'note' => 'Missed the time-out entry after the faculty meeting.',        'admin' => 'Pending',  'hr' => 'Approved'],
            ['days' => 5, 'type' => 'Time In',  'wrong' => '09:10:00', 'right' => '08:00:00', 'note' => 'Wrote the wrong time in the logbook.',                         'admin' => 'Approved', 'hr' => 'Approved'],
            ['days' => 7, 'type' => 'Time Out', 'wrong' => '15:30:00', 'right' => '17:00:00', 'note' => 'Logged out early by mistake, stayed until 5:00 PM.',            'admin' => 'Rejected', 'hr' => 'Pending'],
        ];

        foreach ($samples as $i => $s) {
            TimeCorrection::create([
                'employee_id'       => $employees[$i % $employees->count()]->id,
                'correction_date'   => now()->subDays($s['days'])->toDateString(),
                'correction_type'   => $s['type'],
                'incorrect_time'    => $s['wrong'],
                'correct_time'      => $s['right'],
                'note'              => $s['note'],
                'admin_status'      => $s['admin'],
                'admin_reviewed_at' => $s['admin'] !== 'Pending' ? now() : null,
                'hr_status'         => $s['hr'],
                'hr_reviewed_at'    => $s['hr'] !== 'Pending' ? now() : null,
            ]);
        }
    }
}