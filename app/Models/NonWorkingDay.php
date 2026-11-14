<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonWorkingDay extends Model
{
    public const CATEGORIES = ['National Holiday', 'Local Holiday', 'School Suspension'];

    public const PAID   = 'paid';
    public const UNPAID = 'unpaid';

    protected $fillable = [
        'date',
        'name',
        'category',
        'pay_type',
        'paid_days',
        'paid_hours',
    ];

    protected $casts = [
        'date'       => 'date:Y-m-d',
        'paid_days'  => 'decimal:2',
        'paid_hours' => 'decimal:2',
    ];

    public function isPaid(): bool
    {
        return $this->pay_type === self::PAID;
    }
}