<?php

namespace App\Listeners;

use App\Services\ZKTecoAttendanceService;
use HafizAsifAli\ZkTeco\Laravel\Events\AttendanceSynced;
use RuntimeException;

class SyncZKTecoAttendanceRecords
{
    public function __construct(private readonly ZKTecoAttendanceService $service) {}

    public function handle(AttendanceSynced $event): void
    {
        $stats = $this->service->syncToAttendanceRecords();

        if ($stats['failed'] > 0) {
            throw new RuntimeException(
                "Failed to sync {$stats['failed']} ZKTeco punch(es) to attendance records."
            );
        }
    }
}
