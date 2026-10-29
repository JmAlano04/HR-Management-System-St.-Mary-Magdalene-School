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

    /**
     * Education levels used by the My Account form, its validation and the view.
     * [field key => [label, placeholder, max length]]
     */
    public const EDUCATION_LEVELS = [
        'tertiary' => [
            'label'  => 'Tertiary',
            'fields' => [
                'course' => ['Course', 'e.g., Bachelor of Science in Information Technology', 160],
                'school' => ['School Name', 'University / College Name', 160],
                'years'  => ['Years Attended', 'e.g., 2020 - 2024', 24],
            ],
        ],
        'senior_high' => [
            'label'  => 'Senior High School',
            'fields' => [
                'strand' => ['Strand / Track', 'e.g., STEM / TVL / General Academic', 120],
                'school' => ['School Name', 'Senior High School Name', 160],
                'years'  => ['Years Attended', 'e.g., 2018 - 2020', 24],
            ],
        ],
        'junior_high' => [
            'label'  => 'Junior High School',
            'fields' => [
                'school' => ['School Name', 'Junior High School Name', 160],
                'years'  => ['Years Attended', 'e.g., 2014 - 2018', 24],
            ],
        ],
        'primary' => [
            'label'  => 'Primary',
            'fields' => [
                'school' => ['School Name', 'Elementary School Name', 160],
                'years'  => ['Years Attended', 'e.g., 2008 - 2014', 24],
            ],
        ],
    ];

    protected $fillable = [
        'user_id', 'employee_id', 'name', 'position', 'department', 'role_type',
        'email', 'birthday', 'address', 'emergency_name', 'emergency_contact',

        // Nasa database pero wala dati sa fillable.
        // Ang employment_status ay NOT NULL at walang default sa database.
        'nickname', 'employment_status',

        // My Account
        'gender', 'civil_status', 'phone', 'education',
        'sss_number', 'philhealth_number', 'pagibig_number', 'tin',
    ];

    protected function casts(): array
    {
        return [
            'birthday'          => 'date',
            'education'         => 'array',

            // AES-256 (encrypted automatically on save, decrypted on read)
            'address'           => 'encrypted',
            'emergency_name'    => 'encrypted',
            'emergency_contact' => 'encrypted',
            'phone'             => 'encrypted',
            'sss_number'        => 'encrypted',
            'philhealth_number' => 'encrypted',
            'pagibig_number'    => 'encrypted',
            'tin'               => 'encrypted',
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