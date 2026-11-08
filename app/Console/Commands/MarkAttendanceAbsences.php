<?php

namespace App\Console\Commands;

use App\Services\AttendanceAbsenceService;
use Illuminate\Console\Command;

class MarkAttendanceAbsences extends Command
{
    protected $signature = 'hrms:mark-attendance-absences';

    protected $description = 'Mark employees without biometric attendance absent after the daily cutoff';

    public function handle(AttendanceAbsenceService $service): int
    {
        $created = $service->markUnpunchedEmployeesAbsent(now());

        if ($created > 0) {
            $this->info("Employees marked absent: {$created}");
        }

        return self::SUCCESS;
    }
}
