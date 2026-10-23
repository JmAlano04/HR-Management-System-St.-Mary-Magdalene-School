<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeCorrection extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    // Table: time_corrections (default Laravel naming, no $table needed)

    protected $fillable = [
        'employee_id', 'correction_date', 'correction_type',
        'incorrect_time', 'correct_time', 'note', 'proof_path',
        'admin_status', 'admin_reviewed_at', 'admin_reviewed_by',
        'hr_status', 'hr_reviewed_at', 'hr_reviewed_by',
    ];

    protected $casts = [
        'correction_date'   => 'date',
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

    /** Protected URL (proof is on the private disk). */
    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? route('time-corrections.proof', $this) : null;
    }

    /** Rejected if any reviewer rejected, Approved if both approved, else Pending. */
    public function getOverallStatusAttribute(): string
    {
        if ($this->hr_status === self::REJECTED || $this->admin_status === self::REJECTED) {
            return self::REJECTED;
        }
        if ($this->hr_status === self::APPROVED && $this->admin_status === self::APPROVED) {
            return self::APPROVED;
        }
        return self::PENDING;
    }

    public function scopePending($query)
    {
        return $query->where(fn ($q) => $q->where('hr_status', self::PENDING)->orWhere('admin_status', self::PENDING));
    }
}