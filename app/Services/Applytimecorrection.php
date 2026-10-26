<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\TimeCorrection;
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
    // ---- Schedule rules (inferred from your seed data; change to match your policy) ----
    private const WORK_START      = '08:00:00'; // time-in after this (+ grace) = Late
    private const GRACE_MINUTES   = 0;
    private const LUNCH_HOURS     = 1.0;        // deducted when the shift spans 12:00-13:00
    private const MAX_HOURS       = 8.0;        // regular daily hours (overtime not counted)
    private const HALF_DAY_BELOW  = 6.0;        // fewer hours than this = Half Day

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

        $this->recompute($record);

        $record->save();

        $correction->update(['applied_at' => now()]);
    }

    /** Recalculate hours_worked and status after a time change. */
    private function recompute(AttendanceRecord $record): void
    {
        $in  = $record->time_in  ? Carbon::createFromFormat('H:i:s', substr($record->time_in, 0, 8))  : null;
        $out = $record->time_out ? Carbon::createFromFormat('H:i:s', substr($record->time_out, 0, 8)) : null;

        if (! $in) {
            // Nothing to compute yet (e.g. only a time-out was corrected on an Absent day)
            $record->hours_worked = null;
            $record->status       = $record->status ?: 'Absent';
            return;
        }

        $isLate = $in->gt(
            Carbon::createFromFormat('H:i:s', self::WORK_START)->addMinutes(self::GRACE_MINUTES)
        );

        if (! $out || $out->lte($in)) {
            // Only time-in known: can't compute hours yet
            $record->hours_worked = null;
            $record->status       = $isLate ? 'Late' : 'Present';
            return;
        }

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
    }
}