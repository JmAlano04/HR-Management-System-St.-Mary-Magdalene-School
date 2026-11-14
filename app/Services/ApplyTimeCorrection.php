<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\TimeCorrection;
use App\Support\AttendanceSchedule;
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
        $record->archived = false;

        $this->recompute($record);

        $record->save();

        $correction->update(['applied_at' => now()]);
    }

    /** Recalculate hours_worked and status after a time change. */
    private function recompute(AttendanceRecord $record): void
    {
        $date = Carbon::parse($record->date)->startOfDay();
        $schedule = AttendanceSchedule::forEmployee(
            $record->employee?->role_type,
            $record->employee?->position,
            $date
        );
        $in = AttendanceSchedule::toCarbon($date, $record->time_in);
        $out = AttendanceSchedule::toCarbon($date, $record->time_out);

        if (! $in) {
            // Nothing to compute yet (e.g. only a time-out was corrected on an Absent day)
            $record->hours_worked = null;
            $record->status       = $record->status ?: 'Absent';
            return;
        }

        $isLate = $schedule->lateMinutes($in) > 0;

        if (! $out || $out->lte($in)) {
            // Only time-in known: can't compute hours yet
            $record->hours_worked = null;
            $record->status       = $isLate ? 'Late' : 'Present';
            return;
        }

        $hours = $schedule->hoursWorked($in, $out);

        $record->hours_worked = $hours;
        $record->status = $schedule->status($in, $out);
    }
}