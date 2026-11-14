<?php

namespace App\Services;

use App\Models\LoanRepayment;
use App\Models\LoanRequest;
use App\Models\Payslip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CashAdvancePayrollService
{
    public function loansForPeriod(string $period, iterable $employeeIds): Collection
    {
        $periodStart = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
        $previousMonth = $periodStart->copy()->subMonthNoOverflow();

        return LoanRequest::query()
            ->fullyApproved()
            ->where('loan_type', 'Cash Advance')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('admin_reviewed_at', [
                $previousMonth->toDateString(),
                $previousMonth->copy()->endOfMonth()->endOfDay(),
            ])
            ->withSum([
                'repayments as repaid_before_period' => fn ($query) => $query
                    ->where('paid_at', '<', $periodStart->toDateString()),
            ], 'amount')
            ->orderBy('id')
            ->get()
            ->map(function (LoanRequest $loan) {
                $loan->payroll_balance = max(
                    0,
                    round((float) $loan->amount - (float) ($loan->repaid_before_period ?? 0), 2)
                );

                return $loan;
            })
            ->filter(fn (LoanRequest $loan) => $loan->payroll_balance > 0);
    }

    public function total(Collection $loans): float
    {
        return round($loans->sum('payroll_balance'), 2);
    }

    public function recordPayslipRepayments(Payslip $payslip): void
    {
        $period = $payslip->transfer_date->format('Y-m');
        $cutoff = $payslip->cutoff;
        $loans = $this->loansForPeriod($period, [$payslip->employee_id]);
        $paidAt = $payslip->transfer_date->copy();
        $cutoffDay = $cutoff === '15th' ? 15 : 30;
        $paidAt->day(min($cutoffDay, $paidAt->daysInMonth));

        foreach ($this->installmentAllocations($loans, $cutoff) as $loanId => $installment) {
            if ($installment <= 0) {
                continue;
            }

            $repayment = LoanRepayment::query()
                ->where('loan_request_id', $loanId)
                ->where('payslip_id', $payslip->id)
                ->first();

            if ($repayment) {
                $repayment->update(['amount' => $installment, 'paid_at' => $paidAt->toDateString()]);
            } else {
                LoanRepayment::create([
                    'loan_request_id' => $loanId,
                    'amount' => $installment,
                    'paid_at' => $paidAt->toDateString(),
                    'payslip_id' => $payslip->id,
                ]);
            }
        }
    }

    public function installmentAllocations(Collection $loans, string $cutoff): array
    {
        $firstCutoffRemaining = round($this->total($loans) / 2, 2);
        $allocations = [];

        foreach ($loans as $loan) {
            $balance = (float) $loan->payroll_balance;
            $firstCutoffAmount = min($balance, $firstCutoffRemaining);
            $firstCutoffRemaining = round($firstCutoffRemaining - $firstCutoffAmount, 2);
            $allocations[$loan->id] = $cutoff === '15th'
                ? $firstCutoffAmount
                : round($balance - $firstCutoffAmount, 2);
        }

        return $allocations;
    }
}
