<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipResponse;
use App\Support\PayslipPresenter;
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
            ->with(['response', 'employee.user.roles'])
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
        return Employee::with('user.roles')->where('user_id', $request->user()->id)->firstOrFail();
    }

    /**
     * Shape a payslip for the Blade/Alpine front end.
     * All money figures come from PayslipPresenter, the same builder Accounting, Admin and HR use.
     */
    private function present(Payslip $p, Employee $employee): array
    {
        $c = PayslipPresenter::card($p);
        $r = $p->response;

        // [label, amount] pairs, in the shared deduction order
        $deductions = [];
        foreach (PayslipPresenter::DEDUCTION_LABELS as $key => $label) {
            $deductions[] = [$label, $c['deductions'][$key]];
        }

        return [
            'id'           => $c['id'],
            'cutoff'       => $c['cutoff'],
            'cutoff_day'   => $c['cutoff_day'],
            'period'       => $c['period'],
            'period_label' => $c['cutoff_label'],
            'step_name'    => $c['step_name'],
            'days'         => $c['days_worked'],
            'hours'        => $c['hours_worked'],
            'late_min'     => $c['late_minutes'],
            'monthly_wage' => $c['monthly_wage'],
            'basic_pay'    => $c['basic_pay'],
            'daily_wage'   => $c['daily_wage'],
            'honorarium'   => $c['honorarium'],
            'gross_pay'    => $c['gross_pay'],
            'deductions'   => $deductions,
            'total_deductions' => $c['total_deductions'],
            'net_pay'          => $c['net_pay'],
            'transfer_date'    => $c['transfer_date'],
            'status'           => $r?->status ?? PayslipResponse::PENDING,
            'reason'           => $r?->reason,
            'action_url'       => route('employee.payslips.respond', $p),
            'employee'         => [
                'name'       => $employee->name,
                'number'     => $employee->employee_id,
                'department' => ucfirst($employee->department),
                'position'   => $employee->position ?: '-',
            ],
        ];
    }
}