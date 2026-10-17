<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    public const DEPARTMENTS = [
        'elementary' => 'Elementary',
        'highschool' => 'High School',
        'seniorhigh' => 'Senior High School',
    ];

    public const ROLE_TYPES = [
        'teaching'    => 'Teaching',
        'nonteaching' => 'Non-Teaching',
    ];

    protected $fillable = [
        'user_id', 'employee_id', 'name', 'position', 'department', 'role_type',
        'email', 'birthday', 'address', 'emergency_name', 'emergency_contact',
    ];

    protected function casts(): array
    {
        return [
            'birthday'          => 'date',

            // AES-256 (encrypted automatically on save, decrypted on read)
            'address'           => 'encrypted',
            'emergency_name'    => 'encrypted',
            'emergency_contact' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "Maria Santos" -> "MS" */
    protected function initials(): Attribute
    {
        return Attribute::get(fn () => collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->take(2)
            ->implode(''));
    }

    /** Data used by the details modal and the edit form. */
    public function toCard(): array
    {
        return [
            'id'                => $this->id,
            'employee_id'       => $this->employee_id,
            'name'              => $this->name,
            'initials'          => $this->initials,
            'position'          => $this->position,
            'department'        => $this->department,
            'department_label'  => self::DEPARTMENTS[$this->department] ?? $this->department,
            'role_type'         => $this->role_type,
            'role_label'        => self::ROLE_TYPES[$this->role_type] ?? $this->role_type,
            'email'             => $this->email,
            'birthday'          => $this->birthday->toDateString(),
            'birthday_label'    => $this->birthday->format('F j, Y'),
            'address'           => $this->address,
            'emergency_name'    => $this->emergency_name,
            'emergency_contact' => $this->emergency_contact,
        ];
    }
}