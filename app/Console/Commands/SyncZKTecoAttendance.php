<?php

namespace App\Console\Commands;

use App\Services\ZKTecoAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SyncZKTecoAttendance extends Command
{
    protected $signature = 'hrms:zkteco-sync';

    protected $description = 'Sync ZKTeco attendance into HRMS';

    public function handle(ZKTecoAttendanceService $service): int
    {
        $this->info('Connecting to ZKTeco X628-C...');

        try {
            // Get attendance from the biometric device
            $exitCode = Artisan::call('zkteco:sync');

            $output = Artisan::output();

            if ($output) {
                $this->line($output);
            }

            if ($exitCode !== 0) {
                $this->error('ZKTeco synchronization failed.');

                return self::FAILURE;
            }

            $this->info('ZKTeco records downloaded successfully.');

            // Move ZKTeco records into HRMS attendance_records
            $stats = $service->syncToAttendanceRecords();

            $this->newLine();

            $this->info('HRMS attendance synchronization complete.');

            $this->table(
                [
                    'Processed',
                    'Created',
                    'Updated',
                    'Skipped',
                ],
                [[
                    $stats['processed'],
                    $stats['created'],
                    $stats['updated'],
                    $stats['skipped'],
                ]]
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->error('Synchronization failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}