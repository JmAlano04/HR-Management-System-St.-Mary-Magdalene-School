<?php

namespace App\Support;

/**
 * Pure payroll math: a PHP port of getEmployeePayroll() / getDeductionBreakdown()
 * from the original PAYROLLPROCESSINGScript.js. No database access here, so it is
 * easy to test and is the single source of truth on the server.
 *
 * The same formulas are mirrored in the Alpine component in the Blade view
 * (for the live preview). If you change a rule, change both.
 */
class PayrollCalculator
{
    /** Scheduled workdays used to compute absences (same value as the original script). */
    public const SCHEDULED_WORKDAYS = 22;

    public const FULL_DAY_HOURS = 8;

    /** Pesos deducted per late minute. */
    public const LATE_RATE_PER_MINUTE = 1.80;

    /** Fixed "MALASAKITAN" deduction, every cutoff. */
    public const MALASAKITAN = 50.0;

    /** Deductions that are only taken on the 30th cutoff. */
    public const SECOND_CUTOFF_ONLY = [
        'sss', 'sss_loan', 'philhealth', 'peraa_ceap', 'peraa_ceap_loan', 'hdmf', 'hdmf_loan',
    ];

    /**
     * @param  array{days_worked?:mixed,hours_worked?:mixed,late_minutes?:mixed,honorarium?:mixed,
     *               sss?:mixed,sss_loan?:mixed,philhealth?:mixed,peraa_ceap?:mixed,
     *               peraa_ceap_loan?:mixed,hdmf?:mixed,hdmf_loan?:mixed,cash_advance?:mixed}  $in
     * @param  array{name:string,daily_wage:float|int|string}|null  $step  null = "Unassigned" (wage 0)
     * @param  string  $cutoff  "15th" or "30th"
     */
    public static function calculate(array $in, ?array $step, string $cutoff): array
    {
        $num = static fn (string $key): float => max(0.0, (float) ($in[$key] ?? 0));

        $stepName = $step['name'] ?? 'Unassigned';
        $wage = $step ? max(0.0, (float) $step['daily_wage']) : 0.0;

        $days  = $num('days_worked');
        $hours = $num('hours_worked');

        // Absences: days short of the scheduled workdays, at the full daily wage
        $absences = max(0.0, self::SCHEDULED_WORKDAYS - $days) * $wage;

        // Late: minutes, capped by the minutes actually missing (if any are missing)
        $missingMinutes = max(0.0, $days * self::FULL_DAY_HOURS * 60 - $hours * 60);
        $lateMinutes    = $num('late_minutes');
        $effectiveLate  = max(0.0, min($lateMinutes, $missingMinutes > 0 ? $missingMinutes : $lateMinutes));
        $late           = $effectiveLate * self::LATE_RATE_PER_MINUTE;

        // Basic pay is prorated by average hours per day; the full wage is earned at 8h/day
        $dailyHours = $days > 0 ? $hours / $days : 0.0;
        $ratio      = min(max($dailyHours / self::FULL_DAY_HOURS, 0.0), 1.0);
        $basicPay   = $wage * $days * $ratio;

        $honorarium = $num('honorarium');
        $grossPay   = $basicPay + $honorarium;

        $deductions = [
            'malasakitan' => self::MALASAKITAN,
            'late'        => $late,
            'absences'    => $absences,
        ];
        foreach (self::SECOND_CUTOFF_ONLY as $key) {
            $deductions[$key] = $cutoff === '30th' ? $num($key) : 0.0;
        }
        // Stored as the monthly amount; half is taken each cutoff
        $deductions['cash_advance'] = $num('cash_advance') / 2;

        $totalDeductions = array_sum($deductions);

        return [
            'step_name'        => $stepName,
            'daily_wage'       => $wage,
            'basic_pay'        => $basicPay,
            'honorarium'       => $honorarium,
            'gross_pay'        => $grossPay,
            'deductions'       => $deductions,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $grossPay - $totalDeductions,
        ];
    }
}