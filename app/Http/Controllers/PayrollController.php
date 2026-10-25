<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /**
     * Admin, HR, and Accounting can view payroll.
     * HR sees the HR approval stage; everyone else sees the Admin stage.
     */
    public function index()
    {
        $this->authorizePayrollView();

        $prefix = $this->stagePrefix();          // 'hr' or 'admin'
        $column = "{$prefix}_status";
        $stage  = $prefix === 'hr' ? 'HR' : 'Admin';

        $sent = Payslip::sentToAdmin();

        $summary = [
            'total'    => (clone $sent)->count(),
            'pending'  => (clone $sent)->where($column, Payslip::PENDING)->count(),
            'approved' => (clone $sent)->where($column, Payslip::APPROVED)->count(),
            'rejected' => (clone $sent)->where($column, Payslip::REJECTED)->count(),
        ];

        // Ready for employee release only when BOTH Admin and HR approved everything
        $allApproved = $summary['total'] > 0
            && !Payslip::sentToAdmin()
                ->where(fn ($q) => $q
                    ->where('admin_status', '!=', 'approved')
                    ->orWhere('hr_status', '!=', 'approved'))
                ->exists();

        $statusText = match (true) {
            $summary['total'] === 0 =>
                'Waiting for salary transfer',

            $allApproved =>
                'All payslips approved and ready for employee release',

            default =>
                "{$summary['approved']} of {$summary['total']} payslips approved by {$stage}",
        };

        $payslips = Payslip::sentToAdmin()
            ->with('employee')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        // NOTE: change to view('payroll') if your file is resources/views/payroll.blade.php
        return view('payroll', [
            'payslips'   => $payslips,
            'cards'      => $payslips->getCollection()
                ->map->toCard()
                ->keyBy('id'),
            'summary'    => $summary,
            'statusText' => $statusText,
        ]);
    }

    /**
     * Admin or HR: approve a payslip (updates only their own stage).
     */
    public function approve(Request $request): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

        $data = $request->validate([
            'payslip_id' => ['required', 'integer', 'exists:payslips,id'],
        ]);

        $payslip = Payslip::sentToAdmin()->findOrFail($data['payslip_id']);

        if ($payslip->{"{$prefix}_status"} !== Payslip::PENDING) {
            return back()->withErrors([
                'payslip' => 'This payslip has already been reviewed.',
            ]);
        }

        $payslip->update([
            "{$prefix}_status"      => Payslip::APPROVED,
            "{$prefix}_reviewed_at" => now(),
            "{$prefix}_reviewed_by" => Auth::id(),
        ]);

        return back()->with('success', 'Payslip approved.');
    }

    /**
     * Admin or HR: reject a payslip with a reason.
     */
    public function reject(Request $request): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

        $data = $request->validate([
            'payslip_id' => ['required', 'integer', 'exists:payslips,id'],
            'reason'     => ['required', 'string', 'max:1000'],
        ]);

        $payslip = Payslip::sentToAdmin()->findOrFail($data['payslip_id']);

        if ($payslip->{"{$prefix}_status"} !== Payslip::PENDING) {
            return back()->withErrors([
                'payslip' => 'This payslip has already been reviewed.',
            ]);
        }

        $payslip->update([
            "{$prefix}_status"           => Payslip::REJECTED,
            "{$prefix}_rejection_reason" => trim($data['reason']),
            "{$prefix}_reviewed_at"      => now(),
            "{$prefix}_reviewed_by"      => Auth::id(),
        ]);

        return back()->with('success', 'Payslip rejected.');
    }

    /**
     * Admin or HR: approve all of their pending payslips.
     */
    public function approveAll(): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

        $count = Payslip::sentToAdmin()
            ->where("{$prefix}_status", Payslip::PENDING)
            ->update([
                "{$prefix}_status"      => Payslip::APPROVED,
                "{$prefix}_reviewed_at" => now(),
                "{$prefix}_reviewed_by" => Auth::id(),
            ]);

        if ($count === 0) {
            return back()->withErrors([
                'payslip' => 'There are no pending payslips to approve.',
            ]);
        }

        return back()->with(
            'success',
            $count . ' ' . ($count === 1 ? 'payslip' : 'payslips') . ' approved.'
        );
    }

    /**
     * Allow Admin, HR, and Accounting to view payroll.
     */
    private function authorizePayrollView(): void
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User
                && $user->hasAnyRole(['Admin', 'HR', 'Accounting']),
            403,
            'Only Admin, HR, and Accounting can view payroll.'
        );
    }

    /**
     * Only Admin and HR can review. Returns the column prefix for the
     * current reviewer: 'hr' for HR, 'admin' for Admin.
     */
    private function authorizeReviewer(): string
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasAnyRole(['Admin', 'HR']),
            403,
            'Only Admin and HR can review payroll.'
        );

        return $this->stagePrefix();
    }

    /**
     * 'hr' when the logged-in user is HR, otherwise 'admin'.
     * Matches the $isHR check in the payroll view.
     */
    private function stagePrefix(): string
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('HR') ? 'hr' : 'admin';
    }
}