<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use App\Services\HrmsNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    /**
     * Admin, HR, and Accounting can view payroll.
     * HR sees the HR approval stage; everyone else sees the Admin stage.
     */
    public function index()
    {
        $this->authorizePayrollView();

        $prefix = $this->stagePrefix();          // 'hr' or 'admin'

        $sentToAdminQuery = fn () => Payslip::query()->sentToAdmin();

        $summary = [
            'total' => $sentToAdminQuery()->count(),
            'pending' => $sentToAdminQuery()->stageStatus($prefix, Payslip::PENDING)->count(),
            'approved' => $sentToAdminQuery()->stageStatus($prefix, Payslip::APPROVED)->count(),
            'rejected' => $sentToAdminQuery()->stageStatus($prefix, Payslip::REJECTED)->count(),
        ];

        $payslips = $sentToAdminQuery()
            ->with(['employee', 'response'])
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        // NOTE: change to view('payroll') if your file is resources/views/payroll.blade.php
        return view('payroll', [
            'payslips' => $payslips,
            'cards' => $payslips->getCollection()
                ->map->toCard()
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

        if (! $payslip->isStagePending($prefix)) {
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

        $payslip->approveStage($prefix, Auth::id());
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

        if (! $payslip->isStagePending($prefix)) {
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

        $payslip->rejectStage($prefix, $data['reason'], Auth::id());
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
            ->stageStatus($prefix, Payslip::PENDING);
        if ($prefix === Payslip::STAGE_ADMIN) {
            $pending->whereRaw('LOWER(hr_status) = ?', ['approved']);
        }

        $payslipIds = (clone $pending)->pluck('id');
        $count = $pending
            ->update([
                Payslip::stageColumn($prefix, 'status') => Payslip::APPROVED,
                Payslip::stageColumn($prefix, 'reviewed_at') => now(),
                Payslip::stageColumn($prefix, 'reviewed_by') => Auth::id(),
            ]);

        if ($count === 0) {
            return back()->withErrors([
                'payslip' => 'There are no pending payslips to approve.',
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
     * Admin, HR, and Accounting: download any submitted payslip as PDF.
     */
    public function download(Payslip $payslip)
    {
        $this->authorizePayrollView();

        abort_unless($payslip->sent_to_admin_at !== null, 404);

        $payslip->load(['employee', 'response']);

        $date = ($payslip->transfer_date ?? $payslip->created_at)->format('Y-m');
        $name = "payslip-{$payslip->employee?->employee_id}-{$date}-{$payslip->cutoff}.pdf";

        return Pdf::loadView('pdf', [
            'card' => $payslip->toEmployeeCard(),
            'payslip' => $payslip,
        ])->setPaper('a4')->download($name);
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
     * Only Admin and HR can review. Returns the stage for the
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

        return $user instanceof User && $user->hasRole('HR')
            ? Payslip::STAGE_HR
            : Payslip::STAGE_ADMIN;
    }
}
