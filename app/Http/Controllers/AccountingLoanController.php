<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingLoanController extends Controller
{
    /**
     * Accounting only: loan requests. Only HR-approved ones can be reviewed,
     * the rest are listed so Accounting can see where they stopped.
     */
    public function index()
    {
        $this->authorizeAccounting();

        $loans = LoanRequest::with('employee')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $awaiting = LoanRequest::where('hr_status', 'approved')
            ->where('accounting_status', 'pending')
            ->count();

        return view('loan', compact('loans', 'awaiting'));
    }

    /**
     * Approve (forward to Admin) or reject a loan request.
     */
    public function decide(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $this->authorizeAccounting();

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason'   => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
            'loan_id'  => ['nullable', 'integer'],   // lets the form reopen after a validation error
        ], [
            'reason.required_if' => 'Enter a reason for rejecting this request.',
        ]);

        $result = DB::transaction(function () use ($loanRequest, $data) {
            // lock the row so two reviewers cannot decide the same request twice
            $loan = LoanRequest::whereKey($loanRequest->id)->lockForUpdate()->first();

            if ($loan->hr_status !== 'approved' || $loan->accounting_status !== 'pending') {
                return false;
            }

            $approved = $data['decision'] === 'approved';

            $loan->update([
                'accounting_status'           => $data['decision'],
                'accounting_rejection_reason' => $approved ? null : trim($data['reason']),
                'accounting_reviewed_at'      => now(),
                'accounting_reviewed_by'      => Auth::id(),
            ]);

            return true;
        });

        if (!$result) {
            return back()->withErrors(['loan' => 'This request is not waiting for Accounting review anymore.']);
        }

        return back()->with(
            'success',
            $data['decision'] === 'approved' ? 'Loan request approved and forwarded to Admin.' : 'Loan request rejected.'
        );
    }

    private function authorizeAccounting(): void
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasRole('Accounting'),
            403,
            'Only Accounting can review loan requests here.'
        );
    }
}