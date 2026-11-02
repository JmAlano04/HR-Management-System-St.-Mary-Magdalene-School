<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    // Status values. Change these if your table stores them differently
    // (e.g. 'Pending'). Comparisons below are case-insensitive.
    public const PENDING  = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $table = 'leave_requests';

    protected $fillable = [
        'user_id',
        'employee_code',
        'employee_name',
        'department',
        'type',
        'requested_date',
        'start_date',
        'end_date',
        'reason',
        'leave_balance',
        'status',
        'admin_archived',
        'employee_archived',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'requested_date'    => 'date',
        'start_date'        => 'date',
        'end_date'          => 'date',
        'admin_archived'    => 'boolean',
        'employee_archived' => 'boolean',
        'decided_at'        => 'datetime',
    ];

    /* ---------- Relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('admin_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('admin_archived', true);
    }

    // Requests of one user
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Approved requests (these are the ones that deduct the balance)
    public function scopeApproved($query)
    {
        return $query->whereRaw('LOWER(status) = ?', [self::APPROVED]);
    }

    /* ---------- Accessors ---------- */

    // "Oct 20, 2026 - Oct 22, 2026" (or a single date when both are the same)
    public function getDateRangeAttribute(): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return 'Date unavailable';
        }

        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('M d, Y');
        }

        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    public function getDaysCountAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return (int) abs($this->start_date->diffInDays($this->end_date)) + 1;
    }

    // "Pending" / "Approved" / "Rejected" for display
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(strtolower((string) $this->status));
    }
}