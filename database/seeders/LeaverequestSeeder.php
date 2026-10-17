<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use Illuminate\Database\Seeder;

class LeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['Joshua Aguilera', 'EMP-234482', 'Elementary', 'Sick Leave',     '2026-10-15', '2026-10-20', '2026-10-21', 'Flu and fever, advised rest by doctor.', '8 days remaining', 'pending'],
            ['Maria Santos',    'EMP-234501', 'High School', 'Vacation Leave', '2026-10-12', '2026-10-27', '2026-10-30', 'Family trip.',                         '10 days remaining', 'approved'],
            ['Pedro Dela Cruz', 'EMP-234377', 'Junior High', 'Emergency Leave','2026-10-10', '2026-10-11', '2026-10-11', 'Family emergency.',                    '3 days remaining', 'rejected'],
        ];

        foreach ($rows as [$name, $code, $dept, $type, $requested, $start, $end, $reason, $balance, $status]) {
            LeaveRequest::create([
                'employee_name'  => $name,
                'employee_code'  => $code,
                'department'     => $dept,
                'type'           => $type,
                'requested_date' => $requested,
                'start_date'     => $start,
                'end_date'       => $end,
                'reason'         => $reason,
                'leave_balance'  => $balance,
                'status'         => $status,
            ]);
        }
    }
}