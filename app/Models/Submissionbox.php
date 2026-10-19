<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionBox extends Model
{
    protected $fillable = ['name', 'type', 'deadline', 'instructions', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'deadline'  => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Box data + the employees who uploaded to it (grouped), used by the modals. */
    public function toCard(): array
    {
        $employees = $this->documents
            ->groupBy('employee_id')
            ->map(function ($docs) {
                $employee = $docs->first()->employee;
                $name     = $employee?->name ?? 'Employee';

                return [
                    'id'        => (int) $docs->first()->employee_id,
                    'name'      => $name,
                    'number'    => $employee?->employee_id ?? 'Employee ID unavailable',
                    'initials'  => mb_strtoupper(mb_substr($name, 0, 2)),
                    'documents' => $docs->map->toCard()->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'type'         => $this->type,
            'deadline'     => $this->deadline?->format('M j, Y'),
            'instructions' => $this->instructions,
            'active'       => $this->is_active,
            'employees'    => $employees,
        ];
    }
}