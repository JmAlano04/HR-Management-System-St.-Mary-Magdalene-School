<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Database\Seeder;

class PayslipSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::orderBy('id')->take(4)->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('No employees found. Run: php artisan db:seed --class=EmployeeSeeder first.');
            return;
        }

        $plans = [
            ['daily' => 1200, 'days' => 11, 'hours' => 88, 'late' => 0,  'hon' => 1500, 'step' => 'Step 3', 'late_d' => 0,     'abs' => 0,    'sss' => 900, 'sss_loan' => 0,   'ph' => 375, 'peraa' => 250, 'peraa_loan' => 0, 'hdmf' => 100, 'hdmf_loan' => 0, 'cash' => 0,    'admin' => 'Pending',  'hr' => 'Pending',  'reason' => null,                                                   'released' => false],
            ['daily' => 950,  'days' => 11, 'hours' => 87, 'late' => 15, 'hon' => 1000, 'step' => 'Step 2', 'late_d' => 29.69, 'abs' => 0,    'sss' => 700, 'sss_loan' => 500, 'ph' => 300, 'peraa' => 200, 'peraa_loan' => 0, 'hdmf' => 100, 'hdmf_loan' => 0, 'cash' => 1000, 'admin' => 'Approved', 'hr' => 'Approved', 'reason' => null,                                                   'released' => true],
            ['daily' => 1050, 'days' => 10, 'hours' => 80, 'late' => 0,  'hon' => 800,  'step' => 'Step 1', 'late_d' => 0,     'abs' => 1050, 'sss' => 800, 'sss_loan' => 0,   'ph' => 330, 'peraa' => 220, 'peraa_loan' => 0, 'hdmf' => 100, 'hdmf_loan' => 0, 'cash' => 0,    'admin' => 'Rejected', 'hr' => 'Pending',  'reason' => 'Please verify the absence deduction and recompute the net pay.', 'released' => false],
            ['daily' => 1300, 'days' => 11, 'hours' => 86, 'late' => 30, 'hon' => 2000, 'step' => 'Step 4', 'late_d' => 81.25, 'abs' => 0,    'sss' => 950, 'sss_loan' => 0,   'ph' => 400, 'peraa' => 260, 'peraa_loan' => 0, 'hdmf' => 100, 'hdmf_loan' => 0, 'cash' => 0,    'admin' => 'Pending',  'hr' => 'Approved', 'reason' => null,                                                   'released' => false],
        ];

        foreach ($plans as $i => $p) {
            $employee = $employees[$i % $employees->count()];

            $basic      = round($p['daily'] * $p['days'], 2);
            $gross      = $basic + $p['hon'];
            $deductions = 50 + $p['late_d'] + $p['abs'] + $p['sss'] + $p['sss_loan'] + $p['ph']
                        + $p['peraa'] + $p['peraa_loan'] + $p['hdmf'] + $p['hdmf_loan'] + $p['cash'];

            Payslip::updateOrCreate(
                ['employee_id' => $employee->id, 'transfer_date' => now()->toDateString(), 'cutoff' => '15th'],
                [
                    'step_name'              => $p['step'],
                    'days_worked'            => $p['days'],
                    'hours_worked'           => $p['hours'],
                    'late_minutes'           => $p['late'],
                    'basic_pay'              => $basic,
                    'daily_wage'             => $p['daily'],
                    'honorarium'             => $p['hon'],
                    'gross_pay'              => $gross,
                    'malasakitan'            => 50,
                    'late_deduction'         => $p['late_d'],
                    'absences_deduction'     => $p['abs'],
                    'sss'                    => $p['sss'],
                    'sss_loan'               => $p['sss_loan'],
                    'philhealth'             => $p['ph'],
                    'peraa_ceap'             => $p['peraa'],
                    'peraa_ceap_loan'        => $p['peraa_loan'],
                    'hdmf'                   => $p['hdmf'],
                    'hdmf_loan'              => $p['hdmf_loan'],
                    'cash_advance'           => $p['cash'],
                    'total_deductions'       => $deductions,
                    'net_pay'                => $gross - $deductions,
                    'sent_to_admin_at'       => now(),
                    'admin_status'           => $p['admin'],
                    'admin_rejection_reason' => $p['reason'],
                    'admin_reviewed_at'      => $p['admin'] !== 'Pending' ? now() : null,
                    'hr_status'              => $p['hr'],
                    'hr_reviewed_at'         => $p['hr'] !== 'Pending' ? now() : null,
                    'released_at'            => $p['released'] ? now() : null,
                ]
            );
        }
    }
}