<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class AttendanceSchedule
{
    /** Roles that follow the maintenance schedule. */
    public const MAINTENANCE_ROLES = ['maintenance'];

    /** Month (1-12) when office/teacher/support/admin schedule is adjusted. */
    public const ADJUSTED_MONTH = 5; // May

    /** Hours worked below this value = Half Day. */
    public const HALF_DAY_HOURS = 4;

<<<<<<< HEAD
    public const HALF_DAY_CLOCK_OUT_START = '12:00';

    public const HALF_DAY_CLOCK_OUT_END = '13:00';

    public const UNDERTIME_START = '14:00';

    public const UNDERTIME_END = '16:30';

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public function __construct(
        public string $start,
        public string $end,
        public string $lunchStart,
        public string $lunchEnd,
        public int $graceMinutes = 0,
    ) {}

<<<<<<< HEAD
    public static function isMaintenanceRole(?string $role): bool
    {
        $normalizedRole = preg_replace('/[^a-z]/', '', strtolower(trim((string) $role)));

        return in_array($normalizedRole, ['maintenance', 'maintenancestaff'], true);
    }

    public static function isMaintenanceEmployee(?string $roleType, ?string $position): bool
    {
        return self::isMaintenanceRole($roleType) || self::isMaintenanceRole($position);
    }

    public static function forEmployee(?string $roleType, ?string $position, Carbon $date): self
    {
        return self::for(
            self::isMaintenanceEmployee($roleType, $position) ? 'maintenance' : $roleType,
            $date
        );
    }

    public static function isWorkingDay(?string $role, Carbon $date): bool
    {
        return $date->dayOfWeek !== Carbon::SUNDAY
            && ($date->dayOfWeek !== Carbon::SATURDAY || self::isMaintenanceRole($role));
    }

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    /**
     * Resolve the schedule based on the employee's role and the date.
     */
    public static function for(?string $role, Carbon $date): self
    {
        $role = strtolower(trim((string) $role));

        // Maintenance: 6:00 AM - 3:00 PM (1 hr lunch = 8 hrs work).
<<<<<<< HEAD
        if (self::isMaintenanceRole($role)) {
=======
        if (in_array($role, self::MAINTENANCE_ROLES, true)) {
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            return new self('06:00', '15:00', '12:00', '13:00', 0);
        }

        // Office personnel, teacher, support staff, admin.
        // May: adjusted to 8:00 AM - 4:00 PM.
        if ($date->month === self::ADJUSTED_MONTH) {
            return new self('08:00', '16:00', '12:00', '13:30', 10);
        }

        // Regular: 7:00 AM - 4:30 PM, 10 mins grace, lunch 12:00 - 1:30 PM.
        return new self('07:00', '16:30', '12:00', '13:30', 10);
    }

    /**
     * Build a Carbon for a given "H:i" time on the same day as $date.
     */
    private function at(string $time, Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($time);
    }

    /**
     * Combine a record's date with its time_in / time_out value.
     * Accepts "07:05:00", "07:05" or a full datetime string.
     */
    public static function toCarbon(Carbon $date, $time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }

        $parsed = Carbon::parse($time);

        return $date->copy()->setTime($parsed->hour, $parsed->minute, $parsed->second);
    }

    /**
     * Late only if time-in is beyond start + grace period.
     * 7:10 -> no deduction. 7:11 -> late (counted from 7:00 = 11 mins).
     */
    public function lateMinutes(?Carbon $timeIn): int
    {
        if (! $timeIn) {
            return 0;
        }

        $start = $this->at($this->start, $timeIn);
        $limit = $start->copy()->addMinutes($this->graceMinutes);

        return $timeIn->greaterThan($limit)
            ? (int) abs($start->diffInMinutes($timeIn))
            : 0;
    }

<<<<<<< HEAD
    public function undertimeMinutes(?Carbon $timeOut): int
    {
        if (! $timeOut) {
            return 0;
        }

        $start = $this->at(self::UNDERTIME_START, $timeOut);
        $end = $this->at($this->end, $timeOut);

        if ($timeOut->lessThan($start) || $timeOut->greaterThanOrEqualTo($end)) {
            return 0;
        }

        return (int) ceil($timeOut->diffInSeconds($end) / 60);
    }

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    /**
     * Hours worked. No overtime: time is capped between the
     * scheduled start and end, and the lunch break is deducted.
     */
    public function hoursWorked(?Carbon $timeIn, ?Carbon $timeOut): float
    {
        if (! $timeIn || ! $timeOut || $timeOut->lessThanOrEqualTo($timeIn)) {
            return 0.0;
        }

        $start = $this->at($this->start, $timeIn);
        $end = $this->at($this->end, $timeIn);

<<<<<<< HEAD
        // Arrivals within the grace period receive the full scheduled hours.
        $graceEnd = $start->copy()->addMinutes($this->graceMinutes);
        $in = $timeIn->lessThanOrEqualTo($graceEnd) ? $start : $timeIn;
        // Early time-in / late time-out are not counted (no overtime).
=======
        // Early time-in / late time-out are not counted (no overtime).
        $in = $timeIn->lessThan($start) ? $start : $timeIn;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $out = $timeOut->greaterThan($end) ? $end : $timeOut;

        if ($out->lessThanOrEqualTo($in)) {
            return 0.0;
        }

        $minutes = abs($in->diffInMinutes($out));

        // Deduct the lunch overlap.
        $lunchStart = $this->at($this->lunchStart, $timeIn);
        $lunchEnd = $this->at($this->lunchEnd, $timeIn);
        $overlapStart = $in->greaterThan($lunchStart) ? $in : $lunchStart;
        $overlapEnd = $out->lessThan($lunchEnd) ? $out : $lunchEnd;

        if ($overlapEnd->greaterThan($overlapStart)) {
            $minutes -= abs($overlapStart->diffInMinutes($overlapEnd));
        }

        return round(max($minutes, 0) / 60, 2);
    }

    /**
     * Present / Late / Half Day / Absent.
     */
    public function status(?Carbon $timeIn, ?Carbon $timeOut): string
    {
        if (! $timeIn) {
            return 'Absent';
        }

<<<<<<< HEAD
        if ($timeOut
            && $timeOut->greaterThanOrEqualTo($this->at(self::HALF_DAY_CLOCK_OUT_START, $timeOut))
            && $timeOut->lessThanOrEqualTo($this->at(self::HALF_DAY_CLOCK_OUT_END, $timeOut))) {
            return 'Half Day';
        }

        if ($timeOut && $this->undertimeMinutes($timeOut) > 0) {
            return 'Undertime';
        }

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        if ($timeOut && $this->hoursWorked($timeIn, $timeOut) < self::HALF_DAY_HOURS) {
            return 'Half Day';
        }

        return $this->lateMinutes($timeIn) > 0 ? 'Late' : 'Present';
    }

    public function label(): string
    {
        return Carbon::createFromTimeString($this->start)->format('g:i A')
            .' - '
            .Carbon::createFromTimeString($this->end)->format('g:i A');
    }

    public function lunchLabel(): string
    {
        return Carbon::createFromTimeString($this->lunchStart)->format('g:i A')
            .' - '
            .Carbon::createFromTimeString($this->lunchEnd)->format('g:i A');
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
