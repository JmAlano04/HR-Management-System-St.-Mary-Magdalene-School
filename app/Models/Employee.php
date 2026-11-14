<?php

namespace App\Models;

use App\Casts\EncryptedOrPlaintext;
use App\Models\Concerns\ExcludesInternalStaff;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use ExcludesInternalStaff;

    public const DEPARTMENTS = [
        'elementary' => 'Elementary',
        'highschool' => 'High School',
        'seniorhigh' => 'Senior High School',
    ];

    public const ROLE_TYPES = [
        'teaching'    => 'Teaching',
        'nonteaching' => 'Non-Teaching',
    ];

    /** Options for the Civil Status and Gender dropdowns (also used by validation). */
    public const CIVIL_STATUSES = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];

    public const GENDERS = ['Male', 'Female', 'Other'];

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
        'user_id', 'employee_id', 'zkteco_user_id', 'name', 'position', 'department', 'role_type',
        'email', 'birthday', 'address', 'emergency_name', 'emergency_contact',

        // Name parts (the `name` column is rebuilt from these on every save)
        'first_name', 'middle_name', 'last_name', 'suffix',

        // Nasa database pero wala dati sa fillable.
        // Ang employment_status ay NOT NULL at walang default sa database.
        'nickname', 'employment_status',  'step_no',

        // My Account (gender at civil_status ay nasa fillable na)
        'gender', 'civil_status', 'phone', 'education',
        'sss_number', 'philhealth_number', 'pagibig_number', 'tin',
    ];

    protected static function booted(): void
    {
        // Keep the full `name` column in sync with first/middle/last/suffix.
        // Rows that only have `name` (no parts yet) are left untouched.
        static::saving(function (Employee $employee) {
            if (filled($employee->first_name) || filled($employee->last_name)) {
                $employee->name = $employee->composeName();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'birthday'          => 'date',
            'education'         => 'array',

            'address'           => EncryptedOrPlaintext::class,
            'emergency_name'    => EncryptedOrPlaintext::class,
            'emergency_contact' => EncryptedOrPlaintext::class,
            'phone'             => EncryptedOrPlaintext::class,
            'sss_number'        => EncryptedOrPlaintext::class,
            'philhealth_number' => EncryptedOrPlaintext::class,
            'pagibig_number'    => EncryptedOrPlaintext::class,
            'tin'               => EncryptedOrPlaintext::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "First Middle Last Suffix" built from the separate columns. */
    public function composeName(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->implode(' ');
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
            'zkteco_user_id'    => $this->zkteco_user_id,
            'name'              => $this->name,
            'first_name'        => $this->first_name,
            'middle_name'       => $this->middle_name,
            'last_name'         => $this->last_name,
            'suffix'            => $this->suffix,
            'initials'          => $this->initials,
            'position'          => $this->position,
            'department'        => $this->department,
            'department_label'  => self::DEPARTMENTS[$this->department] ?? $this->department,
            'role_type'         => $this->role_type,
            'role_label'        => self::ROLE_TYPES[$this->role_type] ?? $this->role_type,
            'employment_status' => $this->employment_status,
            'step_no'           => $this->step_no,
            'email'             => $this->email,
            'birthday'          => $this->birthday->toDateString(),
            'birthday_label'    => $this->birthday->format('F j, Y'),
            'civil_status'      => $this->civil_status,
            'gender'            => $this->gender,
            'address'           => $this->address,
            'emergency_name'    => $this->emergency_name,
            'emergency_contact' => $this->emergency_contact,
        ];
    }
}