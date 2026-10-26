<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    /**
     * Financial Loan Dashboard.
     * ?tab=active (default) | archive, plus ?month=YYYY-MM (Archive tab only).
     */
    public function index(Request $request): View
    {
        $tab   = $request->query('tab') === 'archive' ? 'archive' : 'active';
        $month = $tab === 'archive' ? $request->query('month') : null;

        $awaitingAdmin = LoanRequest::awaitingAdmin()->staffArchived(false)->count();

        $loans = LoanRequest::query()
            ->with('employee')
            ->staffArchived($tab === 'archive')
            ->inMonth($month)
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('loan', compact('loans', 'awaitingAdmin', 'tab', 'month'));
    }

    /**
     * Approve / Reject. HR decides the HR stage, Admin decides the Admin stage.
     * A reason is required when rejecting.
     */
    public function decide(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason'   => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], [
            'reason.required_if' => 'Enter a reason for rejecting this request.',
        ]);

        $stage = LoanRequest::stageForUser($request->user());
        abort_unless($stage, 403);

        if (! $loanRequest->canReviewAs($stage)) {
            return back()->withErrors([
                'decision' => 'This request is not open for your review (or was already decided).',
            ]);
        }

        $loanRequest->update([
            "{$stage}_status"           => $data['decision'],
            "{$stage}_rejection_reason" => $data['decision'] === 'rejected' ? trim($data['reason']) : null,
            "{$stage}_reviewed_at"      => now(),
            "{$stage}_reviewed_by"      => $request->user()->id,
        ]);

        return back()->with('success', 'Loan request ' . $data['decision'] . '.');
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