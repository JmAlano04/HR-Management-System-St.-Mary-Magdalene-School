<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffboardingCase extends Model
{
    use HasFactory;

    // Saang disk naka-save ang mga submitted documents
    public const DOCUMENT_DISK = 'local';

    // Documents HR requires for a resignation clearance
    public const REQUIRED_DOCUMENTS = [
        'Letter of Intent',
        'Resignation Letter',
        'DICES Exit Interview',
        'Exit Interview with School Director',
        'DICES Quitclaim Form',
        'Accomplished Clearance Form',
        'Quitclaim Notarized',
        'Resignation Acceptance Letter',
        'CEAP Form 9/PERAA Retirement Form',
        'Photocopy of Valid IDs with three specimen signature',
    ];

    // Marked Done by HR; the employee does not upload these
    public const INTERVIEW_LABELS = [
        'DICES Exit Interview',
        'Exit Interview with School Director',
    ];

    protected $fillable = [
        'employee_id',
        'reason',
        'last_working_day',
        'employee_message',
        'status',
        'progress',
        'requirements_sent_at',
        'rejection_reason',
        'sent_to_admin',
        'sent_to_admin_at',
        'admin_final_approved',
        'admin_approved_at',
        'admin_approved_by',
    ];

    protected $casts = [
        'last_working_day'     => 'date',
        'requirements_sent_at' => 'datetime',
        'sent_to_admin'        => 'boolean',
        'sent_to_admin_at'     => 'datetime',
        'admin_final_approved' => 'boolean',
        'admin_approved_at'    => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OffboardingDocument::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    /* ---------- Scopes ---------- */

    // Cases that HR already handed off to Admin
    public function scopeSentToAdmin($query)
    {
        return $query->where('sent_to_admin', true);
    }

    /* ---------- Helpers ---------- */

    // True once HR has released the document requirements to the employee
    public function requirementsSent(): bool
    {
        return $this->requirements_sent_at !== null;
    }

    // Labels the employee may upload against (interview clearances excluded)
    public static function uploadableLabels(): array
    {
        return array_values(array_diff(self::REQUIRED_DOCUMENTS, self::INTERVIEW_LABELS));
    }

    /* ---------- Accessors ---------- */

    // Every submitted document must be approved by HR (and there must be at least one)
    public function getAllDocumentsApprovedAttribute(): bool
    {
        return $this->documents->isNotEmpty()
            && $this->documents->every(fn (OffboardingDocument $d) => $d->review_status === 'Approved');
    }

    public function getCanFinalApproveAttribute(): bool
    {
        return $this->sent_to_admin
            && ! $this->admin_final_approved
            && $this->all_documents_approved;
    }

    // Admin-side label (used by the admin offboarding dashboard)
    public function getStatusLabelAttribute(): string
    {
        return $this->admin_final_approved ? 'Final approved' : 'Awaiting final approval';
    }

    public function getStatusMessageAttribute(): string
    {
        return match (true) {
            $this->admin_final_approved       => 'Final approval has been completed.',
            $this->all_documents_approved     => 'All submitted documents are approved by HR.',
            default                           => 'Final approval is available after HR approves every submitted document.',
        };
    }

    // Employee-side label (used by the employee tracker)
    public function getEmployeeStatusLabelAttribute(): string
    {
        return match (true) {
            $this->status === 'Rejected'   => 'Rejected',
            $this->status === 'Closed'     => 'Closed',
            $this->admin_final_approved    => 'Final approved',
            $this->sent_to_admin           => 'With Admin for final approval',
            $this->documents->isNotEmpty() => 'Documents submitted',
            $this->requirementsSent()      => 'Documents requested',
            default                        => 'Awaiting HR review',
        };
    }

    
}