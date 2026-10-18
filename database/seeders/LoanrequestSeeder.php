<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LoanRequest;
use Illuminate\Database\Seeder;

class LoanRequestSeeder extends Seeder
{
    public function run(): void
    {
        // Gamitin ang mga existing employees; kung wala pa, gumawa ng ilang sample
        if (Employee::count() < 4) {
            $samples = [
                ['HR-100001', 'Joshua Aguilera',  'Teacher',           'elementary', 'teaching'],
                ['HR-100002', 'Maria Santos',     'Teacher',           'highschool', 'teaching'],
                ['HR-100003', 'Pedro Dela Cruz',  'Registrar Staff',   'seniorhigh', 'nonteaching'],
                ['HR-100004', 'Ana Reyes',        'Teacher',           'elementary', 'teaching'],
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

        $employees = Employee::take(4)->get();

        $rows = [
            // [loan type, amount, reason, hr, accounting, admin, rejection (stage => reason), days ago]
            ['Salary Loan',    15000, 'Tuition fee for my child.',                'approved', 'approved', 'pending',  [], 1],
            ['Emergency Loan', 8000,  'Hospital bills for a family member.',      'approved', 'approved', 'pending',  [], 2],
            ['Salary Loan',    20000, 'Home repair after the typhoon.',           'approved', 'pending',  'pending',  [], 3],
            ['Emergency Loan', 5000,  'Urgent travel expenses.',                  'pending',  'pending',  'pending',  [], 4],
            ['Salary Loan',    30000, 'Debt consolidation.',                      'rejected', 'pending',  'pending',  ['hr_rejection_reason' => 'Existing unpaid loan balance.'], 6],
            ['Salary Loan',    12000, 'School supplies and uniforms.',            'approved', 'approved', 'approved', [], 8],
            ['Emergency Loan', 25000, 'Down payment for a motorcycle.',           'approved', 'approved', 'rejected', ['admin_rejection_reason' => 'Purpose does not qualify as an emergency.'], 10],
        ];

        foreach ($rows as $i => [$type, $amount, $reason, $hr, $accounting, $admin, $rejections, $daysAgo]) {
            $employee = $employees[$i % $employees->count()];

            LoanRequest::create(array_merge([
                'employee_id'       => $employee->id,
                'loan_type'         => $type,
                'amount'            => $amount,
                'reason'            => $reason,
                'requested_at'      => now()->subDays($daysAgo),
                'hr_status'         => $hr,
                'accounting_status' => $accounting,
                'admin_status'      => $admin,
                'admin_reviewed_at' => $admin === 'pending' ? null : now()->subDays(max($daysAgo - 1, 0)),
            ], $rejections));
        }
    }
}