<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;

/**
 * Server-side version of the old updateLeaveBalances() / getLeaveBalanceForType().
 * Only APPROVED requests deduct the balance. Counted per calendar year of start_date.
 */
class LeaveBalanceService
{
    /** regular | non-regular | unknown */
    public function status(Employee $employee): string
    {
        return match ($employee->employment_status) {
            'regular'     => 'regular',
            'non-regular' => 'non-regular',
            default       => 'unknown',
        };
    }

    /**
     * @return array{
     *   status:string,
     *   total:array{initial:int,used:int,remaining:int},
     *   types:array<string,array{initial:int,used:int,remaining:int}>
     * }
     */
    public function balances(Employee $employee, ?int $year = null): array
    {
        $year   = $year ?? now()->year;
        $status = $this->status($employee);
        $nonReg = $status === 'non-regular';

        $nonRegType = config('leave.non_regular.type');

        $approved = LeaveRequest::forUser($employee->user_id)
            ->approved()
            ->whereYear('start_date', $year)
            ->get(['type', 'start_date', 'end_date']);

        // Non-regular: only the allowed type counts
        $eligible  = $nonReg ? $approved->where('type', $nonRegType) : $approved;
        $totalUsed = (int) $eligible->sum(fn (LeaveRequest $r) => $r->days_count);

        $totalInitial = $nonReg ? (int) config('leave.non_regular.days') : (int) config('leave.total');

        $types = [];
        foreach (config('leave.types') as $type => $configured) {
            if ($nonReg && $type !== $nonRegType) {
                continue; // hidden for non-regular employees
            }
            $initial = $nonReg ? (int) config('leave.non_regular.days') : (int) $configured;
            $used    = (int) $eligible->where('type', $type)->sum(fn (LeaveRequest $r) => $r->days_count);

            $types[$type] = [
                'initial'   => $initial,
                'used'      => $used,
                'remaining' => max(0, $initial - $used),
            ];
        }

        return [
            'status' => $status,
            'total'  => [
                'initial'   => $totalInitial,
                'used'      => $totalUsed,
                'remaining' => max(0, $totalInitial - $totalUsed),
            ],
            'types'  => $types,
        ];
    }

    /**
     * Days the employee can still file for a type (min of type and total balance).
     * "Other" only uses the total balance. Null = type not available/configured.
     */
    public function remainingFor(array $balances, string $type): ?int
    {
        $total = $balances['total']['remaining'];

        if ($type === 'Other') {
            return $balances['status'] === 'non-regular' ? null : $total;
        }

        if (! isset($balances['types'][$type])) {
            return null;
        }

        return min($balances['types'][$type]['remaining'], $total);
    }

    /** Map used by the form: type => days remaining (only available types). */
    public function remainingMap(array $balances): array
    {
        $map = [];
        foreach (array_keys($balances['types']) as $type) {
            $map[$type] = $this->remainingFor($balances, $type);
        }
        if ($balances['status'] !== 'non-regular') {
            $map['Other'] = $balances['total']['remaining'];
        }

        return $map;
    }
}