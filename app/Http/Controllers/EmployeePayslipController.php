<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipResponse;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeePayslipController extends Controller
{
    /** The logged-in employee's released payslips, grouped by month. */
    public function index()
    {
        $employee = $this->currentEmployee();
        $months   = [];

        if ($employee) {
            $months = Payslip::with('response')
                ->where('employee_id', $employee->id)
                ->whereNotNull('released_at')
                ->orderByDesc('transfer_date')
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn (Payslip $p) => ($p->transfer_date ?? $p->created_at)->format('Y-m'))
                ->sortKeysDesc()
                ->map(fn ($group, $key) => [
                    'key'     => $key,
                    'label'   => Carbon::createFromFormat('!Y-m', $key)->format('F Y'),
                    'count'   => $group->count(),
                    'records' => $group->map->toEmployeeCard()->values()->all(),
                ])
                ->values()
                ->all();
        }

        return view('payroll', [
            'employee' => $employee,
            'months'   => $months,
            'total'    => collect($months)->sum('count'),
            'reopenId' => session('reopen_payslip'),
        ]);
    }

    /** Approve or reject (with a reason) one of my released payslips. One decision per payslip. */
    public function decide(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        abort_unless($employee, 403, 'Your account is not linked to an employee record. Please contact HR.');

        $data = $request->validate([
            'payslip_id' => ['required', 'integer', 'exists:payslips,id'],
            'decision'   => ['required', Rule::in(['approve', 'reject'])],
            'reason'     => [Rule::requiredIf($request->decision === 'reject'), 'nullable', 'string', 'max:1000'],
        ]);

        // Only my own payslips, and only ones Accounting already released
        $payslip = Payslip::where('employee_id', $employee->id)
            ->whereNotNull('released_at')
            ->findOrFail($data['payslip_id']);

        if ($payslip->response()->exists()) {
            return back()
                ->withErrors(['payslip' => 'You already submitted a decision for this payslip.'])
                ->with('reopen_payslip', $payslip->id);
        }

        $approved = $data['decision'] === 'approve';

        PayslipResponse::create([
            'payslip_id'  => $payslip->id,
            'employee_id' => $employee->id,
            'status'      => $approved ? PayslipResponse::APPROVED : PayslipResponse::REJECTED,
            'reason'      => $approved ? null : trim($data['reason']),
            'reviewed_at' => now(),
        ]);

        return back()
            ->with('success', $approved ? 'Payslip approved.' : 'Payslip rejected. Accounting will review your reason.')
            ->with('reopen_payslip', $payslip->id);
    }

    /** The employee record that belongs to the logged-in user (user_id link, or matching email). */
    private function currentEmployee(): ?Employee
    {
        $user = request()->user();

        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee) {
            $employee = Employee::whereNull('user_id')->where('email', $user->email)->first();
            $employee?->update(['user_id' => $user->id]);
        }

        return $employee;
    }
}