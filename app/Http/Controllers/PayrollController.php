<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use App\Services\HrmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
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
            'approved' => (clone $sent)->where($column, Payslip::APPROVED)->count(),
            'rejected' => (clone $sent)->where($column, Payslip::REJECTED)->count(),
        ];

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

        if ($prefix === Payslip::STAGE_ADMIN
            && $payslip->stageStatus(Payslip::STAGE_HR) !== 'approved') {
            return back()->withErrors([
                'payslip' => 'HR approval is required before Admin can approve this payslip.',
            ]);
        }

        $payslip->update([
            "{$prefix}_status" => Payslip::APPROVED,
            "{$prefix}_reviewed_at" => now(),
            "{$prefix}_reviewed_by" => Auth::id(),
        ]);

        $payslip->refresh();
        $prefix === Payslip::STAGE_HR
            ? HrmsNotifier::payslipHrDecided($payslip)
            : HrmsNotifier::payslipAdminDecided($payslip);

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
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $payslip = Payslip::sentToAdmin()->findOrFail($data['payslip_id']);

        if ($payslip->{"{$prefix}_status"} !== Payslip::PENDING) {
            return back()->withErrors([
                'payslip' => 'This payslip has already been reviewed.',
            ]);
        }

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

        return back()->with('success', 'Payslip rejected.');
    }

    /**
     * Admin or HR: approve all of their pending payslips.
     */
    public function approveAll(): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

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
        );
    }

    /**
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
