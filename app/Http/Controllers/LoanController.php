<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
<<<<<<< HEAD
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
=======
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * One Financial Loan Management page for HR, Accounting and Admin.
 * The stage comes from LoanRequest::stageForUser() (Admin > HR > Accounting),
 * so each role sees the same layout with its own wording and its own decision column.
 */
class LoanController extends Controller
{
    /** Per-role wording and rules. 'scope' is the LoanRequest scope for that role's queue. */
    private const STAGES = [
        'hr' => [
            'heading'       => 'Employee loan requests',
            'subheading'    => 'Review each submission before it can move to Accounting.',
            'queueLabel'    => 'HR',
            'scope'         => 'awaitingHr',
            'decisionLabel' => 'HR decision',
            'rejectionHelp' => 'Provide a reason so the employee understands why this request cannot proceed.',
            'approvedMsg'   => 'Loan request approved and forwarded to Accounting.',
            'showBalances'  => false,
        ],
        'accounting' => [
            'heading'       => 'HR-approved loan requests',
            'subheading'    => 'Only requests approved by HR can be reviewed by Accounting.',
            'queueLabel'    => 'Accounting',
            'scope'         => 'awaitingAccounting',
            'decisionLabel' => 'Accounting decision',
            'rejectionHelp' => 'Provide a reason so the employee understands why this request cannot proceed.',
            'approvedMsg'   => 'Loan request approved and forwarded to Admin.',
            'showBalances'  => true,
        ],
        'admin' => [
            'heading'       => 'Accounting-approved loan requests',
            'subheading'    => 'Admin review opens only after both HR and Accounting approve.',
            'queueLabel'    => 'Admin',
            'scope'         => 'awaitingAdmin',
            'decisionLabel' => 'Admin decision',
            'rejectionHelp' => 'Provide a reason so the employee understands why this request is being rejected.',
            'approvedMsg'   => 'Loan request approved.',
            'showBalances'  => false,   // set true if Admin should also see balances
        ],
    ];

    public function index()
    {
        $stage  = $this->stage();
        $config = self::STAGES[$stage];

        /* ---------- Requests (paginated, newest first) ---------- */
        $loans = LoanRequest::with('employee')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $awaiting = LoanRequest::query()->{$config['scope']}()->count();

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
                'canReview' => $loan->canReviewAs($stage),
            ],
        ]);

        /* ---------- Approved loan balances (only where enabled) ---------- */
        $balances         = collect();
        $totalOutstanding = 0;

        if ($config['showBalances']) {
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
        }

        return view('loan', compact(
            'stage', 'config', 'loans', 'awaiting', 'details', 'balances', 'totalOutstanding'
        ));
    }

    /**
     * Approve or reject, for whichever stage the signed-in role owns.
     */
    public function decide(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $stage  = $this->stage();
        $config = self::STAGES[$stage];

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason'   => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
            'loan_id'  => ['nullable', 'integer'],   // lets the modal reopen after a validation error
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        ], [
            'reason.required_if' => 'Enter a reason for rejecting this request.',
        ]);

<<<<<<< HEAD
        $stage = LoanRequest::stageForUser($request->user());
        abort_unless($stage, 403);

        $updated = DB::transaction(function () use ($loanRequest, $data, $request, $stage): bool {
            $loan = LoanRequest::query()->lockForUpdate()->findOrFail($loanRequest->id);
=======
        $result = DB::transaction(function () use ($loanRequest, $data, $stage) {
            // lock the row so two reviewers cannot decide the same request twice
            $loan = LoanRequest::whereKey($loanRequest->id)->lockForUpdate()->first();

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            if (! $loan->canReviewAs($stage)) {
                return false;
            }

<<<<<<< HEAD
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
=======
            $approved = $data['decision'] === 'approved';

            $update = [
                "{$stage}_status"           => $data['decision'],
                "{$stage}_rejection_reason" => $approved ? null : trim($data['reason']),
                "{$stage}_reviewed_at"      => now(),
                "{$stage}_reviewed_by"      => Auth::id(),
            ];

            // HR approval opens the Accounting stage. Rejections need nothing extra:
            // effectiveStatus() already shows later stages as "Waiting".
            if ($stage === 'hr' && $approved) {
                $update['accounting_status'] = 'pending';
            }

            $loan->update($update);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

            return true;
        });

<<<<<<< HEAD
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
=======
        if (! $result) {
            return back()->withErrors([
                'loan' => 'This request is not waiting for ' . $config['queueLabel'] . ' review anymore.',
            ]);
        }

        return back()->with(
            'success',
            $data['decision'] === 'approved' ? $config['approvedMsg'] : 'Loan request rejected.'
        );
    }

    /** 'hr' | 'accounting' | 'admin', or 403 for any other user. */
    private function stage(): string
    {
        $stage = LoanRequest::stageForUser(Auth::user());

        abort_unless($stage !== null, 403, 'You are not allowed to review loan requests.');

        return $stage;
    }
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
