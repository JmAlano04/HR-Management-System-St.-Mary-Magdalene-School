<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRecordService
{
    public const WORK_START = '07:00:00';   // later than this = Late
    public const WORK_END   = '16:00:00';   // earlier than this = Undertime
    public const MIN_GAP_SECONDS = 60;      // ignore double-scans within this gap

    /**
     * Build/refresh the daily attendance_records row for one biometric user and date.
     * Returns 'created', 'updated' or 'unmatched'.
     */
    public function upsert(string $zkUserId, string $date): string
    {
        $employee = DB::table('employees')
            ->where('zkteco_user_id', $zkUserId)
            ->first(['id']);

        if (!$employee) {
            return 'unmatched';
        }

        $bounds = DB::table('zkteco_attendances')
            ->where('user_id', $zkUserId)
            ->whereDate('punched_at', $date)
            ->selectRaw('MIN(punched_at) as first_punch, MAX(punched_at) as last_punch')
            ->first();

        if (!$bounds || !$bounds->first_punch) {
            return 'unmatched';
        }

        $first = Carbon::parse($bounds->first_punch);
        $last  = Carbon::parse($bounds->last_punch);

        $existing = DB::table('attendance_records')
            ->where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        $timeIn  = $first->format('H:i:s');
        $timeOut = abs($last->getTimestamp() - $first->getTimestamp()) >= self::MIN_GAP_SECONDS
            ? $last->format('H:i:s')
            : null;

        // Merge with an existing row so we never shrink a manual/approved time.
        if ($existing) {
            if ($existing->time_in && $existing->time_in < $timeIn) {
                $timeIn = $existing->time_in;
            }
            if ($existing->time_out && ($timeOut === null || $existing->time_out > $timeOut)) {
                $timeOut = $existing->time_out;
            }
        }

        $hours = null;
        if ($timeOut !== null) {
            $in  = Carbon::parse($date . ' ' . $timeIn);
            $out = Carbon::parse($date . ' ' . $timeOut);
            $hours = round(abs($out->getTimestamp() - $in->getTimestamp()) / 3600, 2);
        }

        $data = [
            'time_in'      => $timeIn,
            'time_out'     => $timeOut,
            'hours_worked' => $hours,
            'status'       => $this->resolveStatus($timeIn, $timeOut),
            'updated_at'   => now(),
        ];

        if ($existing) {
            DB::table('attendance_records')->where('id', $existing->id)->update($data);

            return 'updated';
        }

        DB::table('attendance_records')->insert($data + [
            'employee_id' => $employee->id,
            'date'        => $date,
            'archived'    => 0,
            'created_at'  => now(),
        ]);

        return 'created';
    }

    private function resolveStatus(string $timeIn, ?string $timeOut): string
    {
        if ($timeIn > self::WORK_START) {
            return 'Late';
        }
        if ($timeOut !== null && $timeOut < self::WORK_END) {
            return 'Undertime';
        }

        return 'Present';
    }
}