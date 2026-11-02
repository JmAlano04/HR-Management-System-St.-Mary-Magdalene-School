<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryRecordController extends Controller
{
    /** Same as PayrollProcessingController: monthly wage = daily wage x 26. */
    private const MONTHLY_DAYS = 26;

    /** Fixed amounts (same as PayrollProcessingController). */
    private const HDMF       = 200.00;    // every pay period
    private const HONORARIUM = 1924.31;   // administrative role only

    /** Pay period => attendance cutoff day (same as PayrollProcessingController). */
    private const CUTOFF_DAY = ['15th' => '10th', '30th' => '25th'];

    /**
     * Accounting's history of payslips that were already transferred to employees:
     * approved by Admin AND HR, and released to the employee.
     * Grouped by cutoff (15th / 30th tab), then by month (folders), then by employee.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasRole('Accounting'), 403, 'Only Accounting can view salary records.');

        $cutoff = $request->query('cutoff') === '30th' ? '30th' : '15th';

        // employee.user is loaded once so the administrative-role check does not query per payslip
        $records = Payslip::with('employee.user')
            ->where('cutoff', $cutoff)
            ->where('admin_status', Payslip::APPROVED)
            ->where('hr_status', Payslip::APPROVED)
            ->whereNotNull('released_at')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->get();

        $months = $records
            ->groupBy(fn (Payslip $p) => ($p->transfer_date ?? $p->created_at)->format('Y-m'))
            ->sortKeysDesc()
            ->map(fn ($group, $key) => [
                'key'     => $key,
                'label'   => Carbon::createFromFormat('!Y-m', $key)->format('F Y'),
                'count'   => $group->count(),
                'records' => $group
                    ->sortBy(fn (Payslip $p) => $p->employee?->name)
                    ->map(fn (Payslip $p) => $this->card($p))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return view('salary-record-management', [
            'cutoff' => $cutoff,
            'months' => $months,
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Money figures of a payslip with the fixed amounts applied:
     *  - HDMF       = P200.00 on every payslip
     *  - Honorarium = P1,924.31 for the administrative role, P0 for everyone else
     * Gross pay, total deductions and net pay are recomputed from the saved columns,
     * so the popup always matches Salary Monitoring and Payroll Processing.
     */
    private function figures(Payslip $p): array
    {
        $honorarium = ($p->employee && $this->isAdministrative($p->employee)) ? self::HONORARIUM : 0.00;
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
            'honorarium'       => $honorarium,
            'gross_pay'        => $gross,
            'deductions'       => $deductions,
            'total_deductions' => $total,
            'net_pay'          => round($gross - $total, 2),
        ];
    }

    /** Payslip popup data: same fields and same figures as Salary Monitoring / Payroll Processing. */
    private function card(Payslip $p): array
    {
        $e = $p->employee;
        $f = $this->figures($p);

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
            'period'          => $this->periodLabel($p),

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

    /**
     * Administrative role gets the fixed honorarium.
     * Same rule as PayrollProcessingController::isAdministrative().
     */
    private function isAdministrative(Employee $e): bool
    {
        $isAdmin = fn ($v) => is_string($v) && str_contains(strtolower($v), 'administrative');

        // 1. Any text column on the employee record
        foreach ($e->getAttributes() as $value) {
            if ($isAdmin($value)) {
                return true;
            }
        }
        if ($isAdmin(Employee::DEPARTMENTS[$e->department] ?? null)) {
            return true;
        }

        // 2. Role on the linked user account (Spatie)
        $user = method_exists($e, 'user') ? $e->user : null;

        return $user instanceof User
            && $user->getRoleNames()->contains(fn ($role) => $isAdmin($role));
    }

    /** Month of the payroll run (Payroll Processing stores it as transfer_date = first day of the month). */
    private function periodLabel(Payslip $p): string
    {
        return $p->transfer_date ? Carbon::parse($p->transfer_date)->format('F Y') : '';
    }
}