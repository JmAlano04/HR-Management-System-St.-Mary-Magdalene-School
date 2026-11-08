<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Services\HrmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoanController extends Controller
{
    /**
     * Financial Loan Dashboard.
     * ?tab=active (default) | archive, plus ?month=YYYY-MM (Archive tab only).
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'archive' ? 'archive' : 'active';
        $month = $tab === 'archive' ? $request->query('month') : null;

        $loans = LoanRequest::query()
            ->with('employee')
            ->staffArchived($tab === 'archive')
            ->inMonth($month)
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $awaitingHrCount = LoanRequest::query()
            ->where('hr_status', 'pending')
            ->whereNull('staff_archived_at')
            ->count();
        $awaitingAccounting = LoanRequest::query()
            ->where('hr_status', 'approved')
            ->where('accounting_status', 'pending')
            ->count();

        return view('loan', compact('loans', 'tab', 'month', 'awaitingHrCount', 'awaitingAccounting'));
    }

    /**
     * HR, Accounting, then Admin decide in sequence. A rejection is final.
     */
    public function decide(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], [
            'reason.required_if' => 'Enter a reason for rejecting this request.',
        ]);

        $stage = LoanRequest::stageForUser($request->user());
        abort_unless($stage, 403);

        $updated = DB::transaction(function () use ($loanRequest, $data, $request, $stage): bool {
            $loan = LoanRequest::query()->lockForUpdate()->findOrFail($loanRequest->id);
            if (! $loan->canReviewAs($stage)) {
                return false;
            }

            $loan->update([
                "{$stage}_status" => $data['decision'],
                "{$stage}_rejection_reason" => $data['decision'] === 'rejected' ? trim($data['reason']) : null,
                "{$stage}_reviewed_at" => now(),
                "{$stage}_reviewed_by" => $request->user()->id,
            ]);

            if ($stage === 'hr' && $data['decision'] === 'rejected') {
                $loan->update([
                    'accounting_status' => 'rejected',
                    'accounting_rejection_reason' => trim($data['reason']),
                    'accounting_reviewed_at' => now(),
                    'admin_status' => 'rejected',
                    'admin_rejection_reason' => trim($data['reason']),
                    'admin_reviewed_at' => now(),
                ]);
            }

            if ($stage === 'accounting' && $data['decision'] === 'rejected') {
                $loan->update([
                    'admin_status' => 'rejected',
                    'admin_rejection_reason' => trim($data['reason']),
                    'admin_reviewed_at' => now(),
                ]);
            }

            return true;
        });

        if (! $updated) {
            return back()->withErrors([
                'decision' => "This request is not open for {$stage} review (or was already decided).",
            ]);
        }

        $loan = LoanRequest::findOrFail($loanRequest->id);
        switch ($stage) {
            case 'hr':
                HrmsNotifier::loanHrDecided($loan);
                break;
            case 'accounting':
                HrmsNotifier::loanAccountingDecided($loan);
                break;
            case 'admin':
                HrmsNotifier::loanAdminDecided($loan);
                break;
        }

        return back()->with('success', ucfirst($stage).' '.$data['decision'].' the loan request.');
    }

    /**
     * Move a finished request to the Archive tab.
     */
    public function archive(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        if (! $loanRequest->can_archive) {
            return back()->withErrors([
                'archive' => 'Only approved or rejected requests can be archived.',
            ]);
        }

        $loanRequest->update([
            'staff_archived_at' => now(),
            'staff_archived_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Loan request archived.');
    }

    /**
     * Move a request back to the Active tab.
     */
    public function restore(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $loanRequest->update([
            'staff_archived_at' => null,
            'staff_archived_by' => null,
        ]);

        return back()->with('success', 'Loan request restored.');
    }
}
