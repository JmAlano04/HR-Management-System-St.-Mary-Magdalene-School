<?php

namespace App\Services;

use App\Models\LoanRepayment;
use App\Models\LoanRequest;
use App\Models\Payslip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds `loan_repayments` from payslip deductions the employee has received
 * (payslip_responses.status = Approved), then reads balances from that table.
 * This is the server-side version of getLoanBalanceRows() in the old JS.
 */
class LoanBalanceService
{
    /** Idempotent: safe to run on every page load, a cron job, or after a payslip response. */
    public function sync(): void
    {
        $loans = LoanRequest::fullyApproved()->get()->groupBy('employee_id');

        DB::transaction(function () use ($loans) {
            foreach ($loans as $employeeId => $employeeLoans) {
                $payslips = Payslip::where('employee_id', $employeeId)
                    ->whereNotNull('transfer_date')
                    ->whereIn('id', DB::table('payslip_responses')
                        ->whereRaw('LOWER(status) = ?', ['approved'])
                        ->select('payslip_id'))
                    ->orderBy('transfer_date')
                    ->get();

                // Only rows generated from payslips are rebuilt; manual repayments (payslip_id NULL) are kept.
                LoanRepayment::whereIn('loan_request_id', $employeeLoans->pluck('id'))
                    ->whereNotNull('payslip_id')
                    ->delete();

                $rows = [];
                $now  = now();

                foreach ($employeeLoans->groupBy(fn (LoanRequest $l) => $l->payslipDeductionColumn()) as $column => $typeLoans) {
                    if ($column === '') {
                        continue; // loan type has no deduction column
                    }

                    $typeLoans = $typeLoans->sortBy(fn (LoanRequest $l) => Carbon::parse($l->approvedAt())->timestamp)->values();
                    $repaid    = $typeLoans->mapWithKeys(fn ($l) => [$l->id => 0.0])->all();

                    foreach ($payslips as $payslip) {
                        $payment = (float) $payslip->{$column};
                        if ($payment <= 0) {
                            continue;
                        }

                        foreach ($typeLoans as $loan) {
                            $remaining = (float) $loan->amount - $repaid[$loan->id];

                            // Ignore payslips dated before the loan was approved, or loans already paid off.
                            if (Carbon::parse($loan->approvedAt())->toDateString() > Carbon::parse($payslip->transfer_date)->toDateString() || $remaining <= 0) {
                                continue;
                            }

                            $applied = min($payment, $remaining);
                            $repaid[$loan->id] += $applied;
                            $payment -= $applied;

                            $rows[] = [
                                'loan_request_id' => $loan->id,
                                'payslip_id'      => $payslip->id,
                                'amount'          => round($applied, 2),
                                'paid_at'         => Carbon::parse($payslip->transfer_date)->toDateString(),
                                'created_at'      => $now,
                                'updated_at'      => $now,
                            ];

                            if ($payment <= 0) {
                                break;
                            }
                        }
                    }
                }

                if ($rows) {
                    LoanRepayment::insert($rows);
                }
            }
        });
    }

    /** One row per fully approved loan, with paid and outstanding amounts. */
    public function balanceRows(): Collection
    {
        return LoanRequest::fullyApproved()
            ->with('employee:id,employee_id,name')
            ->withSum('repayments as repaid_sum', 'amount')
            ->get()
            ->filter(fn (LoanRequest $l) => (float) $l->amount > 0)
            ->map(function (LoanRequest $l) {
                $principal = (float) $l->amount;
                $repaid    = min($principal, (float) $l->repaid_sum);

                return (object) [
                    'loan'        => $l,
                    'principal'   => $principal,
                    'repaid'      => $repaid,
                    'outstanding' => max(0, $principal - $repaid),
                ];
            })
            ->sortBy([
                fn ($a, $b) => strcmp((string) $a->loan->employee?->name, (string) $b->loan->employee?->name),
                fn ($a, $b) => strcmp($a->loan->loan_type, $b->loan->loan_type),
                fn ($a, $b) => Carbon::parse($a->loan->approvedAt())->timestamp <=> Carbon::parse($b->loan->approvedAt())->timestamp,
            ])
            ->values();
    }
}