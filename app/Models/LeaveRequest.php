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

<<<<<<< HEAD
=======
    protected $table = 'leave_requests';

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
        'employee_archived',   // NEW
        'decided_by',
        'decided_at',
        'hr_status',
        'hr_decided_by',
        'hr_decided_at',
        'admin_status',
        'admin_decided_by',
        'admin_decided_at',
        'rejection_reason',
=======
        'employee_archived',
        'decided_by',
        'decided_at',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    ];

    protected $casts = [
        'requested_date'    => 'date',
        'start_date'        => 'date',
        'end_date'          => 'date',
        'admin_archived'    => 'boolean',
<<<<<<< HEAD
        'employee_archived' => 'boolean',   // NEW
        'decided_at'        => 'datetime',
        'hr_decided_at'     => 'datetime',
        'admin_decided_at'  => 'datetime',
    ];

=======
        'employee_archived' => 'boolean',
        'decided_at'        => 'datetime',
    ];

    /* ---------- Relationships ---------- */

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

<<<<<<< HEAD
    // NEW: requests of one user
=======
    // Requests of one user
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

<<<<<<< HEAD
    // NEW: approved requests (these are the ones that deduct the balance)
=======
    // Approved requests (these are the ones that deduct the balance)
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public function scopeApproved($query)
    {
        return $query->whereRaw('LOWER(status) = ?', [self::APPROVED]);
    }

    /* ---------- Accessors ---------- */

<<<<<<< HEAD
    // "Oct 20, 2026 - Oct 22, 2026" (o isang petsa lang kung pareho)
    public function getDateRangeAttribute(): string
    {
=======
    // "Oct 20, 2026 - Oct 22, 2026" (or a single date when both are the same)
    public function getDateRangeAttribute(): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return 'Date unavailable';
        }

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('M d, Y');
        }

        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    public function getDaysCountAttribute(): int
    {
<<<<<<< HEAD
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    // NEW: "Pending" / "Approved" / "Rejected" for display
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::PENDING) {
            if ($this->hr_status === self::APPROVED) {
                return 'Awaiting Admin Review';
            }
            if ($this->admin_status === self::APPROVED) {
                return 'Awaiting HR Review';
            }

            return 'Pending Review';
        }

=======
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return (int) abs($this->start_date->diffInDays($this->end_date)) + 1;
    }

    // "Pending" / "Approved" / "Rejected" for display
    public function getStatusLabelAttribute(): string
    {
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        return ucfirst(strtolower((string) $this->status));
    }
}