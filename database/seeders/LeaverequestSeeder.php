<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'employee_name' => 'Joshua Aguilera',
                'employee_code' => 'EMP-234482',
                'department' => 'Elementary',
                'type' => 'Sick Leave',
                'requested_date' => '2026-10-15',
                'start_date' => '2026-10-20',
                'end_date' => '2026-10-21',
                'reason' => 'Flu and fever, advised rest by doctor.',
                'leave_balance' => '8 days remaining',
                'status' => 'pending',
            ],
            [
                'employee_name' => 'Maria Santos',
                'employee_code' => 'EMP-234501',
                'department' => 'High School',
                'type' => 'Vacation Leave',
                'requested_date' => '2026-10-12',
                'start_date' => '2026-10-27',
                'end_date' => '2026-10-30',
                'reason' => 'Family trip.',
                'leave_balance' => '10 days remaining',
                'status' => 'approved',
            ],
            [
                'employee_name' => 'Pedro Dela Cruz',
                'employee_code' => 'EMP-234377',
                'department' => 'Junior High',
                'type' => 'Emergency Leave',
                'requested_date' => '2026-10-10',
                'start_date' => '2026-10-11',
                'end_date' => '2026-10-11',
                'reason' => 'Family emergency.',
                'leave_balance' => '3 days remaining',
                'status' => 'rejected',
            ],
        ];

        foreach ($rows as $row) {
            LeaveRequest::updateOrCreate(
                [
                    'employee_code' => $row['employee_code'],
                    'requested_date' => $row['requested_date'],
                    'start_date' => $row['start_date'],
                ],
                $row
            );
        }

        $this->command?->info('Leave request records seeded successfully.');
    }
}
