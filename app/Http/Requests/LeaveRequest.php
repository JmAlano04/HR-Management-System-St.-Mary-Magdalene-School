<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    protected $fillable = [
        'employee_id', 'leave_type', 'other_leave_type',
        'start_date', 'end_date', 'days', 'reason',
        'admin_status', 'admin_reviewed_at', 'admin_reviewed_by',
        'hr_status', 'hr_reviewed_at', 'hr_reviewed_by',
        'employee_archived',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'days'              => 'integer',
        'employee_archived' => 'boolean',
        'admin_reviewed_at' => 'datetime',
        'hr_reviewed_at'    => 'datetime',
    ];

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

    /** Name shown in the list: "Others: <text>" for custom types. */
    public function getLabelAttribute(): string
    {
        return $this->leave_type === 'Other'
            ? 'Others: ' . $this->other_leave_type
            : $this->leave_type;
    }

    /** "2026-10-26 - 2026-10-28 (3 days)" */
    public function getDateRangeAttribute(): string
    {
        return sprintf(
            '%s - %s (%d day%s)',
            $this->start_date->toDateString(),
            $this->end_date->toDateString(),
            $this->days,
            $this->days === 1 ? '' : 's'
        );
    }

    /** Balance is only deducted when BOTH HR and Admin approved. */
    public function scopeFinalApproved($query)
    {
        return $query->where('hr_status', self::APPROVED)->where('admin_status', self::APPROVED);
    }
}