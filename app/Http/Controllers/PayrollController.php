<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
<<<<<<< HEAD
use App\Services\HrmsNotifier;
=======
use App\Support\PayslipPresenter;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
<<<<<<< HEAD
    /** Monthly wage = daily wage x 26 working days. */
    private const WORKING_DAYS_PER_MONTH = 26;

    /**
     * Admin, HR, and Accounting can view payroll.
     * HR sees the HR approval stage; everyone else sees the Admin stage.
     */
    public function index()
    {
        $this->authorizePayrollView();

        $prefix = $this->stagePrefix();          // 'hr' or 'admin'
        $column = "{$prefix}_status";

        $sent = Payslip::sentToAdmin();
        $pending = (clone $sent)->where($column, Payslip::PENDING);
        $approvable = clone $pending;

        if ($prefix === Payslip::STAGE_ADMIN) {
            $approvable->whereRaw('LOWER(hr_status) = ?', ['approved']);
        }

        $summary = [
            'total' => (clone $sent)->count(),
            'pending' => $pending->count(),
            'approvable' => $approvable->count(),
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'approved' => (clone $sent)->where($column, Payslip::APPROVED)->count(),
            'rejected' => (clone $sent)->where($column, Payslip::REJECTED)->count(),
        ];

<<<<<<< HEAD
        $payslips = Payslip::sentToAdmin()
            ->with(['employee', 'response'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // NOTE: change to view('payroll') if your file is resources/views/payroll.blade.php
        return view('payroll', [
            'payslips' => $payslips,
            'cards' => $payslips->getCollection()
                ->map(fn (Payslip $slip) => $this->card($slip))
                ->keyBy('id'),
            'summary' => $summary,
=======
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

        // employee.user is loaded once so the administrative-role check does not query per payslip
        $payslips = $listQuery
            ->with(['employee.user.roles', 'response'])
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

<<<<<<< HEAD
        if ($prefix === Payslip::STAGE_ADMIN
            && $payslip->stageStatus(Payslip::STAGE_HR) !== 'approved') {
            return back()->withErrors([
                'payslip' => 'HR approval is required before Admin can approve this payslip.',
            ]);
        }

        $payslip->update([
            "{$prefix}_status" => Payslip::APPROVED,
=======
        $payslip->update([
            "{$prefix}_status"      => Payslip::APPROVED,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            "{$prefix}_reviewed_at" => now(),
            "{$prefix}_reviewed_by" => Auth::id(),
        ]);

<<<<<<< HEAD
        $payslip->refresh();
        $prefix === Payslip::STAGE_HR
            ? HrmsNotifier::payslipHrDecided($payslip)
            : HrmsNotifier::payslipAdminDecided($payslip);

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
            'reason' => ['required', 'string', 'max:1000'],
=======
            'reason'     => ['required', 'string', 'max:1000'],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        ]);

        $payslip = Payslip::sentToAdmin()->findOrFail($data['payslip_id']);

        if ($payslip->{"{$prefix}_status"} !== Payslip::PENDING) {
            return back()->withErrors([
                'payslip' => 'This payslip has already been reviewed.',
            ]);
        }

<<<<<<< HEAD
        if ($prefix === Payslip::STAGE_ADMIN
            && $payslip->stageStatus(Payslip::STAGE_HR) === 'pending') {
            return back()->withErrors([
                'payslip' => 'HR must approve or reject this payslip before Admin can review it.',
            ]);
        }

        $payslip->update([
            "{$prefix}_status" => Payslip::REJECTED,
            "{$prefix}_rejection_reason" => trim($data['reason']),
            "{$prefix}_reviewed_at" => now(),
            "{$prefix}_reviewed_by" => Auth::id(),
        ]);

        $payslip->refresh();
        $prefix === Payslip::STAGE_HR
            ? HrmsNotifier::payslipHrDecided($payslip)
            : HrmsNotifier::payslipAdminDecided($payslip);

=======
        $payslip->update([
            "{$prefix}_status"           => Payslip::REJECTED,
            "{$prefix}_rejection_reason" => trim($data['reason']),
            "{$prefix}_reviewed_at"      => now(),
            "{$prefix}_reviewed_by"      => Auth::id(),
        ]);

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        return back()->with('success', 'Payslip rejected.');
    }

    /**
<<<<<<< HEAD
     * Admin or HR: approve all of their pending payslips.
=======
     * Admin or HR: approve all of their pending ACTIVE payslips.
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
     */
    public function approveAll(): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

<<<<<<< HEAD
        $pending = Payslip::sentToAdmin()
            ->where("{$prefix}_status", Payslip::PENDING);

        if ($prefix === Payslip::STAGE_ADMIN) {
            $pending->whereRaw('LOWER(hr_status) = ?', ['approved']);
        }

        $payslipIds = (clone $pending)->pluck('id');
        $count = $pending->update([
            "{$prefix}_status" => Payslip::APPROVED,
            "{$prefix}_reviewed_at" => now(),
            "{$prefix}_reviewed_by" => Auth::id(),
        ]);

        if ($count === 0) {
            return back()->withErrors([
                'payslip' => $prefix === Payslip::STAGE_ADMIN
                    ? 'There are no pending payslips approved by HR.'
                    : 'There are no pending payslips to approve.',
            ]);
        }

        foreach (Payslip::whereIn('id', $payslipIds)->get() as $payslip) {
            $prefix === Payslip::STAGE_HR
                ? HrmsNotifier::payslipHrDecided($payslip)
                : HrmsNotifier::payslipAdminDecided($payslip);
        }

        return back()->with(
            'success',
            $count.' '.($count === 1 ? 'payslip' : 'payslips').' approved.'
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        );
    }

    /**
<<<<<<< HEAD
     * Data for the payslip modal: the model's own card plus
     * the monthly wage, the pay period label and the PhilHealth loan.
     */
    private function card(Payslip $slip): array
    {
        $card = $slip->toCard();

        // Monthly wage = daily wage x 26
        $card['monthly_wage'] = round((float) $slip->daily_wage * self::WORKING_DAYS_PER_MONTH, 2);

        $card['cutoff_label'] = $slip->cutoffDateLabel();

        // Employee's answer for THIS payslip (controls the Download button in the modal)
        $card['employee_status'] = ! $slip->isReleased()
            ? 'Not released'
            : ($slip->response?->status ?? 'Pending');

        // PhilHealth loan: read safely so the page still works before the column exists
        $card['deductions'] = array_merge($card['deductions'] ?? [], [
            'philhealth_loan' => (float) ($slip->getAttributes()['philhealth_loan'] ?? 0),
        ]);

        return $card;
=======
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

    // ------------------------------------------------------------------

    /** Payslip popup data: the shared builder (same numbers for every role) + the employee's answer. */
    private function card(Payslip $p): array
    {
        return PayslipPresenter::card($p) + [
            // Controls the Download button in the modal
            'employee_status' => ! $p->isReleased()
                ? 'Not released'
                : ($p->response?->status ?? 'Pending'),
        ];
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
}
=======
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
