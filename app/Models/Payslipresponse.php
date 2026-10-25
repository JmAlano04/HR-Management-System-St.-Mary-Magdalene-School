<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipResponse extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    protected $fillable = ['payslip_id', 'employee_id', 'status', 'reason', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** Approved or Rejected means the employee already made a decision. */
    public function isFinal(): bool
    {
        return in_array($this->status, [self::APPROVED, self::REJECTED], true);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}