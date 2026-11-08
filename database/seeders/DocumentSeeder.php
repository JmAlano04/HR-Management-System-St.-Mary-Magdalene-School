<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::orderBy('id')->take(4)->get();

        if ($employees->count() < 4) {
            $this->command?->warn('Need at least 4 employees. Run: php artisan db:seed --class=EmployeeSeeder first.');
            return;
        }

        $nbi = SubmissionBox::firstOrCreate(
            ['name' => '2026 NBI Clearance'],
            ['type' => 'NBI', 'deadline' => now()->addDays(14)->toDateString(), 'instructions' => 'Upload a clear PDF copy of your valid NBI clearance.']
        );

        $resume = SubmissionBox::firstOrCreate(
            ['name' => 'Updated Resume / CV'],
            ['type' => 'Resume', 'deadline' => null, 'instructions' => 'Submit your latest resume in PDF format.']
        );

        // [box, employee index, file name, type, size in bytes, status]
        $files = [
            [$nbi,    0, 'NBI_Clearance_2026.pdf', 'NBI',    245760,  'Pending'],
            [$nbi,    1, 'NBI_front.pdf',          'NBI',    318000,  'Approved'],
            [$nbi,    1, 'NBI_back.pdf',           'NBI',    190000,  'Rejected'],
            [$nbi,    2, 'NBI_scan.pdf',           'NBI',    1250000, 'Pending'],
            [$resume, 0, 'Resume_2026.pdf',        'Resume', 402000,  'Approved'],
            [$resume, 3, 'CV_Daniel_Reyes.pdf',    'Resume', 515000,  'Pending'],
        ];

        foreach ($files as [$box, $i, $name, $type, $size, $status]) {
            EmployeeDocument::firstOrCreate(
                ['submission_box_id' => $box->id, 'employee_id' => $employees[$i]->id, 'name' => $name],
                [
                    'type'        => $type,
                    'size_bytes'  => $size,
                    'mime_type'   => 'application/pdf',
                    'status'      => $status,
                    'reviewed_at' => $status !== 'Pending' ? now() : null,
                ]
            );
        }
    }
}