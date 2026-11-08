<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;

/**
 * Builds the payslip popup data, with the same fields and figures as the
 * Payroll Processing payslip. Place at: app/Support/PayslipCard.php
 *
 * Fixed amounts (same as PayrollProcessingController):
 *   - HDMF       = P200.00 on every payslip
 *   - Honorarium = P1,924.31 for the administrative role, P0 for everyone else
 * Gross pay, total deductions and net pay are recomputed from the saved columns.
 *
 * Load the payslips with ->with('employee.user') to avoid one query per payslip.
 */
class PayslipCard
{
    public const HDMF         = 200.00;
    public const HONORARIUM   = 1924.31;
    public const MONTHLY_DAYS = 26;   // monthly wage = daily wage x 26

    /** Pay period => attendance cutoff day. */
    private const CUTOFF_DAY = ['15th' => '10th', '30th' => '25th'];

    public static function make(Payslip $p): array
    {
        $e = $p->employee;

        $honorarium = ($e && self::isAdministrative($e)) ? self::HONORARIUM : 0.00;
        $gross      = round((float) $p->basic_pay + $honorarium, 2);

        $deductions = [
            'malasakitan'     => (float) $p->malasakitan,
            'late'            => (float) $p->late_deduction,
            'absences'        => (float) $p->absences_deduction,
            'sss'             => (float) $p->sss,
            'philhealth'      => (float) $p->philhealth,
            'hdmf'            => self::HDMF,
            'peraa_ceap'      => (float) $p->peraa_ceap,
            'sss_loan'        => (float) $p->sss_loan,
            'philhealth_loan' => (float) ($p->philhealth_loan ?? 0),
            'peraa_ceap_loan' => (float) $p->peraa_ceap_loan,
            'hdmf_loan'       => (float) $p->hdmf_loan,
            'cash_advance'    => (float) $p->cash_advance,
        ];
        $total = round(array_sum($deductions), 2);

        return [
            'id'              => $p->id,
            'employee_name'   => $e?->name ?? 'Unknown employee',
            'employee_number' => $e?->employee_id ?? '-',
            'department'      => $e ? (Employee::DEPARTMENTS[$e->department] ?? $e->department) : '-',
            'position'        => $e?->position ?: '-',
            'step_name'       => $p->step_name ?: 'Unassigned',

            'cutoff'          => $p->cutoff,
            'cutoff_label'    => $p->cutoff . ' Pay Period',
            'cutoff_day'      => self::CUTOFF_DAY[$p->cutoff] ?? '',
            'period'          => self::periodLabel($p),

            'days_worked'     => (float) $p->days_worked,
            'hours_worked'    => (float) $p->hours_worked,
            'late_minutes'    => (int) $p->late_minutes,
            'monthly_wage'    => round((float) $p->daily_wage * self::MONTHLY_DAYS, 2),

            'basic_pay'       => (float) $p->basic_pay,
            'daily_wage'      => (float) $p->daily_wage,
            'honorarium'      => $honorarium,
            'gross_pay'       => $gross,

            'deductions'       => $deductions,
            'total_deductions' => $total,
            'net_pay'          => round($gross - $total, 2),
        ];
    }

    /** Month of the payroll run, e.g. "October 2026". */
    public static function periodLabel(Payslip $p): string
    {
        $date = $p->transfer_date ?? $p->created_at;

        return $date ? Carbon::parse($date)->format('F Y') : '';
    }

    /** Same rule as PayrollProcessingController::isAdministrative(). */
    private static function isAdministrative(Employee $e): bool
    {
        $isAdmin = fn ($v) => is_string($v) && str_contains(strtolower($v), 'administrative');

        foreach ($e->getAttributes() as $value) {
            if ($isAdmin($value)) {
                return true;
            }
        }
        if ($isAdmin(Employee::DEPARTMENTS[$e->department] ?? null)) {
            return true;
        }

        $user = method_exists($e, 'user') ? $e->user : null;

        return $user instanceof User
            && $user->getRoleNames()->contains(fn ($role) => $isAdmin($role));
    }
}