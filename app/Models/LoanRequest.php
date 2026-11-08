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
<<<<<<< HEAD

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'hr_reviewed_at' => 'datetime',
        'accounting_reviewed_at' => 'datetime',
        'admin_reviewed_at' => 'datetime',
        'archived_at' => 'datetime',
        'staff_archived_at' => 'datetime',
=======
        'amount'                 => 'decimal:2',
        'requested_at'           => 'datetime',
        'hr_reviewed_at'         => 'datetime',
        'accounting_reviewed_at' => 'datetime',
        'admin_reviewed_at'      => 'datetime',
        'archived_at'            => 'datetime',
        'staff_archived_at'      => 'datetime',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

<<<<<<< HEAD
=======
    // HR still pending
    // [ADDED]
    public function scopeAwaitingHr(Builder $query): Builder
    {
        return $query->where('hr_status', 'pending');
    }

    // HR approved, Accounting still pending
    // [ADDED]
    public function scopeAwaitingAccounting(Builder $query): Builder
    {
        return $query->where('hr_status', 'approved')
            ->where('accounting_status', 'pending');
    }

    // HR and Accounting both approved, Admin still pending
    public function scopeAwaitingAdmin(Builder $query): Builder
    {
        return $query->where('hr_status', 'approved')
            ->where('accounting_status', 'approved')
            ->where('admin_status', 'pending');
    }

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
            ->whereMonth('requested_at', (int) $mon);
    }

    // A loan counts toward balances only after Admin's final approval.
=======
                     ->whereMonth('requested_at', (int) $mon);
    }

    // Approved by HR, Accounting and Admin (counts toward loan balances)
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public function scopeFullyApproved(Builder $query): Builder
    {
        return $query->where('hr_status', 'approved')
            ->where('accounting_status', 'approved')
            ->where('admin_status', 'approved');
    }

    /* ---------- Staff-side review helpers ---------- */

<<<<<<< HEAD
    // Admin takes precedence for accounts assigned multiple reviewer roles.
=======
    // Which approval stage this user acts on: 'admin' | 'hr' | 'accounting' | null
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public static function stageForUser($user): ?string
    {
        if (! $user) {
            return null;
        }
        if ($user->hasRole('Admin')) {
            return 'admin';
        }
<<<<<<< HEAD
        if ($user->hasRole('Accounting')) {
            return 'accounting';
        }
        if ($user->hasRole('HR')) {
            return 'hr';
        }
=======
        if ($user->hasRole('HR')) {
            return 'hr';
        }
        // [ADDED] Accounting decides the middle stage
        if ($user->hasRole('Accounting')) {
            return 'accounting';
        }
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        return null;
    }

    // Can this stage still be decided right now?
    public function canReviewAs(?string $stage): bool
    {
        return match ($stage) {
<<<<<<< HEAD
            'hr' => $this->hr_status === 'pending',
            'accounting' => $this->hr_status === 'approved'
                && $this->accounting_status === 'pending',
            'admin' => $this->hr_status === 'approved'
                && $this->accounting_status === 'approved'
                && $this->admin_status === 'pending',
            default => false,
=======
            'hr'         => $this->hr_status === 'pending',
            // [ADDED] HR approved, Accounting still pending
            'accounting' => $this->can_accounting_review,
            'admin'      => $this->can_review,   // HR + Accounting approved, Admin pending
            default      => false,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        };
    }

    /* ---------- Accessors (admin side) ---------- */

<<<<<<< HEAD
    // Admin can decide only after both earlier stages approve.
    public function getCanReviewAttribute(): bool
    {
        return $this->canReviewAs('admin');
=======
    // Admin can decide only after HR + Accounting approve, and only once
    public function getCanReviewAttribute(): bool
    {
        return $this->hr_status === 'approved'
            && $this->accounting_status === 'approved'
            && $this->admin_status === 'pending';
    }

    // [ADDED] Accounting can decide: HR approved, Accounting still pending
    public function getCanAccountingReviewAttribute(): bool
    {
        return $this->hr_status === 'approved' && $this->accounting_status === 'pending';
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    // Only finished requests can be archived (nothing still pending)
    public function getCanArchiveAttribute(): bool
    {
<<<<<<< HEAD
        return $this->staff_archived_at === null
            && (
                $this->hr_status === 'rejected'
                || $this->accounting_status === 'rejected'
                || in_array($this->admin_status, ['approved', 'rejected'], true)
            );
=======
        return $this->staff_archived_at === null && (
            $this->hr_status === 'rejected'
            || $this->accounting_status === 'rejected'
            || in_array($this->admin_status, ['approved', 'rejected'], true)
        );
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    // Text shown on the badge / next to the buttons
    public function getGateMessageAttribute(): string
    {
<<<<<<< HEAD
        if ($this->hr_status === 'rejected') {
            return 'Rejected by HR';
        }
        if ($this->hr_status !== 'approved') {
            return 'Waiting for HR decision';
        }
        if ($this->accounting_status === 'rejected') {
            return 'Rejected by Accounting';
        }
        if ($this->accounting_status !== 'approved') {
            return 'Waiting for Accounting decision';
        }

        return match ($this->admin_status) {
            'approved' => 'Finally approved by Admin',
            'rejected' => 'Rejected by Admin',
            default => 'Waiting for Admin decision',
=======
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

    // [ADDED] "Overall status" row of the Accounting details dialog
    public function getOverallStatusAttribute(): string
    {
        return match (true) {
            $this->hr_status === 'rejected'         => 'Rejected by HR',
            $this->accounting_status === 'rejected' => 'Rejected by Accounting',
            $this->admin_status === 'rejected'      => 'Rejected by Admin',
            $this->admin_status === 'approved'      => 'Approved',
            $this->hr_status !== 'approved'         => 'Awaiting HR',
            $this->accounting_status !== 'approved' => 'Awaiting Accounting',
            $this->admin_status !== 'approved'      => 'Awaiting Admin',
            default                                 => 'Pending',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        };
    }

    // Badge color key: locked | pending | approved | rejected
    public function getBadgeKeyAttribute(): string
    {
<<<<<<< HEAD
        if ($this->hr_status === 'rejected') {
            return 'rejected';
        }
        if ($this->accounting_status === 'rejected') {
            return 'rejected';
        }

        if ($this->hr_status !== 'approved' || $this->accounting_status !== 'approved') {
            return 'pending';
=======
        if ($this->hr_status !== 'approved' || $this->accounting_status !== 'approved') {
            return 'locked';
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        }

        return $this->admin_status;
    }

    // Whichever stage rejected the request
    public function getRejectionReasonTextAttribute(): ?string
    {
        return match (true) {
<<<<<<< HEAD
            $this->hr_status === 'rejected' => $this->hr_rejection_reason,
            $this->accounting_status === 'rejected' => $this->accounting_rejection_reason,
            $this->admin_status === 'rejected' => $this->admin_rejection_reason,
            default => null,
=======
            $this->hr_status === 'rejected'         => $this->hr_rejection_reason,
            $this->accounting_status === 'rejected' => $this->accounting_rejection_reason,
            $this->admin_status === 'rejected'      => $this->admin_rejection_reason,
            default                                 => null,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        };
    }

    public function getFormattedAmountAttribute(): string
    {
<<<<<<< HEAD
        return '₱'.number_format((float) $this->amount, 2);
=======
        return '₱' . number_format((float) $this->amount, 2);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    /* ---------- Employee side helpers ---------- */

    /**
<<<<<<< HEAD
     * Later stages are locked until the preceding reviewer approves. A
     * rejection blocks every stage after it.
=======
     * Stage status as the employee sees it: a stage that hasn't opened yet is
     * "locked", and stages behind a rejection are "blocked".
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
     * $stage = 'hr' | 'accounting' | 'admin'
     */
    public function effectiveStatus(string $stage): string
    {
<<<<<<< HEAD
        $hr = $this->hr_status;
        $accounting = $this->accounting_status;

        return match ($stage) {
            'hr' => $hr,
            'accounting' => match ($hr) {
                'approved' => $accounting,
                'rejected' => $accounting === 'rejected' ? 'rejected' : 'blocked',
                default => 'locked',
            },
            'admin' => match (true) {
                $hr === 'rejected' => 'rejected',
                $accounting === 'rejected' => 'rejected',
                $hr === 'approved' && $accounting === 'approved' => $this->admin_status,
                default => 'locked',
            },
            default => 'locked',
        };
    }

    public function isFullyApproved(): bool
    {
        return $this->hr_status === 'approved'
            && $this->accounting_status === 'approved'
            && $this->admin_status === 'approved';
=======
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

    /**
     * [ADDED] The three decision chips on each Accounting card: HR / Accounting / Admin.
     * Returns [['label' => 'HR', 'status' => 'approved', 'text' => 'Approved'], ...]
     * "locked" / "blocked" stages are shown as "Waiting".
     */
    public function stageChips(): array
    {
        $chips = [];

        foreach (['hr' => 'HR', 'accounting' => 'Accounting', 'admin' => 'Admin'] as $stage => $label) {
            $status = $this->effectiveStatus($stage) ?: 'pending';

            $chips[] = [
                'label'  => $label,
                'status' => $status,
                'text'   => in_array($status, ['locked', 'blocked'], true) ? 'Waiting' : ucfirst($status),
            ];
        }

        return $chips;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    // Loan amount minus payslip repayments (use ->withSum('repayments','amount') to avoid N+1)
    public function outstandingBalance(): float
    {
        $repaid = $this->repayments_sum_amount ?? $this->repayments()->sum('amount');

        return max(0, round((float) $this->amount - (float) $repaid, 2));
    }
<<<<<<< HEAD
}
=======

    /* ---------- [ADDED] Used by LoanBalanceService ---------- */

    // When the loan became active (orders repayments oldest-first)
    public function approvedAt()
    {
        return $this->admin_reviewed_at
            ?? $this->accounting_reviewed_at
            ?? $this->hr_reviewed_at
            ?? $this->requested_at;
    }

    // Matching payslip deduction column, or null when the type isn't tracked on payslips
    public function payslipDeductionColumn(): ?string
    {
        $type = preg_replace('/[^a-z]/', '', strtolower((string) $this->loan_type));

        return match ($type) {
            'sss'                    => 'sss_loan',
            'hmdf', 'hdmf'           => 'hdmf_loan',
            'peraaceap', 'peeaaceap' => 'peraa_ceap_loan',
            'cashadvance'            => 'cash_advance',
            default                  => null,   // PhilHealth: no loan column in `payslips`
        };
    }
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
