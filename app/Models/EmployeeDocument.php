<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    protected $fillable = [
        'submission_box_id', 'employee_id', 'name', 'type',
        'file_path', 'mime_type', 'size_bytes',
        'status', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(SubmissionBox::class, 'submission_box_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** 245760 -> "240 KB", 3145728 -> "3 MB" */
    public function getSizeLabelAttribute(): string
    {
        if (! $this->size_bytes) {
            return 'Unknown size';
        }

        return $this->size_bytes >= 1048576
            ? round($this->size_bytes / 1048576, 1) . ' MB'
            : max(1, (int) round($this->size_bytes / 1024)) . ' KB';
    }

    public function toCard(): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'type'            => $this->type,
            'size'            => $this->size_label,
            'uploaded'        => $this->created_at->format('M j, Y'),
            'status'          => $this->status,
            'employee_name'   => $this->employee?->name ?? 'Employee',
            'employee_number' => $this->employee?->employee_id ?? 'Employee ID unavailable',
            'file_url'        => $this->file_path ? route('documents.file', $this) : null,
        ];
    }
}