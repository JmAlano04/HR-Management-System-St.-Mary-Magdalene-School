<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoanController extends Controller
{
    /**
     * Financial Loan Dashboard — list of loan requests + count awaiting Admin.
     */
    public function index(): View
    {
        $awaitingAdmin = DB::table('loan_requests')
            ->where('admin_status', 'pending')
            ->count();

        $loans = LoanRequest::query()
            ->with('employee')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('loan', compact('loans', 'awaitingAdmin'));
    }

    /**
     * Admin Approve / Reject (reason required when rejecting).
     */
    public function decide(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason'   => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], [
            'reason.required_if' => 'Enter a reason for rejecting this request.',
        ]);

        if (! $loanRequest->can_review) {
            return back()->withErrors([
                'decision' => 'This request is not open for Admin review (or was already decided).',
            ]);
        }

        $loanRequest->update([
            'admin_status'           => $data['decision'],
            'admin_rejection_reason' => $data['decision'] === 'rejected' ? trim($data['reason']) : null,
            'admin_reviewed_at'      => now(),
            'admin_reviewed_by'      => $request->user()?->id,
        ]);

        return back()->with('success', 'Loan request ' . $data['decision'] . '.');
    }
}