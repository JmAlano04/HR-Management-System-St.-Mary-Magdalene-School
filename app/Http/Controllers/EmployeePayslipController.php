<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeePayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $this->currentEmployee($request);

        $payslips = Payslip::released()
            ->forEmployee($employee->id)
            ->with('response')
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->get();

        // Group into month "folders": ['2026-10' => ['label' => 'October 2026', 'payslips' => [...]]]
        $months = $payslips
            ->groupBy(fn (Payslip $p) => ($p->transfer_date ?? $p->released_at)->format('Y-m'))
            ->map(fn ($group, $key) => [
                'key'      => $key,
                'label'    => \Carbon\Carbon::createFromFormat('Y-m', $key)->format('F Y'),
                'payslips' => $group->map(fn (Payslip $p) => $this->present($p, $employee))->values(),
            ])
            ->values();

        return view('payroll', [
            'employee'      => $employee,
            'months'        => $months,
            'totalReleased' => $payslips->count(),
            'openPayslipId' => session('open_payslip'),
        ]);
    }

    public function respond(Request $request, Payslip $payslip): RedirectResponse
    {
        $employee = $this->currentEmployee($request);

        // Must be the employee's own, released payslip.
        abort_unless($payslip->employee_id === $employee->id && $payslip->isReleased(), 404);

        // Already decided (Approved or Rejected)? Then it is final.
        $existing = $payslip->response;
        if ($existing && $existing->isFinal()) {
            return back()->withErrors(['payslip' => 'You have already responded to this payslip.']);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason'   => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ], [
            'reason.required_if' => 'Please provide a reason for rejecting this payslip.',
        ]);

        $approved = $data['decision'] === 'approve';

        // Approve: updates the existing response row's status to 'Approved'
        //          (or creates it if none exists yet).
        // Reject:  saved the same way as before, with the reason.
        PayslipResponse::updateOrCreate(
            ['payslip_id' => $payslip->id],
            [
                'employee_id' => $employee->id,
                'status'      => $approved ? PayslipResponse::APPROVED : PayslipResponse::REJECTED,
                'reason'      => $approved ? null : trim($data['reason']),
                'reviewed_at' => now(),
            ]
        );

        return redirect()
            ->route('salary-records.employee.index')
            ->with('success', $approved ? 'Payslip approved.' : 'Payslip rejected. Accounting has been notified.')
            ->with('open_payslip', $payslip->id);
    }

    private function currentEmployee(Request $request): Employee
    {
        return Employee::where('user_id', $request->user()->id)->firstOrFail();
    }

    /** Shape a payslip for the Blade/Alpine front end. */
    private function present(Payslip $p, Employee $employee): array
    {
        $r = $p->response;

        return [
            'id'         => $p->id,
            'cutoff'     => $p->cutoff,
            'step_name'  => $p->step_name,
            'days'       => (float) $p->days_worked,
            'hours'      => (float) $p->hours_worked,
            'late_min'   => (int) $p->late_minutes,
            'basic_pay'  => (float) $p->basic_pay,
            'daily_wage' => (float) $p->daily_wage,
            'honorarium' => (float) $p->honorarium,
            'gross_pay'  => (float) $p->gross_pay,
            'deductions' => [
                ['MALASAKITAN',     (float) $p->malasakitan],
                ['Late',            (float) $p->late_deduction],
                ['Absences',        (float) $p->absences_deduction],
                ['SSS',             (float) $p->sss],
                ['SSS Loan',        (float) $p->sss_loan],
                ['PhilHealth',      (float) $p->philhealth],
                ['PERAA/CEAP',      (float) $p->peraa_ceap],
                ['PERAA/CEAP Loan', (float) $p->peraa_ceap_loan],
                ['HDMF',            (float) $p->hdmf],
                ['HDMF Loan',       (float) $p->hdmf_loan],
                ['Cash advance',    (float) $p->cash_advance],
            ],
            'total_deductions' => (float) $p->total_deductions,
            'net_pay'          => (float) $p->net_pay,
            'transfer_date'    => $p->transfer_date?->format('M d, Y'),
            'status'           => $r?->status ?? PayslipResponse::PENDING,
            'reason'           => $r?->reason,
            'action_url'       => route('employee.payslips.respond', $p),
            'employee'         => [
                'name'       => $employee->name,
                'number'     => $employee->employee_id,
                'department' => ucfirst($employee->department),
                'position'   => $employee->position,
            ],
        ];
    }
}