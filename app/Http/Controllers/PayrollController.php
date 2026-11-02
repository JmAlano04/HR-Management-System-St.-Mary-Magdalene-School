<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /** Monthly wage = daily wage x 26 working days. */
    private const WORKING_DAYS_PER_MONTH = 26;

    /** HDMF (Pag-IBIG) is a fixed amount on every pay period. */
    private const HDMF_FIXED = 200.00;

    /**
     * Admin, HR, and Accounting can view payroll.
     * HR sees the HR approval stage; everyone else sees the Admin stage.
     * ?view=archived shows archived payslips; the default is the active queue.
     */
    public function index(Request $request)
    {
        $this->authorizePayrollView();

        $view   = $request->query('view') === 'archived' ? 'archived' : 'active';
        $prefix = $this->stagePrefix();          // 'hr' or 'admin'
        $column = "{$prefix}_status";
        $stage  = $prefix === 'hr' ? 'HR' : 'Admin';

        // Summary cards and the status text only count the ACTIVE queue
        $sent = Payslip::sentToAdmin()->active();

        $summary = [
            'total'    => (clone $sent)->count(),
            'pending'  => (clone $sent)->where($column, Payslip::PENDING)->count(),
            'approved' => (clone $sent)->where($column, Payslip::APPROVED)->count(),
            'rejected' => (clone $sent)->where($column, Payslip::REJECTED)->count(),
        ];

        $archivedCount = Payslip::sentToAdmin()->archived()->count();

        // Ready for employee release only when BOTH Admin and HR approved everything
        $allApproved = $summary['total'] > 0
            && !Payslip::sentToAdmin()->active()
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

        $listQuery = $view === 'archived'
            ? Payslip::sentToAdmin()->archived()
            : Payslip::sentToAdmin()->active();

        $payslips = $listQuery
            ->with(['employee', 'response'])
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        // NOTE: change to view('payroll.index') to match resources/views/payroll/index.blade.php
        return view('payroll', [
            'payslips'      => $payslips,
            'cards'         => $payslips->getCollection()
                ->map(fn (Payslip $slip) => $this->card($slip))
                ->keyBy('id'),
            'summary'       => $summary,
            'statusText'    => $statusText,
            'view'          => $view,
            'archivedCount' => $archivedCount,
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
     * Admin or HR: approve all of their pending ACTIVE payslips.
     */
    public function approveAll(): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

        $count = Payslip::sentToAdmin()
            ->active()
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
     * Admin or HR: move a reviewed payslip out of the active queue.
     * A payslip still pending at the reviewer's own stage cannot be archived.
     */
    public function archive(Request $request): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

        $data = $request->validate([
            'payslip_id' => ['required', 'integer', 'exists:payslips,id'],
        ]);

        $payslip = Payslip::sentToAdmin()->active()->findOrFail($data['payslip_id']);

        if ($payslip->isStagePending($prefix)) {
            return back()->withErrors([
                'payslip' => 'Approve or reject this payslip before archiving it.',
            ]);
        }

        $payslip->update(['archived_at' => now()]);

        return back()->with('success', 'Payslip archived.');
    }

    /**
     * Admin or HR: bring an archived payslip back to the active queue.
     */
    public function restore(Request $request): RedirectResponse
    {
        $this->authorizeReviewer();

        $data = $request->validate([
            'payslip_id' => ['required', 'integer', 'exists:payslips,id'],
        ]);

        $payslip = Payslip::sentToAdmin()->archived()->findOrFail($data['payslip_id']);

        $payslip->update(['archived_at' => null]);

        return back()->with('success', 'Payslip restored to the active queue.');
    }

    /**
     * Data for the payslip modal: the model's own card plus
     * the monthly wage, the pay period label, the PhilHealth loan
     * and the fixed HDMF amount.
     */
    private function card(Payslip $slip): array
    {
        $card = $slip->toCard();

        // Monthly wage = daily wage x 26
        $card['monthly_wage'] = round((float) $slip->daily_wage * self::WORKING_DAYS_PER_MONTH, 2);

        // "15th" -> "15th Pay Period", "30th" -> "30th Pay Period"
        $card['cutoff_label'] = $this->payPeriodLabel($slip->cutoff);

        // Employee's answer for THIS payslip (controls the Download button in the modal)
        $card['employee_status'] = ! $slip->isReleased()
            ? 'Not released'
            : ($slip->response?->status ?? 'Pending');

        // PhilHealth loan: read safely so the page still works before the column exists
        $deductions = array_merge($card['deductions'] ?? [], [
            'philhealth_loan' => (float) ($slip->getAttributes()['philhealth_loan'] ?? 0),
        ]);

        // HDMF is a fixed P200: replace whatever was stored and fix the totals to match
        $oldHdmf = (float) ($deductions['hdmf'] ?? 0);
        $deductions['hdmf'] = self::HDMF_FIXED;

        $card['deductions']       = $deductions;
        $card['total_deductions'] = round(
            (float) ($card['total_deductions'] ?? 0) - $oldHdmf + self::HDMF_FIXED,
            2
        );
        $card['net_pay']          = round(
            (float) ($card['gross_pay'] ?? 0) - $card['total_deductions'],
            2
        );

        return $card;
    }

    private function payPeriodLabel(?string $cutoff): string
    {
        $cutoff = trim((string) $cutoff);

        return $cutoff === '' ? '-' : $cutoff . ' Pay Period';
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