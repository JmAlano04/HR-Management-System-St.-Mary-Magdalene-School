<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\TimeCorrection;
<<<<<<< HEAD
use App\Support\AttendanceSchedule;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * app/Services/ApplyTimeCorrection.php
 *
 * Writes an approved correction into `attendance_records`.
 * HR/Admin attendance management and the Employee attendance page read this
 * same table, so one update changes all three views.
 */
class ApplyTimeCorrection
{
<<<<<<< HEAD
=======
    // ---- Schedule rules (inferred from your seed data; change to match your policy) ----
    private const WORK_START      = '08:00:00'; // time-in after this (+ grace) = Late
    private const GRACE_MINUTES   = 0;
    private const LUNCH_HOURS     = 1.0;        // deducted when the shift spans 12:00-13:00
    private const MAX_HOURS       = 8.0;        // regular daily hours (overtime not counted)
    private const HALF_DAY_BELOW  = 6.0;        // fewer hours than this = Half Day

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public function handle(TimeCorrection $correction): void
    {
        $field = match ($correction->correction_type) {
            'Time In'  => 'time_in',
            'Time Out' => 'time_out',
            default    => throw new InvalidArgumentException(
                "Unsupported correction type: {$correction->correction_type}"
            ),
        };

        // Unique key is (employee_id, date), so this updates the existing row or creates one
        $record = AttendanceRecord::firstOrNew([
            'employee_id' => $correction->employee_id,
            'date'        => Carbon::parse($correction->correction_date)->toDateString(),
        ]);

        $record->{$field} = $correction->correct_time;
<<<<<<< HEAD
        $record->archived = false;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        $this->recompute($record);

        $record->save();

        $correction->update(['applied_at' => now()]);
    }

    /** Recalculate hours_worked and status after a time change. */
    private function recompute(AttendanceRecord $record): void
    {
<<<<<<< HEAD
        $date = Carbon::parse($record->date)->startOfDay();
        $schedule = AttendanceSchedule::forEmployee(
            $record->employee?->role_type,
            $record->employee?->position,
            $date
        );
        $in = AttendanceSchedule::toCarbon($date, $record->time_in);
        $out = AttendanceSchedule::toCarbon($date, $record->time_out);
=======
        $in  = $record->time_in  ? Carbon::createFromFormat('H:i:s', substr($record->time_in, 0, 8))  : null;
        $out = $record->time_out ? Carbon::createFromFormat('H:i:s', substr($record->time_out, 0, 8)) : null;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        if (! $in) {
            // Nothing to compute yet (e.g. only a time-out was corrected on an Absent day)
            $record->hours_worked = null;
            $record->status       = $record->status ?: 'Absent';
            return;
        }

<<<<<<< HEAD
        $isLate = $schedule->lateMinutes($in) > 0;
=======
        $isLate = $in->gt(
            Carbon::createFromFormat('H:i:s', self::WORK_START)->addMinutes(self::GRACE_MINUTES)
        );
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        if (! $out || $out->lte($in)) {
            // Only time-in known: can't compute hours yet
            $record->hours_worked = null;
            $record->status       = $isLate ? 'Late' : 'Present';
            return;
        }

<<<<<<< HEAD
        $hours = $schedule->hoursWorked($in, $out);

        $record->hours_worked = $hours;
        $record->status = $schedule->status($in, $out);
=======
        $hours = $in->diffInMinutes($out) / 60;

        // Deduct lunch when the shift covers the 12:00-13:00 break
        $noon = Carbon::createFromFormat('H:i:s', '12:00:00');
        $one  = Carbon::createFromFormat('H:i:s', '13:00:00');
        if ($in->lte($noon) && $out->gte($one)) {
            $hours -= self::LUNCH_HOURS;
        }

        // Cap at regular hours and round to the nearest half hour (matches your seed data)
        $hours = round(max(0, min($hours, self::MAX_HOURS)) * 2) / 2;

        $record->hours_worked = $hours;
        $record->status       = $hours < self::HALF_DAY_BELOW
            ? 'Half Day'
            : ($isLate ? 'Late' : 'Present');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }
}