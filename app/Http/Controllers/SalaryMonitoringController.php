<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalaryMonitoringController extends Controller
{
    /** Same as PayrollProcessingController: monthly wage = daily wage x 26. */
    private const MONTHLY_DAYS = 26;

    /** Fixed amounts (same as PayrollProcessingController). */
    private const HDMF       = 200.00;    // every pay period
    private const HONORARIUM = 1924.31;   // administrative role only

    /** Pay period => attendance cutoff day (same as PayrollProcessingController). */
    private const CUTOFF_DAY = ['15th' => '10th', '30th' => '25th'];

    /**
     * Accounting only: every transferred payslip and where it is in the flow
     * Accounting -> Admin + HR (reviewed side by side) -> Employee.
     */
    public function index()
    {
        $this->authorizeAccounting();

        // employee.user is loaded once so the administrative-role check does not query per payslip
        $payslips = Payslip::with('employee.user')
            ->whereNotNull('transfer_date')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // The payslip popup is built here from the saved payslip columns (HDMF and honorarium
        // use the fixed amounts), so it matches the Payroll Processing payslip.
        $cards = $payslips->getCollection()
            ->mapWithKeys(fn (Payslip $p) => [$p->id => $this->card($p)]);

        $rows = $payslips->getCollection()->map(fn (Payslip $p) => [
            'id'       => $p->id,
            'name'     => $p->employee?->name ?? 'Unknown employee',
            'number'   => $p->employee?->employee_id ?? '-',
            'dept'     => $p->employee
                ? (Employee::DEPARTMENTS[$p->employee->department] ?? $p->employee->department)
                : '-',
            'net'      => $this->figures($p)['net_pay'],    // same net pay as the popup
            'cutoff'   => $p->cutoff,                       // 15th | 30th
            'cutoffLabel' => $p->cutoff . ' Pay Period',
            'period'   => $this->periodLabel($p),           // e.g. "October 2026"
            // Admin and HR receive the payslip at the same moment (sent_to_admin_at)
            'admin'    => ['sent' => (bool) $p->sent_to_admin_at, 'status' => $p->admin_status, 'reason' => $p->admin_rejection_reason],
            'hr'       => ['sent' => (bool) $p->sent_to_admin_at, 'status' => $p->hr_status,    'reason' => $p->hr_rejection_reason],
            'employee' => ['sent' => (bool) $p->released_at],
        ])->values();

        return view('salary-monitoring', compact('payslips', 'cards', 'rows'));
    }

    /**
     * 'admin'    -> Send to Admin & HR (also used to resend after a rejection)
     * 'employee' -> release to employees (needs Admin AND HR approval)
     */
    public function send(Request $request): RedirectResponse
    {
        $this->authorizeAccounting();

        $data = $request->validate([
            'role'  => ['required', Rule::in(['admin', 'employee'])],
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:payslips,id'],
        ], ['ids.required' => 'Select at least one payslip first.']);

        $slips = Payslip::whereIn('id', $data['ids'])->get();

        $eligible = $slips->filter(fn (Payslip $p) => $data['role'] === 'admin'
            ? (!$p->sent_to_admin_at || $p->admin_status === Payslip::REJECTED || $p->hr_status === Payslip::REJECTED)
            : !$p->released_at);

        if ($eligible->isEmpty()) {
            return back()->withErrors(['send' => 'The selected payslips were already sent.']);
        }

        if ($data['role'] === 'employee'
            && $eligible->contains(fn ($p) => $p->admin_status !== Payslip::APPROVED || $p->hr_status !== Payslip::APPROVED)) {
            return back()->withErrors(['send' => 'Admin and HR approval is required before sending to Employees.']);
        }

        foreach ($eligible as $p) {
            if ($data['role'] === 'employee') {
                $p->update(['released_at' => now()]);
                continue;
            }

            // Only the side that rejected has to review again; an approval stays approved
            $update = ['sent_to_admin_at' => $p->sent_to_admin_at ?? now(), 'released_at' => null];

            if ($p->admin_status === Payslip::REJECTED) {
                $update += ['admin_status' => Payslip::PENDING, 'admin_rejection_reason' => null,
                            'admin_reviewed_at' => null, 'admin_reviewed_by' => null];
            }
            if ($p->hr_status === Payslip::REJECTED) {
                $update += ['hr_status' => Payslip::PENDING, 'hr_rejection_reason' => null,
                            'hr_reviewed_at' => null, 'hr_reviewed_by' => null];
            }
            if (!$p->sent_to_admin_at) {
                $update['sent_to_admin_at'] = now();
            }

            $p->update($update);
        }

        $label = $data['role'] === 'admin' ? 'Admin & HR' : 'Employees';
        $n = $eligible->count();

        return back()->with('success', "Sent {$n} " . ($n === 1 ? 'payslip' : 'payslips') . " to {$label}.");
    }

    // ------------------------------------------------------------------

    /**
     * Money figures of a payslip with the fixed amounts applied:
     *  - HDMF       = P200.00 on every payslip
     *  - Honorarium = P1,924.31 for the administrative role, P0 for everyone else
     * Gross pay, total deductions and net pay are recomputed from the saved columns,
     * so the popup and the list always add up.
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

    /** Payslip popup data: same fields and same figures as the Payroll Processing payslip. */
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

    private function authorizeAccounting(): void
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasRole('Accounting'),
            403,
            'Only Accounting can manage salary monitoring.'
        );
    }
}