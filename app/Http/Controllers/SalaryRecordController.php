<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryRecordController extends Controller
{
    /**
     * Accounting's history of payslips that were already transferred to employees:
     * approved by Admin AND HR, and released to the employee.
     * Grouped by cutoff (15th / 30th tab), then by month (folders), then by employee.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()?->hasRole('Accounting'), 403, 'Only Accounting can view salary records.');

        $cutoff = $request->query('cutoff') === '30th' ? '30th' : '15th';

        $records = Payslip::with('employee')
            ->where('cutoff', $cutoff)
            ->where('admin_status', Payslip::APPROVED)
            ->where('hr_status', Payslip::APPROVED)
            ->whereNotNull('released_at')
            ->orderByDesc('transfer_date')
            ->orderBy('id')
            ->get();

        $months = $records
            ->groupBy(fn (Payslip $p) => ($p->transfer_date ?? $p->created_at)->format('Y-m'))
            ->sortKeysDesc()
            ->map(fn ($group, $key) => [
                'key'     => $key,
                'label'   => Carbon::createFromFormat('!Y-m', $key)->format('F Y'),
                'count'   => $group->count(),
                'records' => $group
                    ->sortBy(fn (Payslip $p) => $p->employee?->name)
                    ->map->toCard()
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return view('salary-record-management', [
            'cutoff' => $cutoff,
            'months' => $months,
        ]);
    }
}