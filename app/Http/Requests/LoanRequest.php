<?php

namespace App\Models;

use App\Models\Employee;
use App\Models\LoanRepayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRequest extends Model
{
    public const TYPES = ['SSS', 'HMDF', 'PhilHealth', 'PERAA/CEAP', 'Cash Advance'];
    public const CASH_ADVANCE_LIMIT = 2000;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'employee_id', 'loan_type', 'amount', 'reason', 'requested_at',
    ];

    protected $casts = [
        'amount'                => 'decimal:2',
        'requested_at'          => 'datetime',
        'hr_reviewed_at'        => 'datetime',
        'accounting_reviewed_at'=> 'datetime',
        'admin_reviewed_at'     => 'datetime',
        'archived_at'           => 'datetime',
    ];

    /* ---------------- Relationships ---------------- */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /* ---------------- Scopes ---------------- */

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeArchived(Builder $query, bool $archived): Builder
    {
        return $archived ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
    }

    /** Approved by HR, Accounting and Admin. */
    public function scopeFullyApproved(Builder $query): Builder
    {
        return $query->where('hr_status', self::APPROVED)
            ->where('accounting_status', self::APPROVED)
            ->where('admin_status', self::APPROVED);
    }

    /* ---------------- Workflow helpers ---------------- */

    /**
     * Status as the employee should see it. Stages that haven't opened yet
     * are "locked"; stages behind a rejection are "blocked".
     */
    public function effectiveStatus(string $stage): string
    {
        $hr = $this->hr_status;
        $acc = $this->accounting_status;

        return match ($stage) {
            'hr' => $hr,
            'accounting' => match ($hr) {
                self::APPROVED => $acc,
                self::REJECTED => 'blocked',
                default => 'locked',
            },
            'admin' => match (true) {
                $hr === self::REJECTED || $acc === self::REJECTED && $hr === self::APPROVED => 'blocked',
                $hr === self::APPROVED && $acc === self::APPROVED => $this->admin_status,
                default => 'locked',
            },
        };
    }

    public function isFullyApproved(): bool
    {
        return $this->hr_status === self::APPROVED
            && $this->accounting_status === self::APPROVED
            && $this->admin_status === self::APPROVED;
    }

    public function outstandingBalance(): float
    {
        $repaid = $this->repayments_sum_amount ?? $this->repayments()->sum('amount');

        return max(0, round((float) $this->amount - (float) $repaid, 2));
    }

    /** Shape the existing page JS already expects (camelCase). */
    public function toEmployeePayload(): array
    {
        return [
            'id'                       => $this->id,
            'loanType'                 => $this->loan_type,
            'amount'                   => (float) $this->amount,
            'reason'                   => $this->reason,
            'requestedAt'              => optional($this->requested_at)->toIso8601String(),
            'hrStatus'                 => $this->effectiveStatus('hr'),
            'accountingStatus'         => $this->effectiveStatus('accounting'),
            'adminStatus'              => $this->effectiveStatus('admin'),
            'hrRejectionReason'        => $this->hr_rejection_reason,
            'accountingRejectionReason'=> $this->accounting_rejection_reason,
            'adminRejectionReason'     => $this->admin_rejection_reason,
            'archived'                 => $this->archived_at !== null,
        ];
    }
}
