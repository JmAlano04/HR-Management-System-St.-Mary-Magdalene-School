<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use App\Services\CashAdvancePayrollService;
use App\Services\HrmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
=======
use App\Models\Payslip;
use App\Models\User;
use App\Support\PayslipPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

<<<<<<< HEAD
        $payslips = Payslip::with('employee')
            ->whereNotNull('transfer_date')
            ->orderByDesc('transfer_date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $cards = $payslips->getCollection()->map->toCard()->keyBy('id');

        $rows = $payslips->getCollection()->map(fn (Payslip $p) => [
            'id' => $p->id,
            'name' => $p->employee?->name ?? 'Unknown employee',
            'number' => $p->employee?->employee_id ?? '-',
            'dept' => $p->employee
                ? (Employee::DEPARTMENTS[$p->employee->department] ?? $p->employee->department)
                : '-',
            'net' => (float) $p->net_pay,
            'cutoff' => $p->cutoff,
            // Admin and HR receive the payslip at the same moment (sent_to_admin_at)
            'admin' => ['sent' => (bool) $p->sent_to_admin_at, 'status' => $p->admin_status, 'reason' => $p->admin_rejection_reason],
            'hr' => ['sent' => (bool) $p->sent_to_admin_at, 'status' => $p->hr_status,    'reason' => $p->hr_rejection_reason],
            'employee' => ['sent' => (bool) $p->released_at],
        ])->values();
=======
        // employee.user.roles is loaded once so the System Administrator check does not query per payslip
        $payslips = Payslip::with('employee.user.roles')
            ->whereNotNull('transfer_date')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // Every figure (honorarium, HDMF, total deductions, net pay) comes from PayslipPresenter,
        // the same builder Employee, Admin and HR use, so the list, the popup and every role match.
        $cards = $payslips->getCollection()
            ->mapWithKeys(fn (Payslip $p) => [$p->id => PayslipPresenter::card($p)]);

        $rows = $payslips->getCollection()->map(function (Payslip $p) use ($cards) {
            $card = $cards[$p->id];

            return [
                'id'          => $p->id,
                'name'        => $card['employee_name'],
                'number'      => $card['employee_number'],
                'dept'        => $card['department'],
                'net'         => $card['net_pay'],          // same net pay as the popup
                'cutoff'      => $card['cutoff'],           // 15th | 30th
                'cutoffLabel' => $card['cutoff_label'],
                'period'      => $card['period'],           // e.g. "October 2026"
                // Admin and HR receive the payslip at the same moment (sent_to_admin_at)
                'admin'    => ['sent' => (bool) $p->sent_to_admin_at, 'status' => $p->admin_status, 'reason' => $p->admin_rejection_reason],
                'hr'       => ['sent' => (bool) $p->sent_to_admin_at, 'status' => $p->hr_status,    'reason' => $p->hr_rejection_reason],
                'employee' => ['sent' => (bool) $p->released_at],
            ];
        })->values();
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

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
<<<<<<< HEAD
            'role' => ['required', Rule::in(['admin', 'employee'])],
            'ids' => ['required', 'array', 'min:1'],
=======
            'role'  => ['required', Rule::in(['admin', 'employee'])],
            'ids'   => ['required', 'array', 'min:1'],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'ids.*' => ['integer', 'exists:payslips,id'],
        ], ['ids.required' => 'Select at least one payslip first.']);

        $slips = Payslip::whereIn('id', $data['ids'])->get();

        $eligible = $slips->filter(fn (Payslip $p) => $data['role'] === 'admin'
<<<<<<< HEAD
            ? (! $p->sent_to_admin_at || $p->admin_status === Payslip::REJECTED || $p->hr_status === Payslip::REJECTED)
            : ! $p->released_at);
=======
            ? (!$p->sent_to_admin_at || $p->admin_status === Payslip::REJECTED || $p->hr_status === Payslip::REJECTED)
            : !$p->released_at);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        if ($eligible->isEmpty()) {
            return back()->withErrors(['send' => 'The selected payslips were already sent.']);
        }

        if ($data['role'] === 'employee'
<<<<<<< HEAD
            && $eligible->contains(fn ($p) => strtolower(trim((string) $p->admin_status)) !== strtolower(Payslip::APPROVED)
                || strtolower(trim((string) $p->hr_status)) !== strtolower(Payslip::APPROVED))) {
=======
            && $eligible->contains(fn ($p) => $p->admin_status !== Payslip::APPROVED || $p->hr_status !== Payslip::APPROVED)) {
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            return back()->withErrors(['send' => 'Admin and HR approval is required before sending to Employees.']);
        }

        foreach ($eligible as $p) {
            if ($data['role'] === 'employee') {
<<<<<<< HEAD
                DB::transaction(function () use ($p) {
                    $p->update(['released_at' => now()]);
                    app(CashAdvancePayrollService::class)->recordPayslipRepayments($p);
                });
                HrmsNotifier::payslipReleased($p);

=======
                $p->update(['released_at' => now()]);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                continue;
            }

            // Only the side that rejected has to review again; an approval stays approved
<<<<<<< HEAD
            $update = ['sent_to_admin_at' => now(), 'released_at' => null];

            if ($p->admin_status === Payslip::REJECTED) {
                $update += ['admin_status' => Payslip::PENDING, 'admin_rejection_reason' => null,
                    'admin_reviewed_at' => null, 'admin_reviewed_by' => null];
            }
            if ($p->hr_status === Payslip::REJECTED) {
                $update += ['hr_status' => Payslip::PENDING, 'hr_rejection_reason' => null,
                    'hr_reviewed_at' => null, 'hr_reviewed_by' => null];
            }
            if (! $p->sent_to_admin_at) {
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                $update['sent_to_admin_at'] = now();
            }

            $p->update($update);
<<<<<<< HEAD
            HrmsNotifier::payslipSubmitted($p->refresh());
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        }

        $label = $data['role'] === 'admin' ? 'Admin & HR' : 'Employees';
        $n = $eligible->count();

<<<<<<< HEAD
        return back()->with('success', "Sent {$n} ".($n === 1 ? 'payslip' : 'payslips')." to {$label}.");
=======
        return back()->with('success', "Sent {$n} " . ($n === 1 ? 'payslip' : 'payslips') . " to {$label}.");
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
}
=======
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
