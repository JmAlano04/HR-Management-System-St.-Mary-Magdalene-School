<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    public const PENDING  = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';

    protected $fillable = [
        'employee_id', 'cutoff', 'transfer_date', 'step_name',
        'days_worked', 'hours_worked', 'late_minutes',
        'basic_pay', 'daily_wage', 'honorarium', 'gross_pay',
        'malasakitan', 'late_deduction', 'absences_deduction', 'sss', 'sss_loan', 'philhealth',
        'peraa_ceap', 'peraa_ceap_loan', 'hdmf', 'hdmf_loan', 'cash_advance',
        'total_deductions', 'net_pay',
        'sent_to_admin_at',
        'admin_status', 'admin_rejection_reason', 'admin_reviewed_at', 'admin_reviewed_by',
        'hr_status', 'hr_rejection_reason', 'hr_reviewed_at', 'hr_reviewed_by',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date'     => 'date',
            'sent_to_admin_at'  => 'datetime',
            'admin_reviewed_at' => 'datetime',
            'hr_reviewed_at'    => 'datetime',
            'released_at'       => 'datetime',
            'days_worked'       => 'float',
            'hours_worked'      => 'float',
            'basic_pay'         => 'float',
            'daily_wage'        => 'float',
            'honorarium'        => 'float',
            'gross_pay'         => 'float',
            'total_deductions'  => 'float',
            'net_pay'           => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Payslips Accounting has already transferred to Admin. */
    public function scopeSentToAdmin($query)
    {
        return $query->whereNotNull('sent_to_admin_at');
    }

    /** Everything the payslip modal needs. */
    public function toCard(): array
    {
        $e = $this->employee;

        return [
            'id'              => $this->id,
            'employee_name'   => $e?->name ?? 'Unknown employee',
            'employee_number' => $e?->employee_id ?? '-',
            'department'      => $e ? (Employee::DEPARTMENTS[$e->department] ?? $e->department) : '-',
            'position'        => $e?->position ?? '-',
            'step_name'       => $this->step_name ?: '-',
            'days_worked'     => $this->days_worked,
            'hours_worked'    => $this->hours_worked,
            'late_minutes'    => $this->late_minutes,
            'cutoff'          => $this->cutoff,
            'basic_pay'       => $this->basic_pay,
            'daily_wage'      => $this->daily_wage,
            'honorarium'      => $this->honorarium,
            'gross_pay'       => $this->gross_pay,
            'deductions'      => [
                'malasakitan'     => (float) $this->malasakitan,
                'late'            => (float) $this->late_deduction,
                'absences'        => (float) $this->absences_deduction,
                'sss'             => (float) $this->sss,
                'sss_loan'        => (float) $this->sss_loan,
                'philhealth'      => (float) $this->philhealth,
                'peraa_ceap'      => (float) $this->peraa_ceap,
                'peraa_ceap_loan' => (float) $this->peraa_ceap_loan,
                'hdmf'            => (float) $this->hdmf,
                'hdmf_loan'       => (float) $this->hdmf_loan,
                'cash_advance'    => (float) $this->cash_advance,
            ],
            'total_deductions' => $this->total_deductions,
            'net_pay'          => $this->net_pay,
        ];
    }
}