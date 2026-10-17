<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NOTE: kung meron ka nang app/Models/Application.php, i-merge lang ang
 * mga casts, constants, scopes, at accessors dito — huwag palitan ang buong file
 * kung may ibang laman ang sa iyo.
 */
class Application extends Model
{
    // Saang disk naka-save ang resume / cover letter / application letter.
    // Palitan ng 'public' kung doon mo sinave.
    public const DOCUMENT_DISK = 'local';

    // route type => [label, column]
    public const DOCUMENTS = [
        'resume'             => ['label' => 'Resume',             'column' => 'resume'],
        'cover_letter'       => ['label' => 'Cover letter',       'column' => 'coverletter'],
        'application_letter' => ['label' => 'Application letter', 'column' => 'appletter'],
    ];

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
        'phone'          => 'encrypted',   // AES-256, kaya TEXT ang column
        'home_address'   => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $q) use ($term) {
            $like = "%{$term}%";

            $q->where(function (Builder $w) use ($like, $term) {
                $w->where('fullname', 'like', $like)
                    ->orWhere('email', 'like', $like);

                // Search by Applicant ID, e.g. "APP-0012" or "app12"
                if (preg_match('/^app-?0*(\d+)$/i', $term, $m)) {
                    $w->orWhere('id', (int) $m[1]);
                }
            });
        });
    }

    /**
     * Filter by workflow key: pending | scheduled | approved | rejected
     */
    public function scopeStatusKey(Builder $query, ?string $key): Builder
    {
        return match ($key) {
            'approved'  => $query->where('status', 'qualified'),
            'rejected'  => $query->where('status', 'rejected'),
            // pending + may interview date (Interview Scheduled / Awaiting Admin Review)
            'scheduled' => $query->where('status', 'pending')->whereNotNull('interview_date'),
            // pending + wala pang interview date (Under Review)
            'pending'   => $query->where('status', 'pending')->whereNull('interview_date'),
            default     => $query,
        };
    }

    /* ---------- Accessors ---------- */

    // Applicant ID na ipinapakita: APP-0001
    public function getCodeAttribute(): string
    {
        return sprintf('APP-%04d', $this->id);
    }

    // Natapos na ang interview kapag dumating o lumipas na ang interview date
    public function getInterviewCompletedAttribute(): bool
    {
        return $this->interview_date !== null && $this->interview_date->lte(today());
    }

    // Label sa Status column
    public function getWorkflowStatusAttribute(): string
    {
        return match (true) {
            $this->status === 'qualified'      => 'Approved',
            $this->status === 'rejected'       => 'Rejected',
            $this->interview_date === null     => 'Under Review',
            $this->interview_completed         => 'Awaiting Admin Review',
            default                            => 'Interview Scheduled',
        };
    }

    // pending | scheduled | approved | rejected
    public function getStatusKeyAttribute(): string
    {
        return match ($this->workflow_status) {
            'Approved'                                     => 'approved',
            'Rejected'                                     => 'rejected',
            'Interview Scheduled', 'Awaiting Admin Review' => 'scheduled',
            default                                        => 'pending',
        };
    }

    // Puwedeng mag-approve/reject ang Admin kapag tapos na ang interview at wala pang desisyon
    public function getCanDecideAttribute(): bool
    {
        return $this->status === 'pending' && $this->interview_completed;
    }

    public function documentPath(string $type): ?string
    {
        $column = self::DOCUMENTS[$type]['column'] ?? null;

        return $column ? ($this->{$column} ?: null) : null;
    }
}