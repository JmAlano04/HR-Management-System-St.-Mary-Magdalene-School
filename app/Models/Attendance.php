<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Table: attendance_records
 * Columns: id, employee_id (FK -> employees.id), date, time_in, time_out,
 *          hours_worked, status, archived, created_at, updated_at
 */
class Attendance extends Model
{
    protected $table = 'attendance_records';

    public const PRESENT      = 'Present';
    public const LATE         = 'Late';
    public const HALF_DAY     = 'Half Day';
    public const PAID_LEAVE   = 'Paid Leave';
    public const UNPAID_LEAVE = 'Unpaid Leave';
    public const ABSENT       = 'Absent';
    public const NWD_REVIEW   = 'Non-Working Day Review';

    /** Statuses selectable in the Add Attendance form. */
    public const FORM_STATUSES = [
        self::PRESENT, self::LATE, self::HALF_DAY,
        self::PAID_LEAVE, self::UNPAID_LEAVE, self::ABSENT,
    ];

    /** Statuses that do NOT need time in / time out. */
    public const NO_TIME_STATUSES = [
        self::ABSENT, self::PAID_LEAVE, self::UNPAID_LEAVE,
    ];

    /*
     * Hours rule inferred from your seeded data:
     *  07:50-17:05 => 8.00   (clamped to 08:00-17:00, minus 1h lunch)
     *  08:35-17:00 => 7.50   (rounded to nearest 0.5h)
     *  08:00-12:00 => 4.00   (no lunch overlap)
     * Adjust these constants if your HR rules differ.
     */
    public const WORK_START  = '08:00';
    public const WORK_END    = '17:00';
    public const LUNCH_START = '12:00';
    public const LUNCH_END   = '13:00';

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'hours_worked',
        'status',
        'archived',
    ];

    protected $casts = [
        'date'         => 'date:Y-m-d',
        'hours_worked' => 'decimal:2',
        'archived'     => 'boolean',
    ];

    /* ---------------- Relationships ---------------- */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(SchoolEmployee::class, 'employee_id');
    }

    /* ---------------- Scopes ---------------- */

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('archived', false);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('archived', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->whereHas('employee', function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('employee_id', 'like', "%{$term}%");
        });
    }

    public function scopeOnDate(Builder $query, ?string $date): Builder
    {
        return $date ? $query->whereDate('date', $date) : $query;
    }

    public function scopeInDepartment(Builder $query, ?string $department): Builder
    {
        if (! $department || $department === 'all') {
            return $query;
        }

        return $query->whereHas('employee', fn (Builder $q) => $q->where('department', $department));
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        if (! $status || $status === 'all') {
            return $query;
        }

        return $query->whereRaw('LOWER(status) = ?', [strtolower($status)]);
    }

    /* ---------------- Computations ---------------- */

    private static function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $h * 60 + $m;
    }

    /** Creditable hours: clamp to work window, remove lunch overlap, round to 0.5. */
    public static function computeHours(?string $timeIn, ?string $timeOut): float
    {
        if (! $timeIn || ! $timeOut) {
            return 0.0;
        }

        $start = max(self::minutes($timeIn), self::minutes(self::WORK_START));
        $end   = min(self::minutes($timeOut), self::minutes(self::WORK_END));

        if ($end <= $start) {
            return 0.0;
        }

        $lunchOverlap = max(
            0,
            min($end, self::minutes(self::LUNCH_END)) - max($start, self::minutes(self::LUNCH_START))
        );

        $hours = ($end - $start - $lunchOverlap) / 60;

        return round($hours * 2) / 2;
    }

    public static function computeLateMinutes(?string $timeIn): int
    {
        if (! $timeIn) {
            return 0;
        }

        return max(self::minutes($timeIn) - self::minutes(self::WORK_START), 0);
    }

    public static function computeDaysWorked(string $status): float
    {
        return match ($status) {
            self::PRESENT, self::LATE => 1.0,
            self::HALF_DAY            => 0.5,
            default                   => 0.0,
        };
    }
}