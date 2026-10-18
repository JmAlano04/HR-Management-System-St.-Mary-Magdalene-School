<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRequest extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'amount'                 => 'decimal:2',
        'requested_at'           => 'datetime',
        'hr_reviewed_at'         => 'datetime',
        'accounting_reviewed_at' => 'datetime',
        'admin_reviewed_at'      => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    /* ---------- Scopes ---------- */

    // HR and Accounting both approved, Admin still pending
    public function scopeAwaitingAdmin(Builder $query): Builder
    {
        return $query->where('hr_status', 'approved')
            ->where('accounting_status', 'approved')
            ->where('admin_status', 'pending');
    }

    /* ---------- Accessors ---------- */

    // Admin can decide only after HR + Accounting approve, and only once
    public function getCanReviewAttribute(): bool
    {
        return $this->hr_status === 'approved'
            && $this->accounting_status === 'approved'
            && $this->admin_status === 'pending';
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
}