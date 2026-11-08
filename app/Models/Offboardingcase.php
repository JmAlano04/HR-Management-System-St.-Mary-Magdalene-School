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
<<<<<<< HEAD
        'required_documents',
        'completed_interviews',
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        'rejection_reason',
        'sent_to_admin',
        'sent_to_admin_at',
        'admin_final_approved',
        'admin_approved_at',
        'admin_approved_by',
    ];

    protected $casts = [
<<<<<<< HEAD
        'last_working_day' => 'date',
        'requirements_sent_at' => 'datetime',
        'required_documents' => 'array',
        'completed_interviews' => 'array',
        'sent_to_admin' => 'boolean',
        'sent_to_admin_at' => 'datetime',
        'admin_final_approved' => 'boolean',
        'admin_approved_at' => 'datetime',
=======
        'last_working_day'     => 'date',
        'requirements_sent_at' => 'datetime',
        'sent_to_admin'        => 'boolean',
        'sent_to_admin_at'     => 'datetime',
        'admin_final_approved' => 'boolean',
        'admin_approved_at'    => 'datetime',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
        return $this->requirements_sent_at !== null || $this->sent_to_admin;
=======
        return $this->requirements_sent_at !== null;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    // Labels the employee may upload against (interview clearances excluded)
    public static function uploadableLabels(): array
    {
        return array_values(array_diff(self::REQUIRED_DOCUMENTS, self::INTERVIEW_LABELS));
    }

<<<<<<< HEAD
    public function getRequiredDocumentLabelsAttribute(): array
    {
        return $this->required_documents ?: self::REQUIRED_DOCUMENTS;
    }

    public function uploadableDocumentLabels(): array
    {
        return array_values(array_diff($this->required_document_labels, self::INTERVIEW_LABELS));
    }

    public function getUploadableDocumentLabelsAttribute(): array
    {
        return $this->uploadableDocumentLabels();
    }

    public function refreshProgress(): void
    {
        if ($this->sent_to_admin || $this->admin_final_approved) {
            $this->update(['progress' => 100]);

            return;
        }

        if (! $this->requirementsSent()) {
            return;
        }

        $uploadableLabels = $this->uploadableDocumentLabels();
        $totalSteps = count($this->required_document_labels);

        if ($totalSteps === 0) {
            $this->update(['progress' => 20]);

            return;
        }

        $documents = $this->documents()->get()->keyBy('document_label');
        $documentProgress = collect($uploadableLabels)->sum(function (string $label) use ($documents): float {
            $document = $documents->get($label);

            return match ($document?->review_status) {
                'Approved' => 1,
                'Under review', 'Rejected' => 0.5,
                default => 0,
            };
        });
        $completedInterviews = count(array_intersect(
            $this->completed_interviews ?? [],
            array_intersect($this->required_document_labels, self::INTERVIEW_LABELS)
        ));
        $completedSteps = $documentProgress + $completedInterviews;

        $this->update([
            'progress' => min(99, 20 + (int) round(79 * $completedSteps / $totalSteps)),
        ]);
    }

    public function getSubmittedRequiredDocumentCountAttribute(): int
    {
        return $this->documents
            ->whereIn('document_label', $this->uploadable_document_labels)
            ->count();
    }

    /* ---------- Accessors ---------- */

    // Every requested upload must be approved before HR can forward the case.
    public function getAllDocumentsApprovedAttribute(): bool
    {
        if ($this->sent_to_admin && $this->required_documents === null) {
            return $this->documents->isNotEmpty()
                && $this->documents->every(fn (OffboardingDocument $document) => $document->review_status === 'Approved');
        }

        $requiredLabels = $this->uploadableDocumentLabels();

        return $this->requirementsSent()
            && $requiredLabels !== []
            && collect($requiredLabels)->every(fn (string $label) => $this->documents
                ->firstWhere('document_label', $label)?->review_status === 'Approved');
    }

    public function getCanForwardToAdminAttribute(): bool
    {
        $requiredInterviews = array_intersect($this->required_document_labels, self::INTERVIEW_LABELS);

        return $this->requirementsSent()
            && ! $this->sent_to_admin
            && $this->status === 'Open'
            && $this->all_documents_approved
            && count(array_diff($requiredInterviews, $this->completed_interviews ?? [])) === 0;
=======
    /* ---------- Accessors ---------- */

    // Every submitted document must be approved by HR (and there must be at least one)
    public function getAllDocumentsApprovedAttribute(): bool
    {
        return $this->documents->isNotEmpty()
            && $this->documents->every(fn (OffboardingDocument $d) => $d->review_status === 'Approved');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    public function getCanFinalApproveAttribute(): bool
    {
        return $this->sent_to_admin
            && ! $this->admin_final_approved
<<<<<<< HEAD
            && $this->status !== 'Rejected'
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            && $this->all_documents_approved;
    }

    // Admin-side label (used by the admin offboarding dashboard)
    public function getStatusLabelAttribute(): string
    {
<<<<<<< HEAD
        return match (true) {
            $this->admin_final_approved => 'Final approved',
            $this->status === 'Rejected' => 'Rejected',
            default => 'Awaiting final approval',
        };
=======
        return $this->admin_final_approved ? 'Final approved' : 'Awaiting final approval';
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    public function getStatusMessageAttribute(): string
    {
        return match (true) {
<<<<<<< HEAD
            $this->admin_final_approved => 'Final approval has been completed.',
            $this->status === 'Rejected' => ($this->sent_to_admin ? 'Admin' : 'HR').' rejected this offboarding request. Reason: '.($this->rejection_reason ?: 'No reason provided.'),
            $this->all_documents_approved => 'All submitted documents are approved by HR.',
            default => 'Final approval is available after HR approves every submitted document.',
=======
            $this->admin_final_approved       => 'Final approval has been completed.',
            $this->all_documents_approved     => 'All submitted documents are approved by HR.',
            default                           => 'Final approval is available after HR approves every submitted document.',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        };
    }

    // Employee-side label (used by the employee tracker)
    public function getEmployeeStatusLabelAttribute(): string
    {
        return match (true) {
<<<<<<< HEAD
            $this->status === 'Rejected' => 'Rejected',
            $this->status === 'Closed' => 'Closed',
            $this->admin_final_approved => 'Final approved',
            $this->sent_to_admin => 'With Admin for final approval',
            $this->documents->isNotEmpty() => 'Documents submitted',
            $this->requirementsSent() => 'Documents requested',
            default => 'Awaiting HR review',
        };
    }
}
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
