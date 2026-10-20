<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalaryMonitoringController extends Controller
{
    /**
     * Accounting only: every transferred payslip and where it is in the flow
     * Accounting -> Admin + HR (reviewed side by side) -> Employee.
     */
    public function index()
    {
        $this->authorizeAccounting();

        $payslips = Payslip::with('employee')
            ->whereNotNull('transfer_date')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $cards = $payslips->getCollection()->map->toCard()->keyBy('id');

        $rows = $payslips->getCollection()->map(fn (Payslip $p) => [
            'id'       => $p->id,
            'name'     => $p->employee?->name ?? 'Unknown employee',
            'number'   => $p->employee?->employee_id ?? '-',
            'dept'     => $p->employee
                ? (Employee::DEPARTMENTS[$p->employee->department] ?? $p->employee->department)
                : '-',
            'net'      => (float) $p->net_pay,
            'cutoff'   => $p->cutoff,
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