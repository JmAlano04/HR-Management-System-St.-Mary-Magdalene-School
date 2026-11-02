<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use App\Support\AttendanceSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollProcessingController extends Controller
{
    /** Working days of the week (ISO: 1 = Monday ... 7 = Sunday). Change to [1,2,3,4,5,6] for a 6-day week. */
    private const WORK_WEEKDAYS      = [1, 2, 3, 4, 5];
    private const MONTHLY_DAYS       = 26;        // monthly wage = daily wage x 26
    private const LATE_PER_MINUTE    = 1.80;
    private const MALASAKITAN        = 50.00;     // every 15th and 30th pay period
    private const HONORARIUM         = 1924.31;   // administrative role only (fixed)
    private const CASH_ADVANCE_CAP   = 2000.00;   // max deduction per pay period
    private const HDMF               = 200.00;    // fixed amount, every pay period
    private const MAX_HOURS_PER_DAY  = 8;         // no overtime in payroll

    /** Attendance statuses that count as a day worked. "Absent" does not. */
    private const WORKED_STATUSES = ['Present', 'Late', 'Half Day'];

    /** Contribution rates, deducted on the 30th pay period only. Base = monthly wage (daily wage x 26). */
    private const RATES = [
        'sss'        => 0.05,
        'philhealth' => 0.025,
        'peraa_ceap' => 0.04,
    ];

    /** Pay period => attendance cutoff day. */
    private const CUTOFF_DAY = ['15th' => '10th', '30th' => '25th'];

    /** Deduction columns of payroll_records that Accounting still enters manually. */
    private const DED = ['sss_loan', 'philhealth_loan', 'peraa_ceap_loan', 'hdmf_loan', 'cash_advance'];

    /** Columns that are auto-computed; stored as 0 in payroll_records. */
    private const AUTO = ['sss', 'philhealth', 'peraa_ceap', 'hdmf'];

    private const DEFAULT_WAGES = [
        550.00, 577.50, 606.38, 636.70, 668.53, 701.96, 737.06, 773.91, 812.60, 853.23,
        895.89, 940.69, 987.72, 1037.11, 1088.96, 1142.13, 1199.24, 1259.20, 1322.16, 1388.27,
        1457.68, 1530.56, 1607.09, 1687.45, 1771.82, 1860.41, 1953.43, 2051.10, 2153.66, 2261.34,
    ];

    /** Accounting only: payroll calculation for one month + pay period. */
    public function index(Request $request)
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        $employees = $this->inputs($period, $cutoff)->map(function ($x) {
            $isAdmin = $this->isAdministrative($x['employee']);

            return [
                'id'             => $x['employee']->id,
                'name'           => $x['employee']->name,
                'employeeNumber' => $x['employee']->employee_id,
                'department'     => Employee::DEPARTMENTS[$x['employee']->department] ?? $x['employee']->department,
                'position'       => $x['employee']->position,
                'isAdmin'        => $isAdmin,
                'step'           => (int) $x['in']['step_no'],   // fixed, taken from the payslip record
                // scheduled days / days / hours / late come from attendance_records (read-only)
                'scheduledDays'  => (int) $x['in']['scheduled_days'],
                'daysWorked'     => (float) $x['in']['days_worked'],
                'hoursWorked'    => (float) $x['in']['hours_worked'],
                'lateMinutes'    => (float) $x['in']['late_minutes'],
                'honorarium'     => $isAdmin ? self::HONORARIUM : 0.0,
                'deductions'     => [
                    'sssLoan'        => (float) $x['in']['sss_loan'],
                    'philhealthLoan' => (float) $x['in']['philhealth_loan'],
                    'peraaCeapLoan'  => (float) $x['in']['peraa_ceap_loan'],
                    'hdmfLoan'       => (float) $x['in']['hdmf_loan'],
                    'cashAdvance'    => (float) $x['in']['cash_advance'],   // outstanding balance
                ],
            ];
        })->values();

        return view('payroll-processing', [
            'employees' => $employees,
            'steps'     => $this->steps(),
            'period'    => $period,
            'cutoff'    => $cutoff,                    // pay period key: 15th | 30th
            'cutoffs'   => $this->allCutoffs($period), // cutoff day + date range per pay period
        ]);
    }

    /**
     * Autosave of the table (called with fetch). Writes payroll_records only.
     * Days / hours / late are NOT taken from the browser: they are read from attendance_records.
     */
    public function save(Request $request): JsonResponse
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        // step_no and days/hours/late are intentionally not trusted from the request.
        $data = $request->validate([
            'rows'                    => ['required', 'array', 'min:1'],
            'rows.*.employee_id'      => ['required', 'integer', 'exists:employees,id'],
            'rows.*.sss_loan'         => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.philhealth_loan'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.peraa_ceap_loan'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.hdmf_loan'        => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.cash_advance'     => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $employees = Employee::with('user')
            ->whereIn('id', collect($data['rows'])->pluck('employee_id'))
            ->get()
            ->keyBy('id');
        $attendance = $this->attendanceTotals($period, $cutoff, $employees);
        $fixed      = $this->fixedStepNos($this->steps());
        $existing   = DB::table('payroll_records')
            ->where(['period' => $period, 'cutoff' => $cutoff])
            ->pluck('step_no', 'employee_id');
        $now        = now();

        $rows = collect($data['rows'])->map(function ($r) use ($employees, $attendance, $fixed, $existing, $period, $cutoff, $now) {
            $id  = (int) $r['employee_id'];
            $att = $attendance[$id];
            $row = [
                'employee_id'  => $id,
                // fixed step: payslip table first, then the saved record, then step 1
                'step_no'      => $fixed[$id] ?? $existing[$id] ?? 1,
                'days_worked'  => $att['days_worked'],
                'hours_worked' => $att['hours_worked'],
                'late_minutes' => $att['late_minutes'],
                // honorarium is fixed by role; the browser value is ignored
                'honorarium'   => $this->isAdministrative($employees[$id]) ? self::HONORARIUM : 0,
                'period'       => $period,
                'cutoff'       => $cutoff,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
            foreach (self::DED as $k) {
                $row[$k] = $r[$k] ?? 0;
            }
            foreach (self::AUTO as $k) {
                $row[$k] = 0;   // auto-computed on the fly, nothing to store
            }
            return $row;
        })->all();

        DB::table('payroll_records')->upsert(
            $rows,
            ['period', 'cutoff', 'employee_id'],
            array_merge(
                ['days_worked', 'hours_worked', 'late_minutes', 'honorarium', 'updated_at'],
                self::DED,
                self::AUTO
            )
        );

        return response()->json(['saved' => count($rows)]);
    }

    /** Save the daily wage table (payroll_steps). */
    public function saveSteps(Request $request): JsonResponse
    {
        $this->authorizeAccounting();

        $data = $request->validate([
            'steps'             => ['required', 'array', 'min:1', 'max:60'],
            'steps.*.name'      => ['nullable', 'string', 'max:60'],
            'steps.*.dailyWage' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        DB::transaction(function () use ($data) {
            DB::table('payroll_steps')->delete();
            $now = now();
            foreach (array_values($data['steps']) as $i => $s) {
                DB::table('payroll_steps')->insert([
                    'step_no'    => $i + 1,
                    'name'       => trim($s['name'] ?? '') ?: 'Step ' . ($i + 1),
                    'daily_wage' => round((float) $s['dailyWage'], 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return response()->json(['steps' => $this->steps()]);
    }

    /**
     * Transfer the SELECTED employees to Salary Monitoring. Reads attendance_records and the
     * SAVED payroll_records and recalculates everything on the server; nothing the browser
     * calculated is trusted.
     */
    public function transfer(Request $request): RedirectResponse
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        $ids = collect($request->validate([
            'employee_ids'   => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ], [
            'employee_ids.required' => 'Select at least one employee to transfer.',
        ])['employee_ids'])->map(fn ($id) => (int) $id);

        $steps        = $this->steps();
        $transferDate = Carbon::createFromFormat('Y-m-d', $period . '-01')->toDateString();
        $sent = 0;
        $skipped = 0;

        $selected = $this->inputs($period, $cutoff)
            ->filter(fn ($x) => $ids->contains($x['employee']->id));

        DB::transaction(function () use ($selected, $cutoff, $steps, $transferDate, &$sent, &$skipped) {
            foreach ($selected as $x) {
                $existing = Payslip::where([
                    'employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate,
                ])->first();

                // never overwrite a payslip already released to the employee
                if ($existing?->released_at) {
                    $skipped++;
                    continue;
                }

                $p = $this->compute($x['in'], $steps, $cutoff, $this->isAdministrative($x['employee']));
                $b = $p['breakdown'];

                Payslip::updateOrCreate(
                    ['employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate],
                    [
                        'step_name'          => $p['step']['name'],
                        'days_worked'        => $p['days'],
                        'hours_worked'       => $p['hours'],
                        'late_minutes'       => (int) round($p['lateMinutes']),
                        'basic_pay'          => $p['basicPay'],
                        'daily_wage'         => $p['step']['dailyWage'],
                        'honorarium'         => $p['honorarium'],
                        'gross_pay'          => $p['grossPay'],
                        'malasakitan'        => $b['malasakitan'],
                        'late_deduction'     => $b['late'],
                        'absences_deduction' => $b['absences'],
                        'sss'                => $b['sss'],
                        'sss_loan'           => $b['sss_loan'],
                        'philhealth'         => $b['philhealth'],
                        'philhealth_loan'    => $b['philhealth_loan'],
                        'peraa_ceap'         => $b['peraa_ceap'],
                        'peraa_ceap_loan'    => $b['peraa_ceap_loan'],
                        'hdmf'               => $b['hdmf'],
                        'hdmf_loan'          => $b['hdmf_loan'],
                        'cash_advance'       => $b['cash_advance'],
                        'total_deductions'   => $p['totalDeductions'],
                        'net_pay'            => $p['netPay'],

                        // a fresh transfer restarts the approval chain
                        'sent_to_admin_at'       => null,
                        'admin_status'           => Payslip::PENDING,
                        'admin_rejection_reason' => null,
                        'admin_reviewed_at'      => null,
                        'admin_reviewed_by'      => null,
                        'hr_status'              => Payslip::PENDING,
                        'hr_rejection_reason'    => null,
                        'hr_reviewed_at'         => null,
                        'hr_reviewed_by'         => null,
                        'released_at'            => null,
                    ]
                );
                $sent++;
            }
        });

        $info = $this->cutoffInfo($period, $cutoff);
        $msg  = "Transferred {$sent} payslip" . ($sent === 1 ? '' : 's')
              . " ({$cutoff} pay period, {$info['day']} cutoff: {$info['range']}) to Salary Monitoring.";
        if ($skipped) {
            $msg .= " {$skipped} already released and were left unchanged.";
        }

        return redirect()->route('salary-monitoring.index')->with('success', $msg);
    }

    // ------------------------------------------------------------------

    /**
     * Everything attendance-related for the pay period, built from the database:
     *
     *  - scheduled_days: working days (Mon-Fri) inside the cutoff window, minus unpaid
     *    non-working days (non_working_days.pay_type = 'unpaid')
     *  - days_worked / hours_worked / late_minutes: from attendance_records (time_in / time_out)
     *      * a day counts when the status is Present, Late or Half Day ("Absent" does not)
     *      * hours come from the work schedule (lunch deducted, no overtime), falling back
     *        to the stored hours_worked when there is no time out; capped at 8 per day
     *      * late minutes = sum of the late minutes of each day (after the grace period)
     *  - paid non-working days (non_working_days.pay_type = 'paid') on a working day are
     *    credited (paid_days / paid_hours) when the employee has no attendance that day
     *
     * Absences are then scheduled_days - days_worked, so a day with no attendance counts as absent.
     *
     * @param  iterable<Employee>  $employees  only ->id and ->position are used
     * @return array<int, array{scheduled_days: int, days_worked: float, hours_worked: float, late_minutes: float}>
     */
    private function attendanceTotals(string $period, string $cutoff, iterable $employees): array
    {
        $byId = [];
        foreach ($employees as $e) {
            $byId[$e->id] = $e;
        }

        [$from, $to] = $this->cutoffWindow($period, $cutoff);

        // non-working days in the window (holidays etc.)
        $nonWorking = DB::table('non_working_days')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($n) => Carbon::parse($n->date)->toDateString());

        // working days in the window; unpaid non-working days are not scheduled at all
        $scheduled = 0;
        $paidHolidays = [];   // [Y-m-d => [paid_days, paid_hours]]
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if (! in_array($d->dayOfWeekIso, self::WORK_WEEKDAYS, true)) {
                continue;
            }
            $key = $d->toDateString();
            $nwd = $nonWorking[$key] ?? null;

            if ($nwd && strtolower((string) $nwd->pay_type) !== 'paid') {
                continue;   // unpaid holiday: no work expected, no pay, not an absence
            }
            $scheduled++;
            if ($nwd) {
                $paidHolidays[$key] = [(float) $nwd->paid_days, (float) $nwd->paid_hours];
            }
        }

        // every employee gets a row, even when they have no attendance in the window
        $totals = [];
        foreach ($byId as $id => $_) {
            $totals[$id] = [
                'scheduled_days' => $scheduled,
                'days_worked'    => 0.0,
                'hours_worked'   => 0.0,
                'late_minutes'   => 0.0,
            ];
        }
        if (! $byId) {
            return $totals;
        }

        $records = AttendanceRecord::query()
            ->whereIn('employee_id', array_keys($byId))
            ->where('archived', false)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', self::WORKED_STATUSES)
            ->get();

        $attended = [];   // [employee_id => [Y-m-d => true]]

        foreach ($records as $rec) {
            $date     = Carbon::parse($rec->date)->startOfDay();
            $schedule = AttendanceSchedule::for($byId[$rec->employee_id]->position, $date);

            $in  = $rec->time_in ? AttendanceSchedule::toCarbon($date, $rec->time_in) : null;
            $out = $rec->time_out ? Carbon::parse($date->toDateString() . ' ' . $rec->time_out) : null;

            $hours = $in ? (float) $schedule->hoursWorked($in, $out) : 0.0;
            if ($hours <= 0) {
                $hours = (float) $rec->hours_worked;
            }
            $hours = min(max($hours, 0), self::MAX_HOURS_PER_DAY);

            $totals[$rec->employee_id]['days_worked']  += 1;
            $totals[$rec->employee_id]['hours_worked'] += $hours;
            $totals[$rec->employee_id]['late_minutes'] += $in ? (int) $schedule->lateMinutes($in) : 0;

            $attended[$rec->employee_id][$date->toDateString()] = true;
        }

        // paid holidays: credit employees who have no attendance that day
        foreach ($paidHolidays as $dateKey => [$paidDays, $paidHours]) {
            foreach (array_keys($byId) as $id) {
                if (empty($attended[$id][$dateKey])) {
                    $totals[$id]['days_worked']  += $paidDays;
                    $totals[$id]['hours_worked'] += $paidHours;
                }
            }
        }

        foreach ($totals as &$t) {
            $t['hours_worked'] = round($t['hours_worked'], 2);
            $t['late_minutes'] = round($t['late_minutes'], 2);
        }
        unset($t);

        return $totals;
    }

    /**
     * Fixed step per employee, taken from their latest payslip (payslips.step_name).
     * Returns [employee_id => step_no]. Employees with no payslip yet are not included.
     */
    private function fixedStepNos(array $steps): array
    {
        $byName = [];
        foreach ($steps as $i => $s) {
            $byName[$s['name']] = $i + 1;
        }

        return Payslip::orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->get(['employee_id', 'step_name'])
            ->unique('employee_id')
            ->filter(fn ($p) => isset($byName[$p->step_name]))
            ->mapWithKeys(fn ($p) => [$p->employee_id => $byName[$p->step_name]])
            ->all();
    }

    /**
     * Every employee with their saved payroll_record (loans, cash advance, step) and the
     * attendance totals for the cutoff window (days, hours, late).
     */
    private function inputs(string $period, string $cutoff)
    {
        $records = DB::table('payroll_records')
            ->where(['period' => $period, 'cutoff' => $cutoff])
            ->get()->keyBy('employee_id');

        $fixed = $this->fixedStepNos($this->steps());

        $defaults = array_merge(
            ['step_no' => 1, 'scheduled_days' => 0, 'days_worked' => 0, 'hours_worked' => 0, 'late_minutes' => 0, 'honorarium' => 0],
            array_fill_keys(array_merge(self::DED, self::AUTO), 0)
        );

        // with('user') loads the linked account once, so the role check does not query per employee.
        // Remove it if your Employee model has no user() relation.
        $employees  = Employee::with('user')->orderBy('name')->get();
        $attendance = $this->attendanceTotals($period, $cutoff, $employees);

        return $employees->map(function ($e) use ($records, $defaults, $fixed, $attendance) {
            $in = array_merge($defaults, (array) ($records[$e->id] ?? []));
            // attendance is the source of truth for days / hours / late
            $in = array_merge($in, $attendance[$e->id]);
            // the step is fixed: payslip table wins over anything saved in payroll_records
            $in['step_no'] = $fixed[$e->id] ?? $in['step_no'];

            return ['employee' => $e, 'in' => $in];
        });
    }

    /**
     * Payroll rules:
     *  - pay periods: 15th and 30th. Attendance cutoffs: 10th (15th pay period) and 25th (30th pay period)
     *  - scheduled days / days / hours / late: read from the database (attendance_records + non_working_days)
     *  - absences: (scheduled days - days worked) x daily wage
     *  - monthly wage: daily wage x 26
     *  - late: 1.80 / minute
     *  - MALASAKITAN: 50 on every pay period
     *  - SSS 5%, PhilHealth 2.5%, PERAA/CEAP 4%: 30th pay period only (base = monthly wage)
     *  - HDMF: fixed 200, every pay period
     *  - loans (SSS, PhilHealth, PERAA/CEAP, HDMF): manual input, deducted on the 30th pay period only
     *  - cash advance: max 2,000 per pay period, on both pay periods
     *  - honorarium: fixed 1,924.31, administrative role only
     */
    private function compute(array $r, array $steps, string $cutoff, bool $isAdmin): array
    {
        $step  = $steps[((int) $r['step_no']) - 1] ?? ['name' => 'Unassigned', 'dailyWage' => 0];
        $wage  = (float) $step['dailyWage'];
        $days  = (float) $r['days_worked'];
        $hours = min((float) $r['hours_worked'], $days * 8);

        $absences = max(0, (float) ($r['scheduled_days'] ?? 0) - $days) * $wage;

        $missing   = max(0, $days * 8 * 60 - $hours * 60);
        $late      = (float) $r['late_minutes'];
        $effective = max(0, min($late, $missing ?: $late));
        $lateDed   = $effective * self::LATE_PER_MINUTE;

        $ratio = $days > 0 ? min(max(($hours / $days) / 8, 0), 1) : 0;
        $basic = $wage * $days * $ratio;
        $honor = $isAdmin ? self::HONORARIUM : 0;
        $gross = $basic + $honor;

        $g      = fn (string $k) => max(0, (float) ($r[$k] ?? 0));
        $only30 = $cutoff === '30th';

        $monthlyWage = $wage * self::MONTHLY_DAYS;
        $contrib     = fn (string $k) => $only30 ? $monthlyWage * self::RATES[$k] : 0;

        // TODO: loans above 30,000 are deducted on the 15th too (paid within 10 months,
        // one request per year). Needs the principal from loan_requests.
        $breakdown = [
            'malasakitan'     => self::MALASAKITAN,
            'late'            => $lateDed,
            'absences'        => $absences,
            'sss'             => $contrib('sss'),
            'philhealth'      => $contrib('philhealth'),
            'hdmf'            => self::HDMF,
            'peraa_ceap'      => $contrib('peraa_ceap'),
            'sss_loan'        => $only30 ? $g('sss_loan') : 0,
            'philhealth_loan' => $only30 ? $g('philhealth_loan') : 0,
            'peraa_ceap_loan' => $only30 ? $g('peraa_ceap_loan') : 0,
            'hdmf_loan'       => $only30 ? $g('hdmf_loan') : 0,
            // cash_advance holds the outstanding balance; deduct up to the cap each pay period
            'cash_advance'    => min(self::CASH_ADVANCE_CAP, $g('cash_advance')),
        ];
        $breakdown = array_map(fn ($v) => round($v, 2), $breakdown);
        $total     = round(array_sum($breakdown), 2);

        return [
            'step'            => ['name' => $step['name'], 'dailyWage' => $wage],
            'monthlyWage'     => round($monthlyWage, 2),
            'days'            => $days,
            'hours'           => $hours,
            'lateMinutes'     => $effective,
            'basicPay'        => round($basic, 2),
            'honorarium'      => round($honor, 2),
            'grossPay'        => round($gross, 2),
            'breakdown'       => $breakdown,
            'totalDeductions' => $total,
            'netPay'          => round($gross - $total, 2),
        ];
    }

    /**
     * Administrative role gets the fixed honorarium of P1,924.31.
     * Checks every text field on the employee record (role, position, department,
     * employee type, etc.) and the roles on the linked user account.
     */
    private function isAdministrative(Employee $e): bool
    {
        $isAdmin = fn ($v) => is_string($v) && str_contains(strtolower($v), 'administrative');

        // 1. Any text column on the employee record
        foreach ($e->getAttributes() as $value) {
            if ($isAdmin($value)) {
                return true;
            }
        }
        if ($isAdmin(Employee::DEPARTMENTS[$e->role_type] ?? null)) {
            return true;
        }

        // 2. Role on the linked user account (Spatie)
        $user = method_exists($e, 'user') ? $e->user : null;

        return $user instanceof User
            && $user->getRoleNames()->contains(fn ($role) => $isAdmin($role));
    }

    /**
     * Attendance dates covered by a pay period of the given month (Y-m).
     *  - 15th pay period: 10th cutoff, covers the 26th of the previous month to the 10th
     *  - 30th pay period: 25th cutoff, covers the 11th to the 25th
     * Adjust the dates here if your actual cutoff windows differ.
     *
     * @return array{0: Carbon, 1: Carbon} [from, to]
     */
    private function cutoffWindow(string $period, string $payPeriod): array
    {
        $m = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfDay();

        if ($payPeriod === '15th') {
            return [$m->copy()->subMonthNoOverflow()->day(26), $m->copy()->day(10)];
        }

        return [$m->copy()->day(11), $m->copy()->day(25)];
    }

    /** Cutoff day and date range label for display. */
    private function cutoffInfo(string $period, string $payPeriod): array
    {
        [$from, $to] = $this->cutoffWindow($period, $payPeriod);

        return [
            'day'   => self::CUTOFF_DAY[$payPeriod],
            'range' => $from->format('M j') . ' - ' . $to->format('M j, Y'),
        ];
    }

    private function allCutoffs(string $period): array
    {
        return [
            '15th' => $this->cutoffInfo($period, '15th'),
            '30th' => $this->cutoffInfo($period, '30th'),
        ];
    }

    private function periodAndCutoff(Request $request): array
    {
        $v = validator($request->all(), [
            'period' => ['nullable', 'date_format:Y-m'],
            'cutoff' => ['nullable', 'in:15th,30th'],   // pay period key
        ])->validate();

        return [$v['period'] ?? now()->format('Y-m'), $v['cutoff'] ?? '15th'];
    }

    /** Steps from the DB, seeded with the default wage table the first time. */
    private function steps(): array
    {
        if (DB::table('payroll_steps')->count() === 0) {
            $now = now();
            foreach (self::DEFAULT_WAGES as $i => $w) {
                DB::table('payroll_steps')->insert([
                    'step_no' => $i + 1, 'name' => 'Step ' . ($i + 1), 'daily_wage' => $w,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return DB::table('payroll_steps')->orderBy('step_no')->get()
            ->map(fn ($s) => ['name' => $s->name, 'dailyWage' => (float) $s->daily_wage])
            ->all();
    }

    private function authorizeAccounting(): void
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasRole('Accounting'),
            403,
            'Only Accounting can process payroll.'
        );
    }
}