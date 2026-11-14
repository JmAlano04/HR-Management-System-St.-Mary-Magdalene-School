<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeePayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $this->currentEmployee($request);

        $payslips = Payslip::released()
            ->forEmployee($employee->id)
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
            'openPayslipId' => session('open_payslip'),
        ]);
    }

    private function currentEmployee(Request $request): Employee
    {
        return Employee::where('user_id', $request->user()->id)->firstOrFail();
    }

    /** Shape a payslip for the Blade/Alpine front end. */
    private function present(Payslip $p, Employee $employee): array
    {
        return [
            'id'           => $p->id,
            'cutoff'       => $p->cutoff,
            'cutoff_label' => $p->cutoffDateLabel(),
            'step_name'    => $p->step_name,
            'days'         => (float) $p->days_worked,
            'hours'        => (float) $p->hours_worked,
            'late_min'     => (int) $p->late_minutes,
            'basic_pay'    => (float) $p->basic_pay,
            'daily_wage'   => (float) $p->daily_wage,
            'honorarium'   => (float) $p->honorarium,
            'gross_pay'    => (float) $p->gross_pay,
            'deductions' => [
                ['MALASAKITAN',     (float) $p->malasakitan],
                ['Late',            (float) $p->late_deduction],
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
            'status'           => 'Released',
            'employee'         => [
                'name'       => $employee->name,
                'number'     => $employee->employee_id,
                'department' => ucfirst($employee->department),
                'position'   => $employee->position,
            ],
        ];
    }
}