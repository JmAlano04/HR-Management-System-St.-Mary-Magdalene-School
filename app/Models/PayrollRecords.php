<?php

namespace App\Models;

use App\Support\PayrollCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRecords extends Model
{
    public const CUTOFFS = ['15th', '30th'];

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_TRANSFERRED = 'transferred';

    /** Columns Accounting may edit (used for validation and for the JSON the page receives). */
    public const EDITABLE = [
        'step_no', 'days_worked', 'hours_worked', 'late_minutes', 'honorarium',
        'sss', 'sss_loan', 'philhealth', 'peraa_ceap', 'peraa_ceap_loan',
        'hdmf', 'hdmf_loan', 'cash_advance',
    ];

    protected $fillable = [
        'period', 'cutoff',
        'employee_id', 'employee_number', 'employee_name', 'department', 'position',
        ...self::EDITABLE,
        'status',
        'step_name', 'daily_wage', 'basic_pay', 'gross_pay', 'total_deductions', 'net_pay',
        'deduction_breakdown', 'transferred_at', 'transferred_by',
    ];

    protected $casts = [
        'step_no'             => 'integer',
        'days_worked'         => 'float',
        'hours_worked'        => 'float',
        'late_minutes'        => 'float',
        'honorarium'          => 'float',
        'sss'                 => 'float',
        'sss_loan'            => 'float',
        'philhealth'          => 'float',
        'peraa_ceap'          => 'float',
        'peraa_ceap_loan'     => 'float',
        'hdmf'                => 'float',
        'hdmf_loan'           => 'float',
        'cash_advance'        => 'float',
        'daily_wage'          => 'float',
        'basic_pay'           => 'float',
        'gross_pay'           => 'float',
        'total_deductions'    => 'float',
        'net_pay'             => 'float',
        'deduction_breakdown' => 'array',
        'transferred_at'      => 'datetime',
    ];

    public function transferrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Compute this record's payroll with the given step (null = unassigned).
     */
    public function calculate(?PayrollStep $step = null): array
    {
        return PayrollCalculator::calculate(
            $this->only(self::EDITABLE),
            $step ? ['name' => $step->name, 'daily_wage' => $step->daily_wage] : null,
            $this->cutoff,
        );
    }

    /** Shape consumed by the Alpine component on the page. */
    public function toRow(): array
    {
        return [
            'id'              => $this->id,
            'cutoff'          => $this->cutoff,
            'employee_number' => $this->employee_number,
            'name'            => $this->employee_name,
            'department'      => $this->department,
            'position'        => $this->position,
            'status'          => $this->status,
        ] + $this->only(self::EDITABLE);
    }
}