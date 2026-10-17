<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeCorrection extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    protected $fillable = [
        'employee_id', 'correction_date', 'correction_type',
        'incorrect_time', 'correct_time', 'note', 'proof_path',
        'admin_status', 'admin_reviewed_at', 'admin_reviewed_by',
        'hr_status', 'hr_reviewed_at', 'hr_reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'correction_date'   => 'date',
            'admin_reviewed_at' => 'datetime',
            'hr_reviewed_at'    => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** "08:42:00" -> "8:42 AM" */
    private static function clock(?string $time): string
    {
        return $time ? date('g:i A', strtotime($time)) : 'No time recorded';
    }

    /** Data used by the details modal. $field is 'admin' or 'hr' (the current reviewer). */
    public function toCard(string $field): array
    {
        return [
            'id'              => $this->id,
            'employee_name'   => $this->employee?->name ?? 'Unknown employee',
            'date_label'      => $this->correction_date->format('M j, Y'),
            'type'            => $this->correction_type,
            'incorrect_time'  => self::clock($this->incorrect_time),
            'correct_time'    => self::clock($this->correct_time),
            'note'            => $this->note,
            'proof_url'       => $this->proof_path ? route('time-corrections.proof', $this) : null,
            'admin_status'    => $this->admin_status,
            'hr_status'       => $this->hr_status,
            'can_decide'      => $this->{$field . '_status'} === self::PENDING,
        ];
    }
}