<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use App\Models\User;
<<<<<<< HEAD
use Illuminate\Support\Facades\Auth;
=======
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

class AccountingLoanController extends Controller
{
    /**
<<<<<<< HEAD
     * Accounting can review requests after HR approves them.
=======
     * Accounting only.
     *  1. Approved loan balances (fully approved loans, minus payslip repayments)
     *  2. All loan requests; only HR-approved + Accounting-pending ones can be decided
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
     */
    public function index()
    {
        $this->authorizeAccounting();

<<<<<<< HEAD
=======
        /* ---------- Requests (paginated, newest first) ---------- */
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $loans = LoanRequest::with('employee')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

<<<<<<< HEAD
        $approvedLoans = LoanRequest::fullyApproved()
            ->with('employee')
            ->withSum('repayments', 'amount')
            ->orderByDesc('updated_at')
            ->get();

        $loanBalances = $approvedLoans->map(fn (LoanRequest $loan) => [
            'employee' => $loan->employee?->name ?? 'Employee',
            'employee_number' => $loan->employee?->employee_id ?? 'ID unavailable',
            'loan_type' => $loan->loan_type ?: 'Not provided',
            'approved_amount' => (float) $loan->amount,
            'paid' => (float) ($loan->repayments_sum_amount ?? 0),
            'outstanding' => $loan->outstandingBalance(),
        ]);
        $totalOutstanding = round($loanBalances->sum('outstanding'), 2);
        $awaitingAccounting = LoanRequest::where('hr_status', 'approved')
            ->where('accounting_status', 'pending')
            ->count();

        return view('loan', compact('loans', 'loanBalances', 'totalOutstanding', 'awaitingAccounting'));
=======
        $awaiting = LoanRequest::awaitingAccounting()->count();

        // Data the details dialog reads on the client (keyed by loan id)
        $details = $loans->getCollection()->mapWithKeys(fn (LoanRequest $loan) => [
            $loan->id => [
                'name'      => $loan->employee?->name ?? 'Employee',
                'number'    => $loan->employee?->employee_id ?? 'ID unavailable',
                'status'    => $loan->overall_status,
                'type'      => $loan->loan_type ?: 'Not provided',
                'amount'    => $loan->formatted_amount,
                'submitted' => $loan->requested_at
                    ? $loan->requested_at->format('M j, Y, g:i A')
                    : 'Date unavailable',
                'reason'    => $loan->reason ?: 'No purpose provided.',
                'rejection' => $loan->rejection_reason_text,
                'canReview' => $loan->can_accounting_review,
            ],
        ]);

        /* ---------- Approved loan balances ---------- */
        $balances = LoanRequest::with('employee')
            ->fullyApproved()
            ->withSum('repayments', 'amount')
            ->get()
            ->map(function (LoanRequest $loan) {
                $principal = (float) $loan->amount;
                $repaid    = min($principal, (float) ($loan->repayments_sum_amount ?? 0));

                return [
                    'loan'        => $loan,
                    'principal'   => $principal,
                    'repaid'      => $repaid,
                    'outstanding' => max(0, round($principal - $repaid, 2)),
                ];
            })
            ->filter(fn ($row) => $row['principal'] > 0 && $row['loan']->employee)
            ->sort(fn ($a, $b) =>
                strcasecmp($a['loan']->employee->name, $b['loan']->employee->name)
                ?: strcasecmp((string) $a['loan']->loan_type, (string) $b['loan']->loan_type)
                ?: ($a['loan']->approvedAt()?->timestamp <=> $b['loan']->approvedAt()?->timestamp)
            )
            ->values();

        $totalOutstanding = $balances->sum('outstanding');

        return view('loan', compact(
            'loans', 'awaiting', 'details', 'balances', 'totalOutstanding'
        ));
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
            'loan_id'  => ['nullable', 'integer'],   // lets the modal reopen after a validation error
        ], [
            'reason.required_if' => 'Enter a reason for rejecting this request.',
        ]);

        $result = DB::transaction(function () use ($loanRequest, $data) {
            // lock the row so two reviewers cannot decide the same request twice
            $loan = LoanRequest::whereKey($loanRequest->id)->lockForUpdate()->first();

            if (! $loan->can_accounting_review) {
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

        if (! $result) {
            return back()->withErrors(['loan' => 'This request is not waiting for Accounting review anymore.']);
        }

        return back()->with(
            'success',
            $data['decision'] === 'approved'
                ? 'Loan request approved and forwarded to Admin.'
                : 'Loan request rejected.'
        );
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    private function authorizeAccounting(): void
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasRole('Accounting'),
            403,
<<<<<<< HEAD
            'Only Accounting can view loan requests here.'
        );
    }
}
=======
            'Only Accounting can review loan requests here.'
        );
    }
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
