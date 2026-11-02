<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;

/**
 * ONE place for every payslip figure. Employee, Accounting (Salary Monitoring / Salary Records),
 * Admin, HR and the PDF all read their numbers from here, so they can never disagree.
 *
 * Rules (same as PayrollProcessingController):
 *  - monthly wage = daily wage x 26
 *  - HDMF = fixed 200 on every pay period
 *  - honorarium = 1,924.31 for the System Administrator only
 *  - SSS, PhilHealth, PERAA/CEAP and the loans are deducted on the 30th pay period only
 *  - gross / total deductions / net pay are recomputed from the saved columns
 */
final class PayslipPresenter
{
    public const MONTHLY_DAYS = 26;
    public const HDMF         = 200.00;
    public const HONORARIUM   = 1924.31;

    /** Pay period => attendance cutoff day. */
    public const CUTOFF_DAY = ['15th' => '10th', '30th' => '25th'];

    /** Deducted on the 30th pay period only (0 / "-" on the 15th). */
    public const ONLY_30TH = [
        'sss', 'philhealth', 'peraa_ceap',
        'sss_loan', 'philhealth_loan', 'peraa_ceap_loan', 'hdmf_loan',
    ];

    /** Deduction key => label, in the order shown on every payslip. */
    public const DEDUCTION_LABELS = [
        'malasakitan'     => 'MALASAKITAN',
        'late'            => 'Late',
        'absences'        => 'Absences',
        'sss'             => 'SSS',
        'philhealth'      => 'PhilHealth',
        'hdmf'            => 'HDMF',
        'peraa_ceap'      => 'PERAA/CEAP',
        'sss_loan'        => 'SSS Loan',
        'philhealth_loan' => 'PhilHealth Loan',
        'peraa_ceap_loan' => 'PERAA/CEAP Loan',
        'hdmf_loan'       => 'HDMF Loan',
        'cash_advance'    => 'Cash advance',
    ];

    /** Full payslip data used by every popup / list / PDF. */
    public static function card(Payslip $p): array
    {
        $e   = $p->employee;
        $f   = self::figures($p);
        $key = self::cutoffKey($p->cutoff);

        return [
            'id'              => $p->id,
            'employee_name'   => $e?->name ?? 'Unknown employee',
            'employee_number' => $e?->employee_id ?? '-',
            'department'      => $e ? (Employee::DEPARTMENTS[$e->department] ?? $e->department) : '-',
            'position'        => $e?->position ?: '-',
            'step_name'       => $p->step_name ?: 'Unassigned',

            'cutoff'          => $key ?? (string) $p->cutoff,
            'cutoff_label'    => self::payPeriodLabel($p->cutoff),
            'cutoff_day'      => self::CUTOFF_DAY[$key] ?? '',
            'period'          => self::periodLabel($p),
            'transfer_date'   => $p->transfer_date ? Carbon::parse($p->transfer_date)->format('M d, Y') : null,

            'days_worked'     => (float) $p->days_worked,
            'hours_worked'    => (float) $p->hours_worked,
            'late_minutes'    => (int) $p->late_minutes,
            'monthly_wage'    => round((float) $p->daily_wage * self::MONTHLY_DAYS, 2),

            'basic_pay'       => (float) $p->basic_pay,
            'daily_wage'      => (float) $p->daily_wage,
            'honorarium'      => $f['honorarium'],
            'gross_pay'       => $f['gross_pay'],

            'deductions'       => $f['deductions'],
            'total_deductions' => $f['total_deductions'],
            'net_pay'          => $f['net_pay'],
        ];
    }

    /** Honorarium, gross, deductions, total and net pay. */
    public static function figures(Payslip $p): array
    {
        $is30       = self::cutoffKey($p->cutoff) === '30th';
        $honorarium = ($p->employee && self::isAdministrative($p->employee)) ? self::HONORARIUM : 0.00;
        $gross      = round((float) $p->basic_pay + $honorarium, 2);

        $amount = fn (string $k, $v) => (! $is30 && in_array($k, self::ONLY_30TH, true)) ? 0.0 : (float) $v;

        $deductions = [
            'malasakitan'     => (float) $p->malasakitan,
            'late'            => (float) $p->late_deduction,
            'absences'        => (float) $p->absences_deduction,
            'sss'             => $amount('sss', $p->sss),
            'philhealth'      => $amount('philhealth', $p->philhealth),
            'hdmf'            => self::HDMF,
            'peraa_ceap'      => $amount('peraa_ceap', $p->peraa_ceap),
            'sss_loan'        => $amount('sss_loan', $p->sss_loan),
            'philhealth_loan' => $amount('philhealth_loan', $p->philhealth_loan ?? 0),
            'peraa_ceap_loan' => $amount('peraa_ceap_loan', $p->peraa_ceap_loan),
            'hdmf_loan'       => $amount('hdmf_loan', $p->hdmf_loan),
            'cash_advance'    => (float) $p->cash_advance,
        ];

        // Round each line first, then add the ROUNDED lines (same as PayrollProcessingController),
        // so the total is always exactly the sum of the rows shown on the payslip.
        $deductions = array_map(fn ($v) => round((float) $v, 2), $deductions);
        $total      = round(array_sum($deductions), 2);

        return [
            'honorarium'       => $honorarium,
            'gross_pay'        => $gross,
            'deductions'       => $deductions,
            'total_deductions' => $total,
            'net_pay'          => round($gross - $total, 2),
        ];
    }

    /**
     * Only the System Administrator gets the fixed honorarium.
     * In this system that is the account holding the Spatie "Admin" role (not HR, Accounting
     * or Employee), so it is decided by role and not by text typed in the employee record.
     */
    public static function isAdministrative(Employee $e): bool
    {
        $user = method_exists($e, 'user') ? $e->user : null;

        return $user instanceof User && $user->hasRole('Admin');
    }

    /** "15th", "30th Cutoff", "15" ... => '15th' | '30th' | null */
    public static function cutoffKey(?string $cutoff): ?string
    {
        return preg_match('/(15|30)/', (string) $cutoff, $m) ? $m[1] . 'th' : null;
    }

    public static function payPeriodLabel(?string $cutoff): string
    {
        $key = self::cutoffKey($cutoff);

        return $key ? $key . ' Pay Period' : (trim((string) $cutoff) ?: '-');
    }

    /** Month of the payroll run, e.g. "October 2026". */
    public static function periodLabel(Payslip $p): string
    {
        return $p->transfer_date ? Carbon::parse($p->transfer_date)->format('F Y') : '';
    }
}