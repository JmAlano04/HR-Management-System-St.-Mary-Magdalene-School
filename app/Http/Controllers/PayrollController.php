<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /** Admin's salary monitoring approval queue. */
    public function index()
    {
        $this->authorizeAdmin();

        $sent = Payslip::sentToAdmin();

        $summary = [
            'total'    => (clone $sent)->count(),
            'pending'  => (clone $sent)->where('admin_status', Payslip::PENDING)->count(),
            'approved' => (clone $sent)->where('admin_status', Payslip::APPROVED)->count(),
            'rejected' => (clone $sent)->where('admin_status', Payslip::REJECTED)->count(),
        ];

        $allApproved = $summary['total'] > 0
            && ! Payslip::sentToAdmin()
                ->where(fn ($q) => $q->where('admin_status', '!=', Payslip::APPROVED)
                    ->orWhere('hr_status', '!=', Payslip::APPROVED))
                ->exists();

        $statusText = match (true) {
            $summary['total'] === 0 => 'Waiting for salary transfer',
            $allApproved            => 'All payslips approved and ready for employee release',
            default                 => "{$summary['approved']} of {$summary['total']} payslips approved by Admin",
        };

        $payslips = Payslip::sentToAdmin()
            ->with('employee')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('payroll', [
            'payslips'   => $payslips,
            'cards'      => $payslips->getCollection()->map->toCard()->keyBy('id'),
            'summary'    => $summary,
            'statusText' => $statusText,
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate(['payslip_id' => ['required', 'integer', 'exists:payslips,id']]);

        $payslip = Payslip::sentToAdmin()->findOrFail($data['payslip_id']);

        if ($payslip->admin_status !== Payslip::PENDING) {
            return back()->withErrors(['payslip' => 'This payslip has already been reviewed.']);
        }

        $payslip->update([
            'admin_status'      => Payslip::APPROVED,
            'admin_reviewed_at' => now(),
            'admin_reviewed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Payslip approved.');
    }

    public function reject(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'payslip_id' => ['required', 'integer', 'exists:payslips,id'],
            'reason'     => ['required', 'string', 'max:1000'],
        ]);

        $payslip = Payslip::sentToAdmin()->findOrFail($data['payslip_id']);

        if ($payslip->admin_status !== Payslip::PENDING) {
            return back()->withErrors(['payslip' => 'This payslip has already been reviewed.']);
        }

        $payslip->update([
            'admin_status'           => Payslip::REJECTED,
            'admin_rejection_reason' => trim($data['reason']),
            'admin_reviewed_at'      => now(),
            'admin_reviewed_by'      => Auth::id(),
        ]);

        return back()->with('success', 'Payslip rejected.');
    }

    public function approveAll(): RedirectResponse
    {
        $this->authorizeAdmin();

        $count = Payslip::sentToAdmin()
            ->where('admin_status', Payslip::PENDING)
            ->update([
                'admin_status'      => Payslip::APPROVED,
                'admin_reviewed_at' => now(),
                'admin_reviewed_by' => Auth::id(),
            ]);

        if ($count === 0) {
            return back()->withErrors(['payslip' => 'There are no pending payslips to approve.']);
        }

        return back()->with('success', $count . ' ' . ($count === 1 ? 'payslip' : 'payslips') . ' approved.');
    }

    /**
     * The sidebar sends Admin, Accounting and Employee to the same "Payroll" menu item,
     * so make sure only Admin can use this page and its actions.
     */
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        $isAdmin = $user !== null
            && is_callable([$user, 'hasRole'])
            && call_user_func([$user, 'hasRole'], 'Admin');

        abort_unless($isAdmin, 403, 'Only Admin can review payroll.');
    }
}