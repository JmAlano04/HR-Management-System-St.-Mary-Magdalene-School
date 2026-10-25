<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payslip extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    /** Review stages; each maps to {stage}_status, {stage}_reviewed_at, etc. */
    public const STAGE_ADMIN = 'admin';
    public const STAGE_HR    = 'hr';

    protected $table = 'payslips';

    protected $fillable = [
        'employee_id', 'cutoff', 'transfer_date', 'step_name',
        'days_worked', 'hours_worked', 'late_minutes',
        'basic_pay', 'daily_wage', 'honorarium', 'gross_pay',
        'malasakitan', 'late_deduction', 'absences_deduction', 'sss', 'sss_loan', 'philhealth',
        'peraa_ceap', 'peraa_ceap_loan', 'hdmf', 'hdmf_loan', 'cash_advance',
        'total_deductions', 'net_pay',
        'sent_to_admin_at',
        'admin_status', 'admin_rejection_reason', 'admin_reviewed_at', 'admin_reviewed_by',
        'hr_status', 'hr_rejection_reason', 'hr_reviewed_at', 'hr_reviewed_by',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date'      => 'date',
            'sent_to_admin_at'   => 'datetime',
            'admin_reviewed_at'  => 'datetime',
            'hr_reviewed_at'     => 'datetime',
            'released_at'        => 'datetime',
            'days_worked'        => 'float',
            'hours_worked'       => 'float',
            'late_minutes'       => 'integer',
            'basic_pay'          => 'float',
            'daily_wage'         => 'float',
            'honorarium'         => 'float',
            'gross_pay'          => 'float',
            'malasakitan'        => 'float',
            'late_deduction'     => 'float',
            'absences_deduction' => 'float',
            'sss'                => 'float',
            'sss_loan'           => 'float',
            'philhealth'         => 'float',
            'peraa_ceap'         => 'float',
            'peraa_ceap_loan'    => 'float',
            'hdmf'               => 'float',
            'hdmf_loan'          => 'float',
            'cash_advance'       => 'float',
            'total_deductions'   => 'float',
            'net_pay'            => 'float',
        ];
    }

    /* ------------------------------------------------------------------
     | Relationships
     * ------------------------------------------------------------------ */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** The employee's own approve/reject decision (one per payslip). */
    public function response(): HasOne
    {
        return $this->hasOne(PayslipResponse::class, 'payslip_id');
    }

    /* ------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    public function scopeSentToAdmin(Builder $query): Builder
    {
        return $query->whereNotNull('sent_to_admin_at');
    }

    /** Released by Accounting, so visible to the employee. */
    public function scopeReleased(Builder $query): Builder
    {
        return $query->whereNotNull('released_at');
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /** Payslips where BOTH Admin and HR approved. */
    public function scopeFullyApproved(Builder $query): Builder
    {
        return $query->where('admin_status', self::APPROVED)
                     ->where('hr_status', self::APPROVED);
    }

    /** Payslips where a given stage ('admin' or 'hr') has this status. */
    public function scopeStageStatus(Builder $query, string $stage, string $status): Builder
    {
        return $query->where(self::stageColumn($stage, 'status'), $status);
    }

    /* ------------------------------------------------------------------
     | Review workflow helpers
     * ------------------------------------------------------------------ */

    /** Column name for a stage, e.g. stageColumn('hr', 'status') => 'hr_status'. */
    public static function stageColumn(string $stage, string $field): string
    {
        return in_array($stage, [self::STAGE_ADMIN, self::STAGE_HR], true)
            ? "{$stage}_{$field}"
            : throw new \InvalidArgumentException("Unknown review stage [{$stage}].");
    }

    /**
     * Normalized stage status: 'pending' | 'approved' | 'rejected'.
     * Case-insensitive, so legacy lowercase and capitalized values both work.
     */
    public function stageStatus(string $stage): string
    {
        return strtolower((string) $this->{self::stageColumn($stage, 'status')});
    }

    public function isStagePending(string $stage): bool
    {
        return $this->stageStatus($stage) === 'pending';
    }

    public function approveStage(string $stage, int|string|null $userId): bool
    {
        return $this->update([
            self::stageColumn($stage, 'status')      => self::APPROVED,
            self::stageColumn($stage, 'reviewed_at') => now(),
            self::stageColumn($stage, 'reviewed_by') => $userId,
        ]);
    }

    public function rejectStage(string $stage, string $reason, int|string|null $userId): bool
    {
        return $this->update([
            self::stageColumn($stage, 'status')           => self::REJECTED,
            self::stageColumn($stage, 'rejection_reason') => trim($reason),
            self::stageColumn($stage, 'reviewed_at')      => now(),
            self::stageColumn($stage, 'reviewed_by')      => $userId,
        ]);
    }

    public function isFullyApproved(): bool
    {
        return $this->stageStatus(self::STAGE_ADMIN) === 'approved'
            && $this->stageStatus(self::STAGE_HR) === 'approved';
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }

    /** Release to the employee. Only allowed once Admin and HR both approved. */
    public function release(): bool
    {
        if ($this->isReleased() || ! $this->isFullyApproved()) {
            return false;
        }

        return $this->update(['released_at' => now()]);
    }

    /* ------------------------------------------------------------------
     | Cards for the views
     * ------------------------------------------------------------------ */

    public function toCard(): array
    {
        $e = $this->employee;

        return [
            'id'              => $this->id,
            'employee_name'   => $e?->name ?? 'Unknown employee',
            'employee_number' => $e?->employee_id ?? '-',
            'department'      => $e ? (Employee::DEPARTMENTS[$e->department] ?? $e->department) : '-',
            'position'        => $e?->position ?? '-',
            'step_name'       => $this->step_name ?: '-',
            'days_worked'     => $this->days_worked,
            'hours_worked'    => $this->hours_worked,
            'late_minutes'    => $this->late_minutes,
            'cutoff'          => $this->cutoff,
            'basic_pay'       => $this->basic_pay,
            'daily_wage'      => $this->daily_wage,
            'honorarium'      => $this->honorarium,
            'gross_pay'       => $this->gross_pay,
            'deductions'      => [
                'malasakitan'     => $this->malasakitan,
                'late'            => $this->late_deduction,
                'absences'        => $this->absences_deduction,
                'sss'             => $this->sss,
                'sss_loan'        => $this->sss_loan,
                'philhealth'      => $this->philhealth,
                'peraa_ceap'      => $this->peraa_ceap,
                'peraa_ceap_loan' => $this->peraa_ceap_loan,
                'hdmf'            => $this->hdmf,
                'hdmf_loan'       => $this->hdmf_loan,
                'cash_advance'    => $this->cash_advance,
            ],
            'total_deductions' => $this->total_deductions,
            'net_pay'          => $this->net_pay,
        ];
    }

    /** Card for the employee page. Field names match what the Blade/Alpine code reads. */
    public function toEmployeeCard(): array
    {
        $r = $this->response;

        return $this->toCard() + [
            'employee_status' => $r?->status ?? self::PENDING,
            'employee_reason' => $r?->reason,
        ];
    }
}