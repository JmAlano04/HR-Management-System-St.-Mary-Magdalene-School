<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    // Disk where applicant uploads are stored. Change if your upload code uses another disk.
    public const DOCUMENT_DISK = 'public';

    // column name => display meta
    public const DOCUMENTS = [
        'resume'      => ['label' => 'Resume'],
        'coverletter' => ['label' => 'Cover Letter'],
        'appletter'   => ['label' => 'Application Letter'],
    ];

    // Statuses that mean the application is finished (goes to the Archive tab)
    public const APPROVED_STATUSES = ['qualified', 'approved'];
    public const REJECTED_STATUSES = ['rejected'];

    protected $fillable = [
        'user_id',
        'fullname',
        'email',
        'phone',
        'home_address',
        'birth_date',
        'gender',
        'civil_status',
        'education',
        'job_type',
        'position',
        'resume',
        'coverletter',
        'appletter',
        'interview_date',
        'interview_time',
        'interview_location',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'birth_date'     => 'date',
        'interview_date' => 'date',
    ];

    /* ---------- Relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Scopes ---------- */

    // Finished (approved or rejected) = Archive tab. Everything else = Active tab.
    public function scopeArchived(Builder $query, bool $archived = true): Builder
    {
        $finished = array_merge(self::APPROVED_STATUSES, self::REJECTED_STATUSES);

        return $archived
            ? $query->whereIn('status', $finished)
            : $query->whereNotIn('status', $finished);
    }

    /**
     * Filter by key: pending | scheduled | approved | rejected
     * Empty string / unknown key = no filter.
     */
    public function scopeStatusKey(Builder $query, ?string $key): Builder
    {
        $finished = array_merge(self::APPROVED_STATUSES, self::REJECTED_STATUSES);

        return match ($key) {
            'approved'  => $query->whereIn('status', self::APPROVED_STATUSES),
            'rejected'  => $query->whereIn('status', self::REJECTED_STATUSES),
            'scheduled' => $query->whereNotIn('status', $finished)->whereNotNull('interview_date'),
            'pending'   => $query->whereNotIn('status', $finished)->whereNull('interview_date'),
            default     => $query,
        };
    }

    // Search by name, email, position, or code (e.g. APP-00012)
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('fullname', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('position', 'like', "%{$term}%");

            if (preg_match('/(\d+)/', $term, $m)) {
                $q->orWhere('id', (int) $m[1]);
            }
        });
    }

    /* ---------- Accessors ---------- */

    // Display code, e.g. APP-00012
    public function getCodeAttribute(): string
    {
        return 'APP-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    // pending | scheduled | approved | rejected
    public function getStatusKeyAttribute(): string
    {
        if (in_array($this->status, self::APPROVED_STATUSES, true)) {
            return 'approved';
        }
        if (in_array($this->status, self::REJECTED_STATUSES, true)) {
            return 'rejected';
        }

        return $this->interview_date ? 'scheduled' : 'pending';
    }

    // Label shown on the badge / modal
    public function getWorkflowStatusAttribute(): string
    {
        return match ($this->status_key) {
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'scheduled' => 'Interview Scheduled',
            default     => 'Pending Review',
        };
    }

    // true once the application is approved or rejected (archived)
    public function getIsArchivedAttribute(): bool
    {
        return in_array($this->status_key, ['approved', 'rejected'], true);
    }

    // Interview date + time as one Carbon instance (null if not scheduled)
    public function getInterviewAtAttribute(): ?Carbon
    {
        if (! $this->interview_date) {
            return null;
        }

        $at = $this->interview_date->copy()->startOfDay();

        if ($this->interview_time) {
            try {
                $time = Carbon::parse($this->interview_time);
                $at->setTime($time->hour, $time->minute);
            } catch (\Throwable $e) {
                // unreadable time: keep start of day
            }
        }

        return $at;
    }

    // Admin/HR can decide only after the interview is done, and only once
    public function getCanDecideAttribute(): bool
    {
        if ($this->is_archived) {
            return false;
        }

        $at = $this->interview_at;

        return $at !== null && $at->lte(now());
    }

    /* ---------- Helpers ---------- */

    // Stored path of an uploaded document (null if none)
    public function documentPath(string $type): ?string
    {
        if (! array_key_exists($type, self::DOCUMENTS)) {
            return null;
        }

        return $this->getAttribute($type) ?: null;
    }
}