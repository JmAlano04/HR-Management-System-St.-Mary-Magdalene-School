<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'user_id', 'fullname', 'email', 'phone', 'home_address', 'birth_date',
        'gender', 'civil_status', 'education', 'job_type', 'position',
        'resume', 'coverletter', 'appletter',
        'status', 'rejection_reason',
        'interview_date', 'interview_time', 'interview_location',
    ];

    protected function casts(): array
    {
        return [
            'birth_date'     => 'date',
            'interview_date' => 'date',

            // AES-256 (Laravel encrypted cast). These columns must be TEXT.
            'phone'          => 'encrypted',
            'home_address'   => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}