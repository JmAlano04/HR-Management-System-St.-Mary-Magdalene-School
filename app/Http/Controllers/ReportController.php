<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Admin: HR + Accounting tabs.  HR: HR tab only.  Accounting: Accounting tab only.
     */
    public function index(Request $request)
    {
        /** @var User&\Spatie\Permission\Traits\HasRoles $user */
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasAnyRole(['Admin', 'HR', 'Accounting']),
            403,
            'Only Admin, HR and Accounting can view reports.'
        );

        $isAdmin = $user->hasAnyRole(['Admin']);
        $tabs = [];
        if ($isAdmin || $user->hasAnyRole(['HR']))         $tabs[] = 'hr';
        if ($isAdmin || $user->hasAnyRole(['Accounting'])) $tabs[] = 'accounting';

        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : $tabs[0];

        return view('report', [
            'tabs' => $tabs,
            'tab'  => $tab,
            'hr'   => in_array('hr', $tabs, true) ? $this->attendanceReport($request) : null,
            'acc'  => in_array('accounting', $tabs, true) ? $this->payrollReport($request) : null,
        ]);
    }

    /** Days worked / late / absent for one month, from attendance_records (current + archived). */
    private function attendanceReport(Request $request): array
    {
        $today = now();

        // Year dropdown: 5 years back to next year, plus any year that has records
        $years = collect(range($today->year - 5, $today->year + 1));
        $min = DB::table('attendance_records')->min('date');
        $max = DB::table('attendance_records')->max('date');
        if ($min && $max) {
            $years = $years->merge(range((int) substr($min, 0, 4), (int) substr($max, 0, 4)));
        }
        $years = $years->unique()->sort()->values()->all();

        $month = (int) $request->query('hr_month');
        $year  = (int) $request->query('hr_year');

        if ($month < 1 || $month > 12 || !in_array($year, $years, true)) {
            $month = $today->month;
            $year  = $today->year;

            // nothing recorded this month yet: open the most recent month that has records
            $hasThisMonth = DB::table('attendance_records')
                ->whereBetween('date', $this->monthRange($year, $month))->exists();
            if (!$hasThisMonth) {
                $latest = DB::table('attendance_records')->where('date', '<=', $today->toDateString())->max('date');
                if ($latest) {
                    $year  = (int) substr($latest, 0, 4);
                    $month = (int) substr($latest, 5, 2);
                }
            }
        }

        $rows = DB::table('attendance_records')
            ->whereBetween('date', $this->monthRange($year, $month))
            ->select('status', DB::raw('COUNT(*) as n'))
            ->groupBy('status')
            ->get();

        $counts = ['worked' => 0, 'late' => 0, 'absent' => 0];
        foreach ($rows as $r) {
            $s = strtolower(trim($r->status));
            if (in_array($s, ['present', 'half day'], true)) $counts['worked'] += $r->n;
            elseif ($s === 'late')                          $counts['late']   += $r->n;
            elseif ($s === 'absent')                        $counts['absent'] += $r->n;
        }

        $total = array_sum($counts);

        return [
            'month'       => $month,
            'year'        => $year,
            'years'       => $years,
            'period'      => Carbon::create($year, $month, 1)->format('F Y'),
            'counts'      => $counts,
            'total'       => $total,
            'recordCount' => $rows->sum('n'),
            'workedEnd'   => $total ? $counts['worked'] / $total * 100 : 0,
            'lateEnd'     => $total ? ($counts['worked'] + $counts['late']) / $total * 100 : 0,
        ];
    }

    /** Salary given vs deductions per month, from payslips released to employees. */
    private function payrollReport(Request $request): array
    {
        $months = [];
        Payslip::whereNotNull('released_at')
            ->whereNotNull('transfer_date')
            ->get(['transfer_date', 'net_pay', 'total_deductions'])
            ->each(function ($p) use (&$months) {
                $key = substr((string) $p->getRawOriginal('transfer_date'), 0, 7);   // 2026-09
                $months[$key]['salary']     = ($months[$key]['salary'] ?? 0) + (float) $p->net_pay;
                $months[$key]['deductions'] = ($months[$key]['deductions'] ?? 0) + (float) $p->total_deductions;
            });
        ksort($months);

        $latest = array_key_last($months);
        $years  = collect(array_map(fn ($k) => (int) substr($k, 0, 4), array_keys($months)))
            ->push(now()->year)->unique()->sort()->values()->all();

        $m = (string) $request->query('pay_month');
        $y = (int) $request->query('pay_year');
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $m) || !in_array($y, $years, true)) {
            $m = $latest ? substr($latest, 5, 2) : now()->format('m');
            $y = $latest ? (int) substr($latest, 0, 4) : now()->year;
        }

        $entry  = $months["{$y}-{$m}"] ?? null;
        $salary = (float) ($entry['salary'] ?? 0);
        $ded    = (float) ($entry['deductions'] ?? 0);
        $sum    = $salary + $ded;

        // 12 months of the selected year for the bar graph
        $series = [];
        for ($mm = 1; $mm <= 12; $mm++) {
            $k = sprintf('%d-%02d', $y, $mm);
            $series[] = [
                'label'      => Carbon::create(2000, $mm, 1)->format('M'),
                'month'      => sprintf('%02d', $mm),
                'salary'     => (float) ($months[$k]['salary'] ?? 0),
                'deductions' => (float) ($months[$k]['deductions'] ?? 0),
            ];
        }
        $seriesMax = max(1, ...array_map(fn ($s) => max($s['salary'], $s['deductions']), $series));

        return [
            'series'        => $series,
            'seriesMax'     => $seriesMax,
            'month'         => $m,
            'year'          => $y,
            'years'         => $years,
            'period'        => Carbon::create($y, (int) $m, 1)->format('F Y'),
            'hasData'       => $entry !== null,
            'salary'        => $salary,
            'deductions'    => $ded,
            'salaryPercent' => $sum > 0 ? $salary / $sum * 100 : 0,
        ];
    }

    private function monthRange(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1);

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }
}