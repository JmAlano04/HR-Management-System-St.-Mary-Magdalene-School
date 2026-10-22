<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SchoolEmployee extends Model
{
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
    ];

    protected $casts = [
        'birthday'          => 'date',
        'address'           => 'encrypted',   // AES-256 via APP_KEY
        'emergency_contact' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ---------- Filters (run in the database) ---------- */

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('employee_id', 'like', "%{$term}%")
                      ->orWhere('position', 'like', "%{$term}%");
                });
            })
            ->when($filters['department'] ?? null, fn ($q, $v) => $q->where('department', $v))
            ->when($filters['role_type'] ?? null, fn ($q, $v) => $q->where('role_type', $v));
    }

    /* ---------- Accessors ---------- */

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = Str::substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? Str::substr(end($parts), 0, 1) : '';

        return Str::upper($first . $last);
    }

    public function getDepartmentLabelAttribute(): string
    {
        return self::DEPARTMENTS[$this->department] ?? $this->department;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_TYPES[$this->role_type] ?? $this->role_type;
    }

    /* ---------- Masking: sensitive data is hidden before it reaches the browser ---------- */

    public function getMaskedEmailAttribute(): string
    {
        [$local, $domain] = array_pad(explode('@', $this->email, 2), 2, null);

        return $domain === null
            ? $this->email
            : Str::substr($local, 0, 1) . str_repeat('*', max(0, Str::length($local) - 1)) . '@' . $domain;
    }

    public function getMaskedBirthdayAttribute(): string
    {
        // May 12, 19**
        return $this->birthday->format('F j, ') . substr((string) $this->birthday->year, 0, 2) . '**';
    }

    public function getMaskedEmergencyContactAttribute(): string
    {
        $total = preg_match_all('/\d/', $this->emergency_contact);
        $seen  = 0;
        $out   = '';

        foreach (str_split($this->emergency_contact) as $char) {
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