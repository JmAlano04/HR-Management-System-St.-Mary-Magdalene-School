<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
<<<<<<< HEAD
use App\Services\HrmsNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
=======
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

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
<<<<<<< HEAD
=======
        $stage  = $prefix === Payslip::STAGE_HR ? 'HR' : 'Admin';
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        $sentToAdminQuery = fn () => Payslip::query()->sentToAdmin();

        $summary = [
<<<<<<< HEAD
            'total' => $sentToAdminQuery()->count(),
            'pending' => $sentToAdminQuery()->stageStatus($prefix, Payslip::PENDING)->count(),
=======
            'total'    => $sentToAdminQuery()->count(),
            'pending'  => $sentToAdminQuery()->stageStatus($prefix, Payslip::PENDING)->count(),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'approved' => $sentToAdminQuery()->stageStatus($prefix, Payslip::APPROVED)->count(),
            'rejected' => $sentToAdminQuery()->stageStatus($prefix, Payslip::REJECTED)->count(),
        ];

<<<<<<< HEAD
=======
        // Ready for employee release only when BOTH Admin and HR approved everything
        $allApproved = $summary['total'] > 0
            && ! $sentToAdminQuery()
                ->where(fn ($q) => $q
                    ->where('admin_status', '!=', Payslip::APPROVED)
                    ->orWhere('hr_status', '!=', Payslip::APPROVED))
                ->exists();

        $statusText = match (true) {
            $summary['total'] === 0 =>
                'Waiting for salary transfer',

            $allApproved =>
                'All payslips approved and ready for employee release',

            default =>
                "{$summary['approved']} of {$summary['total']} payslips approved by {$stage}",
        };

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $payslips = $sentToAdminQuery()
            ->with(['employee', 'response'])
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        // NOTE: change to view('payroll') if your file is resources/views/payroll.blade.php
        return view('payroll', [
<<<<<<< HEAD
            'payslips' => $payslips,
            'cards' => $payslips->getCollection()
                ->map->toCard()
                ->keyBy('id'),
            'summary' => $summary,
=======
            'payslips'   => $payslips,
            'cards'      => $payslips->getCollection()
                ->map->toCard()
                ->keyBy('id'),
            'summary'    => $summary,
            'statusText' => $statusText,
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

        if (! $payslip->isStagePending($prefix)) {
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

        $payslip->approveStage($prefix, Auth::id());
        $payslip->refresh();
        $prefix === Payslip::STAGE_HR
            ? HrmsNotifier::payslipHrDecided($payslip)
            : HrmsNotifier::payslipAdminDecided($payslip);
=======
        $payslip->approveStage($prefix, Auth::id());
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

        if (! $payslip->isStagePending($prefix)) {
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

        $payslip->rejectStage($prefix, $data['reason'], Auth::id());
        $payslip->refresh();
        $prefix === Payslip::STAGE_HR
            ? HrmsNotifier::payslipHrDecided($payslip)
            : HrmsNotifier::payslipAdminDecided($payslip);
=======
        $payslip->rejectStage($prefix, $data['reason'], Auth::id());
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        return back()->with('success', 'Payslip rejected.');
    }

    /**
     * Admin or HR: approve all of their pending payslips.
     */
    public function approveAll(): RedirectResponse
    {
        $prefix = $this->authorizeReviewer();

<<<<<<< HEAD
        $pending = Payslip::sentToAdmin()
            ->stageStatus($prefix, Payslip::PENDING);
        if ($prefix === Payslip::STAGE_ADMIN) {
            $pending->whereRaw('LOWER(hr_status) = ?', ['approved']);
        }

        $payslipIds = (clone $pending)->pluck('id');
        $count = $pending
            ->update([
                Payslip::stageColumn($prefix, 'status') => Payslip::APPROVED,
=======
        $count = Payslip::sentToAdmin()
            ->stageStatus($prefix, Payslip::PENDING)
            ->update([
                Payslip::stageColumn($prefix, 'status')      => Payslip::APPROVED,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                Payslip::stageColumn($prefix, 'reviewed_at') => now(),
                Payslip::stageColumn($prefix, 'reviewed_by') => Auth::id(),
            ]);

        if ($count === 0) {
            return back()->withErrors([
                'payslip' => 'There are no pending payslips to approve.',
            ]);
        }

<<<<<<< HEAD
        foreach (Payslip::whereIn('id', $payslipIds)->get() as $payslip) {
            $prefix === Payslip::STAGE_HR
                ? HrmsNotifier::payslipHrDecided($payslip)
                : HrmsNotifier::payslipAdminDecided($payslip);
        }

        return back()->with(
            'success',
            $count.' '.($count === 1 ? 'payslip' : 'payslips').' approved.'
=======
        return back()->with(
            'success',
            $count . ' ' . ($count === 1 ? 'payslip' : 'payslips') . ' approved.'
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
            'card' => $payslip->toEmployeeCard(),
=======
            'card'    => $payslip->toEmployeeCard(),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
}
=======
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
