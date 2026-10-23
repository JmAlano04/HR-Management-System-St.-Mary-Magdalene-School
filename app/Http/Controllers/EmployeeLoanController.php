<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeLoanController extends Controller
{
    /** GET /employee/loan-requests[?tab=archived] */
    public function index(Request $request): View
    {
        $employee = $request->user()?->employee;   // ASSUMPTION: users.employee relationship
        $archived = $request->query('tab') === 'archived';

        $loans = collect();
        $balances = ['total' => 0, 'byType' => collect(LoanRequest::TYPES)->mapWithKeys(fn ($t) => [$t => 0])];

        if ($employee) {
            $loans = LoanRequest::forEmployee($employee->id)
                ->archived($archived)
                ->orderByDesc('requested_at')
                ->paginate(10)
                ->withQueryString();

            $approved = LoanRequest::forEmployee($employee->id)
                ->fullyApproved()
                ->withSum('repayments', 'amount')
                ->get();

            $byType = collect(LoanRequest::TYPES)->mapWithKeys(fn ($type) => [
                $type => round($approved->where('loan_type', $type)->sum(fn ($l) => $l->outstandingBalance()), 2),
            ]);
            $balances = ['total' => round($byType->sum(), 2), 'byType' => $byType];
        }

        return view('loan', compact('employee', 'loans', 'balances', 'archived'));
    }

    /** POST /employee/loan-requests */
    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'loan_type' => ['required', 'string', Rule::in(LoanRequest::TYPES)],
            'amount'    => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'reason'    => ['required', 'string', 'max:1000'],
        ]);

        if ($data['loan_type'] === 'Cash Advance' && $data['amount'] > LoanRequest::CASH_ADVANCE_LIMIT) {
            return back()->withInput()->withErrors(['amount' => 'Cash Advance requests are limited to PHP 2,000.00.']);
        }

        // hr/accounting/admin status default to "pending" in the DB; the view shows
        // later stages as "locked" via LoanRequest::effectiveStatus().
        LoanRequest::create([
            'employee_id'  => $employee->id,
            'loan_type'    => $data['loan_type'],
            'amount'       => $data['amount'],
            'reason'       => trim($data['reason']),
            'requested_at' => now(),
        ]);

        return redirect()->route('loan.loan-employee.index')->with('status', 'Request sent to HR for validation.');
    }

    /** POST /employee/loan-requests/archive  (ids[]) */
    public function archive(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = LoanRequest::forEmployee($employee->id)
            ->whereNull('archived_at')
            ->whereIn('id', $data['ids'])
            ->update(['archived_at' => now()]);

        return redirect()->route('loan.loan-employee.index')
            ->with('status', "{$count} loan request" . ($count === 1 ? '' : 's') . ' archived.');
    }

    /** POST /employee/loan-requests/{loanRequest}/restore */
    public function restore(Request $request, int $loanRequest): RedirectResponse
    {
        LoanRequest::forEmployee($this->employee($request)->id)
            ->findOrFail($loanRequest)
            ->update(['archived_at' => null]);

        return redirect()->route('loan.loan-employee.index', ['tab' => 'archived'])
            ->with('status', 'Loan request restored to active requests.');
    }

    private function employee(Request $request)
    {
        return $request->user()?->employee ?? abort(403, 'No employee profile is linked to this account.');
    }
}