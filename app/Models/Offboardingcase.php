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

    protected $fillable = [
        'employee_id',
        'reason',
        'last_working_day',
        'employee_message',
        'status',
        'progress',
        'sent_to_admin',
        'sent_to_admin_at',
        'admin_final_approved',
        'admin_approved_at',
        'admin_approved_by',
    ];

    protected $casts = [
        'last_working_day'     => 'date',
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
}