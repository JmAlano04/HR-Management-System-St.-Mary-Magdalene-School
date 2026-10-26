<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** app/Models/TimeCorrection.php */
class TimeCorrection extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    protected $fillable = [
        'employee_id',
        'correction_date',
        'correction_type',
        'incorrect_time',
        'correct_time',
        'note',
        'proof_path',
        'admin_status',
        'admin_reviewed_at',
        'admin_reviewed_by',
        'hr_status',
        'applied_at',
        'hr_reviewed_at',
        'hr_reviewed_by',
    ];

    protected $casts = [
        'correction_date'   => 'date',
        'admin_reviewed_at' => 'datetime',
        'hr_reviewed_at'    => 'datetime',
    ];


    public function isFullyApproved(): bool
{
    return $this->hr_status === self::APPROVED
        && $this->admin_status === self::APPROVED;
}
 
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}