<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollStep extends Model
{
    protected $fillable = ['step_no', 'name', 'daily_wage'];

    protected $casts = [
        'step_no'    => 'integer',
        'daily_wage' => 'float',
    ];

    /** Default daily wage for Step 1..30 (same table as the original page). */
    public const DEFAULT_WAGES = [
        550.00, 577.50, 606.38, 636.70, 668.53, 701.96, 737.06, 773.91, 812.60, 853.23,
        895.89, 940.69, 987.72, 1037.11, 1088.96, 1142.13, 1199.24, 1259.20, 1322.16, 1388.27,
        1457.68, 1530.56, 1607.09, 1687.45, 1771.82, 1860.41, 1953.43, 2051.10, 2153.66, 2261.34,
    ];

    /** Create the 30 default steps if the table is empty. */
    public static function ensureDefaults(): void
    {
        if (static::query()->exists()) {
            return;
        }

        foreach (self::DEFAULT_WAGES as $index => $wage) {
            static::create([
                'step_no'    => $index + 1,
                'name'       => 'Step ' . ($index + 1),
                'daily_wage' => $wage,
            ]);
        }
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('step_no');
    }
}