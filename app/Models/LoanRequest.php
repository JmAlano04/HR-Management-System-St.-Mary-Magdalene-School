<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRequest extends Model
{
    use HasFactory;

    public const TYPES = ['SSS', 'HMDF', 'PhilHealth', 'PERAA/CEAP', 'Cash Advance'];
    public const CASH_ADVANCE_LIMIT = 2000;

    protected $fillable = [
        'employee_id',
        'loan_type',
        'amount',
        'reason',
        'requested_at',
        'hr_status',
        'hr_rejection_reason',
        'hr_reviewed_at',
        'hr_reviewed_by',
        'accounting_status',
        'accounting_rejection_reason',
        'accounting_reviewed_at',
        'accounting_reviewed_by',
        'admin_status',
        'admin_rejection_reason',
        'admin_reviewed_at',
        'admin_reviewed_by',
        'archived_at',          // employee Archive tab
        'staff_archived_at',    // HR/Admin Archive tab
        'staff_archived_by',
    ];

    protected $casts = [
        'amount'                 => 'decimal:2',
        'requested_at'           => 'datetime',
        'hr_reviewed_at'         => 'datetime',
        'accounting_reviewed_at' => 'datetime',
        'admin_reviewed_at'      => 'datetime',
        'archived_at'            => 'datetime',
        'staff_archived_at'      => 'datetime',
    ];

    /* ---------- Relationships ---------- */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    public function hrReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_reviewed_by');
    }

    public function accountingReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounting_reviewed_by');
    }

    public function staffArchiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_archived_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /* ---------- Scopes ---------- */

    // HR and Accounting both approved, Admin still pending
    public function scopeAwaitingAdmin(Builder $query): Builder
    {
        return $query->where('hr_status', 'approved')
            ->where('accounting_status', 'approved')
            ->where('admin_status', 'pending');
    }

    // Employee side: only this employee's requests
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    // Employee side: Active tab (false) vs Archive tab (true)
    public function scopeArchived(Builder $query, bool $archived): Builder
    {
        return $archived ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
    }

    // HR/Admin side: Active tab (false) vs Archive tab (true)
    public function scopeStaffArchived(Builder $query, bool $archived): Builder
    {
        return $archived
            ? $query->whereNotNull('staff_archived_at')
            : $query->whereNull('staff_archived_at');
    }

    // Month filter, expects "YYYY-MM" (matches <input type="month">)
    public function scopeInMonth(Builder $query, ?string $month): Builder
    {
        if (! $month || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $query;
        }

        [$year, $mon] = explode('-', $month);

        return $query->whereYear('requested_at', (int) $year)
                     ->whereMonth('requested_at', (int) $mon);
    }

    // Approved by HR, Accounting and Admin (counts toward loan balances)
    public function scopeFullyApproved(Builder $query): Builder
    {
        return $query->where('hr_status', 'approved')
            ->where('accounting_status', 'approved')
            ->where('admin_status', 'approved');
    }

    /* ---------- Staff-side review helpers ---------- */

    // Which approval stage this user acts on: 'admin' | 'hr' | null
    public static function stageForUser($user): ?string
    {
        if (! $user) {
            return null;
        }
        if ($user->hasRole('Admin')) {
            return 'admin';
        }
        if ($user->hasRole('HR')) {
            return 'hr';
        }

        return null;
    }

    // Can this stage still be decided right now?
    public function canReviewAs(?string $stage): bool
    {
        return match ($stage) {
            'hr'    => $this->hr_status === 'pending',
            'admin' => $this->can_review,   // HR + Accounting approved, Admin pending
            default => false,
        };
    }

    /* ---------- Accessors (admin side) ---------- */

    // Admin can decide only after HR + Accounting approve, and only once
    public function getCanReviewAttribute(): bool
    {
        return $this->hr_status === 'approved'
            && $this->accounting_status === 'approved'
            && $this->admin_status === 'pending';
    }

    // Only finished requests can be archived (nothing still pending)
    public function getCanArchiveAttribute(): bool
    {
        return $this->staff_archived_at === null && (
            $this->hr_status === 'rejected'
            || $this->accounting_status === 'rejected'
            || in_array($this->admin_status, ['approved', 'rejected'], true)
        );
    }

    // Text shown on the badge / next to the buttons
    public function getGateMessageAttribute(): string
    {
        if ($this->hr_status !== 'approved') {
            return $this->hr_status === 'rejected' ? 'Stopped after HR rejection' : 'Waiting for HR approval';
        }
        if ($this->accounting_status !== 'approved') {
            return $this->accounting_status === 'rejected' ? 'Stopped after Accounting rejection' : 'Waiting for Accounting approval';
        }

        return match ($this->admin_status) {
            'approved' => 'Approved by Admin',
            'rejected' => 'Rejected by Admin',
            default    => 'Awaiting Admin review',
        };
    }

    // Badge color key: locked | pending | approved | rejected
    public function getBadgeKeyAttribute(): string
    {
        if ($this->hr_status !== 'approved' || $this->accounting_status !== 'approved') {
            return 'locked';
        }

        return $this->admin_status;
    }

    // Whichever stage rejected the request
    public function getRejectionReasonTextAttribute(): ?string
    {
        return match (true) {
            $this->hr_status === 'rejected'         => $this->hr_rejection_reason,
            $this->accounting_status === 'rejected' => $this->accounting_rejection_reason,
            $this->admin_status === 'rejected'      => $this->admin_rejection_reason,
            default                                 => null,
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return '₱' . number_format((float) $this->amount, 2);
    }

    /* ---------- Employee side helpers ---------- */

    /**
     * Stage status as the employee sees it: a stage that hasn't opened yet is
     * "locked", and stages behind a rejection are "blocked".
     * $stage = 'hr' | 'accounting' | 'admin'
     */
    public function effectiveStatus(string $stage): string
    {
        $hr  = $this->hr_status;
        $acc = $this->accounting_status;

        return match ($stage) {
            'hr'         => $hr,
            'accounting' => match ($hr) {
                'approved' => $acc,
                'rejected' => 'blocked',
                default    => 'locked',
            },
            'admin'      => match (true) {
                $hr === 'rejected',
                $hr === 'approved' && $acc === 'rejected' => 'blocked',
                $hr === 'approved' && $acc === 'approved' => $this->admin_status,
                default                                   => 'locked',
            },
        };
    }

    // Loan amount minus payslip repayments (use ->withSum('repayments','amount') to avoid N+1)
    public function outstandingBalance(): float
    {
        $repaid = $this->repayments_sum_amount ?? $this->repayments()->sum('amount');

        return max(0, round((float) $this->amount - (float) $repaid, 2));
    }
}