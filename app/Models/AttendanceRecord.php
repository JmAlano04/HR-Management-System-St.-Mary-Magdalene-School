<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUSES = ['Present', 'Absent', 'Late', 'Half Day'];

    // Mga department value sa employees table -> label na ipinapakita
    public const DEPARTMENT_LABELS = [
        'elementary' => 'Elementary',
        'highschool' => 'High School',
        'seniorhigh' => 'Senior High School',
    ];

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
        'date'         => 'date',
        'hours_worked' => 'decimal:2',
        'archived'     => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /* ---------- Helpers ---------- */

    public static function departmentLabel(?string $department): string
    {
        if (! $department) {
            return 'N/A';
        }

        return self::DEPARTMENT_LABELS[$department] ?? str($department)->headline()->toString();
    }

    /* ---------- Scopes ---------- */

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('archived', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('archived', false);
    }

    /**
     * Search (name / employee ID), date, department, and status filters.
     * $ignoreDate = true kapag nakabukas na ang isang archive folder (petsa na ang folder).
     */
    public function scopeFilter(Builder $query, array $filters, bool $ignoreDate = false): Builder
    {
        $q          = trim((string) ($filters['q'] ?? ''));
        $date       = $filters['date'] ?? null;
        $department = $filters['department'] ?? null;
        $status     = $filters['status'] ?? null;

        return $query
            ->when($q !== '', function (Builder $w) use ($q) {
                $like = "%{$q}%";

                $w->whereHas('employee', fn (Builder $e) => $e
                    ->where('name', 'like', $like)
                    ->orWhere('employee_id', 'like', $like));
            })
            ->when(! $ignoreDate && $date, fn (Builder $w) => $w->whereDate('date', $date))
            ->when($department && $department !== 'all', fn (Builder $w) => $w
                ->whereHas('employee', fn (Builder $e) => $e->where('department', $department)))
            ->when($status && $status !== 'all', fn (Builder $w) => $w
                ->whereRaw('LOWER(status) = ?', [strtolower($status)]));
    }

    /* ---------- Accessors ---------- */

    public function getTimeInLabelAttribute(): string
    {
        return $this->time_in ? Carbon::parse($this->time_in)->format('h:i A') : '—';
    }

    public function getTimeOutLabelAttribute(): string
    {
        return $this->time_out ? Carbon::parse($this->time_out)->format('h:i A') : '—';
    }

    public function getHoursLabelAttribute(): string
    {
        return $this->hours_worked !== null ? number_format((float) $this->hours_worked, 1) : '—';
    }
}