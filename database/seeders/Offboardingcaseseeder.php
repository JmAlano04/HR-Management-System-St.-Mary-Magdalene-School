<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\OffboardingCase;
use App\Models\OffboardingDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class OffboardingCaseSeeder extends Seeder
{
    public function run(): void
    {
        // Gamitin ang mga existing employees; kung wala pa, gumawa ng ilang sample
        if (Employee::count() < 4) {
            $samples = [
                ['HR-100001', 'Joshua Aguilera', 'Teacher',         'elementary', 'teaching'],
                ['HR-100002', 'Maria Santos',    'Teacher',         'highschool', 'teaching'],
                ['HR-100003', 'Pedro Dela Cruz', 'Registrar Staff', 'seniorhigh', 'nonteaching'],
                ['HR-100004', 'Ana Reyes',       'Teacher',         'elementary', 'teaching'],
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
        $disk = Storage::disk(OffboardingCase::DOCUMENT_DISK);

        $rows = [
            // [reason, last working day offset, message, final approved?, documents [label => review status]]
            ['Resignation',          14, 'Thank you for the opportunity.',
                false, ['Resignation Letter' => 'Approved', 'Clearance Form' => 'Approved', 'Exit Interview' => 'Approved']],
            ['End of contract',      30, 'Contract ends this semester.',
                false, ['Clearance Form' => 'Approved', 'Turnover Report' => 'Under review']],
            ['Retirement',           -3, 'Happy to have served the school.',
                true,  ['Retirement Letter' => 'Approved', 'Clearance Form' => 'Approved']],
            ['Personal reasons',      7, null,
                false, ['Resignation Letter' => 'Approved']],
        ];

        foreach ($rows as $i => [$reason, $offset, $message, $finalApproved, $documents]) {
            $employee = $employees[$i % $employees->count()];

            $case = OffboardingCase::create([
                'employee_id'          => $employee->id,
                'reason'               => $reason,
                'last_working_day'     => now()->addDays($offset)->toDateString(),
                'employee_message'     => $message,
                'status'               => $finalApproved ? 'Closed' : 'Open',
                'progress'             => $finalApproved ? 100 : 60,
                'sent_to_admin'        => true,
                'sent_to_admin_at'     => now()->subDays(4 - $i),
                'admin_final_approved' => $finalApproved,
                'admin_approved_at'    => $finalApproved ? now()->subDay() : null,
            ]);

            foreach ($documents as $label => $reviewStatus) {
                $fileName = str($label)->slug('_') . '.txt';
                $path = "offboarding/seed/{$case->id}-{$fileName}";
                $disk->put($path, "Sample {$label} of {$employee->name}.");

                OffboardingDocument::create([
                    'offboarding_case_id' => $case->id,
                    'document_label'      => $label,
                    'file_name'           => $fileName,
                    'file_path'           => $path,
                    'review_status'       => $reviewStatus,
                    'reviewed_at'         => $reviewStatus === 'Approved' ? now()->subDays(2) : null,
                ]);
            }
        }
    }
}