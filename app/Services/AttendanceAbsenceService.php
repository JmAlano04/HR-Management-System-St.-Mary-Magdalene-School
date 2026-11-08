<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\SchoolEmployee;
use App\Support\AttendanceSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceAbsenceService
{
    private const ABSENCE_CUTOFF = '16:30';

    public function markUnpunchedEmployeesAbsent(Carbon $now): int
    {
        if ($now->lt($now->copy()->setTimeFromTimeString(self::ABSENCE_CUTOFF))) {
            return 0;
        }

        $date = $now->toDateString();
        $cacheKey = "attendance:absences-marked:{$date}";

        if (Cache::has($cacheKey)) {
            return 0;
        }

        $rows = [];

        if (! NonWorkingDay::whereDate('date', $date)->exists()) {
            $existingEmployeeIds = Attendance::query()
                ->whereDate('date', $date)
                ->pluck('employee_id')
                ->flip();

            foreach (SchoolEmployee::excludeInternalStaff()->get(['id', 'role_type', 'position']) as $employee) {
                $role = AttendanceSchedule::isMaintenanceEmployee($employee->role_type, $employee->position)
                    ? 'maintenance'
                    : $employee->role_type;

                if (
                    ! AttendanceSchedule::isWorkingDay($role, $now)
                    || $existingEmployeeIds->has($employee->id)
                ) {
                    continue;
                }

                $rows[] = [
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'time_in' => null,
                    'time_out' => null,
                    'hours_worked' => null,
                    'status' => Attendance::ABSENT,
                    'archived' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $created = $rows ? Attendance::query()->insertOrIgnore($rows) : 0;

        Cache::put($cacheKey, true, $now->copy()->endOfDay());

        return $created;
    }
}
