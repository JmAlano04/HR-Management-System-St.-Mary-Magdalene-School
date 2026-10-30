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
    /** Non-regular employees: the only leave type they can use and its days. */
    public const NON_REGULAR_TYPE = 'Non-Convertible Leave';
    public const NON_REGULAR_DAYS = 5;

    /** regular | non-regular | unknown */
    public function status(Employee $employee): string
    {
        // Sa database ay 'non regular' (may espasyo); tinatanggap din ang 'non-regular' at 'non_regular'
        $value = strtolower(str_replace(['-', '_'], ' ', trim((string) $employee->employment_status)));

        return match ($value) {
            'regular'     => 'regular',
            'non regular' => 'non-regular',
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

        // May default lahat para hindi mag-crash kapag kulang ang config/leave.php.
        // Non-regular: laging 5 days ng Non-Convertible Leave. Hindi na binabasa ang
        // leave.non_regular.days sa config, kaya hindi na ito magiging 4 (o ibang bilang).
        $nonRegType = (string) config('leave.non_regular.type', self::NON_REGULAR_TYPE);
        $nonRegDays = self::NON_REGULAR_DAYS;
        $total      = (int) config('leave.total', 0);
        $configured = (array) config('leave.types', []);

        // Kapag walang user account ang employee, walang leave requests na maiuugnay
        // (iwas din na mabilang ang mga request na NULL ang user_id).
        $approved = $employee->user_id
            ? LeaveRequest::forUser($employee->user_id)
                ->approved()
                ->whereYear('start_date', $year)
                ->get(['type', 'start_date', 'end_date'])
            : collect();

        // Non-regular: only the allowed type counts
        $eligible  = $nonReg ? $approved->where('type', $nonRegType) : $approved;
        $totalUsed = (int) $eligible->sum(fn (LeaveRequest $r) => $r->days_count);

        $totalInitial = $nonReg ? $nonRegDays : $total;

        // Non-regular: ang allowed type lang, kahit wala ito sa leave.types
        $typeList = $nonReg ? [$nonRegType => $nonRegDays] : $configured;

        $types = [];
        foreach ($typeList as $type => $days) {
            $initial = (int) $days;
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