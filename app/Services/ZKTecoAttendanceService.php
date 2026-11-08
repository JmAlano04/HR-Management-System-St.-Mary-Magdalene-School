<?php

namespace App\Services;

<<<<<<< HEAD
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Support\AttendanceSchedule;
=======
use App\Models\AttendanceRecord;
use App\Models\Employee;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ZKTecoAttendanceService
{
    private const USER_COLUMNS = ['user_id', 'device_user_id', 'pin', 'uid'];
    private const TIME_COLUMNS = ['punched_at', 'timestamp', 'punch_time', 'recorded_at', 'datetime'];

    public function syncToAttendanceRecords(): array
    {
        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        $columns = Schema::getColumnListing('zkteco_attendances');

        $userCol = $this->firstExisting($columns, self::USER_COLUMNS);
        $timeCol = $this->firstExisting($columns, self::TIME_COLUMNS);

        if (! $userCol || ! $timeCol) {
            throw new RuntimeException(
                'Cannot find user/time columns in zkteco_attendances. Columns found: ' . implode(', ', $columns)
            );
        }

        $hasZkColumn = Schema::hasColumn('employees', 'zkteco_user_id');
        $employeeCache = [];

        // ===== INCREMENTAL START =====
        $lastId = (int) Cache::get('zkteco:last_synced_id', 0);
        $maxId = $lastId;

        DB::table('zkteco_attendances')
            ->where('id', '>', $lastId)
            ->chunkById(500, function ($punches) use (
                &$stats, &$employeeCache, &$maxId, $userCol, $timeCol, $hasZkColumn, $columns
            ) {
                foreach ($punches as $punch) {
                    $maxId = max($maxId, $punch->id);
                    $stats['processed']++;

                    try {
                        $deviceUserId = trim((string) $punch->{$userCol});

                        if (! array_key_exists($deviceUserId, $employeeCache)) {
                            $employeeCache[$deviceUserId] = ($hasZkColumn
                                    ? Employee::where('zkteco_user_id', $deviceUserId)->first()
                                    : null)
                                ?? Employee::where('employee_id', $deviceUserId)->first();
                        }

                        $employee = $employeeCache[$deviceUserId];

                        if (! $employee) {
                            $stats['skipped']++;
                            Log::warning('ZKTeco employee not mapped', [
                                'device_user_id' => $deviceUserId,
                                'punched_at'     => $punch->{$timeCol},
                            ]);
                            continue;
                        }

                        $timezone = in_array('timezone', $columns, true) && $punch->timezone
                            ? $punch->timezone
                            : config('app.timezone', 'Asia/Manila');

                        $punchedAt = Carbon::parse($punch->{$timeCol}, $timezone);
                        $date = $punchedAt->toDateString();
                        $time = $punchedAt->format('H:i:s');

                        $attendance = AttendanceRecord::where('employee_id', $employee->id)
                            ->whereDate('date', $date)
                            ->first();

                        $wasExisting = (bool) $attendance;
<<<<<<< HEAD
                        $isNonWorkingDay = $attendance && in_array($attendance->status, [
                            Attendance::PAID_HOLIDAY,
                            Attendance::NON_WORKING_DAY,
                            Attendance::NWD_REVIEW,
                        ], true);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

                        if (! $attendance) {
                            $attendance = new AttendanceRecord();
                            $attendance->forceFill([
                                'employee_id' => $employee->id,
                                'date'        => $date,
                            ]);
                        }

                        $currentIn  = $this->normalizeTime($attendance->time_in);
                        $currentOut = $this->normalizeTime($attendance->time_out);

                        $direction = strtolower(str_replace(
                            [' ', '_', '-'], '',
                            (string) ($punch->direction ?? $punch->state ?? $punch->type ?? '')
                        ));

                        $entry = ['checkin', 'breakin', 'overtimein', '0', '3', '4'];
                        $exit  = ['checkout', 'breakout', 'overtimeout', '1', '2', '5'];

                        if (in_array($direction, $entry, true)) {
                            if (! $currentIn || $time < $currentIn) {
                                $currentIn = $time;
                            }
                        } elseif (in_array($direction, $exit, true)) {
                            if (! $currentOut || $time > $currentOut) {
                                $currentOut = $time;
                            }
                        } else {
                            if (! $currentIn || $time < $currentIn) {
                                $currentIn = $time;
                            }
                            if ($time > $currentIn && (! $currentOut || $time > $currentOut)) {
                                $currentOut = $time;
                            }
                        }

                        $attendance->time_in  = $currentIn;
                        $attendance->time_out = $currentOut;

<<<<<<< HEAD
                        $dateStart = Carbon::parse($date)->startOfDay();
                        $schedule = AttendanceSchedule::forEmployee(
                            $employee->role_type,
                            $employee->position,
                            $dateStart
                        );
                        $timeIn = AttendanceSchedule::toCarbon($dateStart, $currentIn);
                        $timeOut = AttendanceSchedule::toCarbon($dateStart, $currentOut);

                        $attendance->hours_worked = $timeIn && $timeOut
                            ? $schedule->hoursWorked($timeIn, $timeOut)
                            : null;
                        $attendance->status = $isNonWorkingDay
                            ? Attendance::NWD_REVIEW
                            : $schedule->status($timeIn, $timeOut);
=======
                        if ($currentIn && $currentOut) {
                            $minutes = Carbon::parse("$date $currentIn")
                                ->diffInMinutes(Carbon::parse("$date $currentOut"), true);

                            $workedMinutes = $minutes > 300 ? $minutes - 60 : $minutes;
                            $attendance->hours_worked = round(max(0, $workedMinutes) / 60, 2);
                        }

                        $attendance->status = $this->determineStatus(
                            $currentIn,
                            $currentOut,
                            $attendance->hours_worked !== null ? (float) $attendance->hours_worked : null
                        );
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

                        $attendance->archived = $attendance->archived ?? false;
                        $attendance->save();

                        $wasExisting ? $stats['updated']++ : $stats['created']++;
                    } catch (Throwable $e) {
                        $stats['failed']++;
                        Log::error('ZKTeco punch sync failed', [
                            'punch'   => (array) $punch,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Cache::forever('zkteco:last_synced_id', $maxId);
        // ===== INCREMENTAL END =====

        return $stats;
    }

<<<<<<< HEAD
=======
    private function determineStatus(?string $timeIn, ?string $timeOut, ?float $hoursWorked): string
    {
        if (! $timeIn) {
            return 'Absent';
        }

        if ($timeIn > '08:00:00') {
            return 'Late';
        }

        if (! $timeOut) {
            return 'Present';
        }

        if ($hoursWorked !== null && $hoursWorked < 4) {
            return 'Half Day';
        }

        return 'Present';
    }

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    private function normalizeTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('H:i:s');
    }

    private function firstExisting(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}