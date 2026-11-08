<?php

namespace App\Models;

use App\Casts\EncryptedOrPlaintext;
use App\Models\Concerns\ExcludesInternalStaff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SchoolEmployee extends Model
{
    use ExcludesInternalStaff;

    protected $table = 'employees'; // existing table (2026_10_17_102132_create_employee_table)

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

        // employment_status is NOT NULL (no default) in the database
        'employment_status',
    ];

    protected function casts(): array
    {
        return [
            'birthday'          => 'date',

            'address'           => EncryptedOrPlaintext::class,
            'emergency_name'    => EncryptedOrPlaintext::class,
            'emergency_contact' => EncryptedOrPlaintext::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Departments from SQL ---------- */

    /** Distinct departments stored in the database: [value => label]. */
    public static function departmentsInDatabase(): Collection
    {
        return static::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->mapWithKeys(fn ($value) => [
                $value => self::DEPARTMENTS[$value] ?? Str::headline($value),
            ]);
    }

    /** Distinct role types stored in the database: [value => label]. */
    public static function roleTypesInDatabase(): Collection
    {
        return static::query()
            ->whereNotNull('role_type')
            ->where('role_type', '!=', '')
            ->distinct()
            ->orderBy('role_type')
            ->pluck('role_type')
            ->mapWithKeys(fn ($value) => [
                $value => self::ROLE_TYPES[$value] ?? match ($value) {
                    'teaching' => 'Teaching',
                    'nonteaching' => 'Non-Teaching',
                    default => Str::headline($value),
                },
            ]);
    }

    /** Standard departments + any extra value already stored in SQL. */
    public static function departmentOptions(): Collection
    {
        return collect(self::DEPARTMENTS)->union(static::departmentsInDatabase());
    }

    /* ---------- Filters (run in the database) ---------- */

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $search = $filters['search'] ?? null;
        $search = is_string($search) ? trim($search) : '';
        $department = $filters['department'] ?? null;
        $department = is_string($department) ? $department : '';
        $roleType = $filters['role_type'] ?? null;
        $roleType = is_string($roleType) ? $roleType : '';
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)) . '%';

        return $query
            ->when($search !== '', function ($q) use ($pattern) {
                $q->where(function ($q) use ($pattern) {
                    $q->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
                      ->orWhereRaw("LOWER(employee_id) LIKE ? ESCAPE '!'", [$pattern])
                      ->orWhereRaw("LOWER(position) LIKE ? ESCAPE '!'", [$pattern]);
                });
            })
            // Department must be a value that exists in SQL
            ->when(
                $department !== '' && static::departmentsInDatabase()->has($department),
                fn ($q) => $q->where('department', $department)
            )
            ->when(
                $roleType !== '' && static::roleTypesInDatabase()->has($roleType),
                fn ($q) => $q->where('role_type', $roleType)
            );
    }

    /* ---------- Accessors ---------- */

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);
        $first = Str::substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? Str::substr(end($parts), 0, 1) : '';

        return Str::upper($first . $last);
    }

    public function getDepartmentLabelAttribute(): string
    {
        return self::DEPARTMENTS[$this->department] ?? Str::headline((string) $this->department);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_TYPES[$this->role_type] ?? Str::headline((string) $this->role_type);
    }

    /* ---------- Masking: sensitive data is hidden before it reaches the browser ---------- */

    public function getMaskedEmailAttribute(): string
    {
        $email = (string) $this->email;
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, null);

        return $domain === null
            ? $email
            : Str::substr($local, 0, 1) . str_repeat('*', max(0, Str::length($local) - 1)) . '@' . $domain;
    }

    public function getMaskedBirthdayAttribute(): string
    {
        if (! $this->birthday) {
            return '';
        }

        // May 12, 19**
        return $this->birthday->format('F j, ') . substr((string) $this->birthday->year, 0, 2) . '**';
    }

    public function getMaskedEmergencyContactAttribute(): string
    {
        $contact = (string) $this->emergency_contact;
        $total   = preg_match_all('/\d/', $contact);
        $seen    = 0;
        $out     = '';

        foreach (mb_str_split($contact) as $char) {
            if (! ctype_digit($char)) {
                $out .= $char;
                continue;
            }
            $seen++;
            $out .= ($total - $seen) < 4 ? '*' : $char;   // hide last 4 digits
        }

        return $out;
    }
}